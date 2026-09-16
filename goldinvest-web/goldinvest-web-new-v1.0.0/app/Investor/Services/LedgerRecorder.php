<?php

namespace App\Investor\Services;

use App\Investor\Exceptions\LedgerException;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\Flow;
use App\Investor\Ledger\LedgerEvent;
use App\Investor\Models\LedgerEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The only thing that writes to the investor ledger.
 *
 * Everything goes through post(): a movement is a set of legs, each naming a
 * bucket and a signed amount. The recorder refuses anything that would make the
 * ledger untrue — an internal movement whose legs do not cancel out, an amount
 * of zero where money was supposed to move, an entry dated before the one
 * before it — and it computes the running balances itself so that no caller can
 * get them wrong.
 */
class LedgerRecorder
{
    /** Money is stored to 8 decimal places; anything smaller is rounding noise. */
    private const EPSILON = 0.000001;

    /**
     * Record one movement.
     *
     * @param  array<int, array{bucket: string, amount: float}>  $legs
     * @param  array<string, mixed>  $context
     * @return array<int, LedgerEntry>
     */
    public function post(int $userId, string $eventType, array $legs, array $context = []): array
    {
        LedgerEvent::assert($eventType);

        $legs = $this->normalise($legs);
        $flow = $this->resolveFlow($eventType, $legs, $context);

        $occurredAt = isset($context['occurred_at'])
            ? Carbon::parse($context['occurred_at'])
            : Carbon::now();

        return DB::transaction(function () use ($userId, $eventType, $legs, $flow, $occurredAt, $context) {
            $last = LedgerEntry::where('user_id', $userId)
                ->orderByDesc('seq')
                ->lockForUpdate()
                ->first();

            [$occurredAt, $reportedAt] = $this->monotonic($last, $occurredAt);

            if ($reportedAt !== null) {
                $context['meta'] = array_merge($context['meta'] ?? [], ['occurred_at_reported' => $reportedAt]);
            }

            $running = $last
                ? [
                    Bucket::AVAILABLE => (float) $last->balance_available,
                    Bucket::PROFIT    => (float) $last->balance_profit,
                    Bucket::COMMITTED => (float) $last->balance_committed,
                ]
                : Bucket::zeroed();

            $seq = $last ? (int) $last->seq : 0;
            $reference = $context['reference'] ?? $this->nextReference($eventType, $occurredAt);
            $group = (string) Str::uuid();

            $entries = [];

            foreach ($legs as $leg) {
                $running[$leg['bucket']] = round($running[$leg['bucket']] + $leg['amount'], 8);

                $entries[] = LedgerEntry::create([
                    'user_id'           => $userId,
                    'seq'               => ++$seq,
                    'occurred_at'       => $occurredAt,
                    'entry_date'        => $occurredAt->toDateString(),
                    'event_type'        => $eventType,
                    'bucket'            => $leg['bucket'],
                    'amount_usd'        => $leg['amount'],
                    'flow'              => $flow,
                    'group_uuid'        => $group,
                    'balance_available' => $running[Bucket::AVAILABLE],
                    'balance_profit'    => $running[Bucket::PROFIT],
                    'balance_committed' => $running[Bucket::COMMITTED],
                    'reference'         => $reference,
                    'trx_id'            => $context['trx_id'] ?? null,
                    'transaction_id'    => $context['transaction_id'] ?? null,
                    'gold_lot_id'       => $context['gold_lot_id'] ?? null,
                    'allocation_id'     => $context['allocation_id'] ?? null,
                    'reverses_entry_id' => $context['reverses_entry_id'] ?? null,
                    'description'       => $context['description'] ?? LedgerEvent::label($eventType),
                    'meta'              => $context['meta'] ?? null,
                ]);
            }

            $this->assertNoNegativeBucket($running, $eventType, $context);

            return $entries;
        });
    }

    /**
     * Drops legs that move nothing, rounds the rest, and checks the buckets are real.
     *
     * @param  array<int, array{bucket: string, amount: float}>  $legs
     */
    private function normalise(array $legs): array
    {
        $clean = [];

        foreach ($legs as $leg) {
            if (! isset($leg['bucket']) || ! array_key_exists('amount', $leg)) {
                throw new LedgerException('Every ledger leg needs a bucket and an amount.');
            }

            Bucket::assert($leg['bucket']);

            $amount = round((float) $leg['amount'], 8);

            if (abs($amount) < self::EPSILON) {
                continue;
            }

            $clean[] = ['bucket' => $leg['bucket'], 'amount' => $amount];
        }

        return $clean;
    }

    /**
     * Works out whether this movement brings money in, takes it out, or shuffles
     * it between buckets — and refuses it if the legs disagree with the event.
     */
    private function resolveFlow(string $eventType, array &$legs, array $context): string
    {
        if ($legs === []) {
            // A marker records that something happened without money moving, which
            // is how an approved withdrawal is stamped: the money left when the
            // request was made, not when the admin clicked approve.
            if (($context['flow'] ?? null) === Flow::MARKER) {
                $legs = [['bucket' => Bucket::AVAILABLE, 'amount' => 0.0]];

                return Flow::MARKER;
            }

            throw new LedgerException('A ledger movement must move something. Event: ' . $eventType);
        }

        if (LedgerEvent::isInternal($eventType)) {
            if (count($legs) < 2) {
                throw new LedgerException(
                    'An internal movement needs at least two legs, since money has to come from somewhere. Event: ' . $eventType
                );
            }

            $sum = round(array_sum(array_column($legs, 'amount')), 8);

            if (abs($sum) > self::EPSILON) {
                throw new LedgerException(
                    'Internal movement "' . $eventType . '" does not balance: legs sum to '
                    . number_format($sum, 8) . ' instead of zero. Total position would have changed.'
                );
            }

            return Flow::INTERNAL;
        }

        $positive = array_filter($legs, fn ($l) => $l['amount'] > 0);
        $negative = array_filter($legs, fn ($l) => $l['amount'] < 0);

        if ($positive !== [] && $negative !== []) {
            throw new LedgerException(
                'External movement "' . $eventType . '" has legs going both ways. '
                . 'Money entering and leaving at once is an internal transfer; classify it as one.'
            );
        }

        return $positive !== [] ? Flow::EXTERNAL_IN : Flow::EXTERNAL_OUT;
    }

    /**
     * Keeps entry dates from going backwards.
     *
     * Order in this ledger is defined by seq, not by the clock, so an entry dated
     * before the one it follows breaks nothing arithmetically. It does break date
     * ranges: a statement for a period would show an opening balance that never
     * existed. Since the causes are mundane — clock skew between the application
     * and the database, a timezone difference, a transaction backdated by an admin
     * — refusing the movement would be worse than the problem.
     *
     * So the date is clamped to the previous entry's and the date that was
     * actually claimed is kept in meta, where it can still be seen.
     *
     * @return array{0: CarbonInterface, 1: string|null}
     */
    private function monotonic(?LedgerEntry $last, CarbonInterface $occurredAt): array
    {
        if (! $last || ! $occurredAt->lt($last->occurred_at)) {
            return [$occurredAt, null];
        }

        return [$last->occurred_at->copy(), $occurredAt->toDateTimeString()];
    }

    /**
     * An investor cannot owe the company a negative amount of their own money.
     * This catches a deal taking capital that was never there.
     */
    private function assertNoNegativeBucket(array $running, string $eventType, array $context): void
    {
        foreach ($running as $bucket => $value) {
            if ($value < -self::EPSILON) {
                throw new LedgerException(
                    'Movement "' . $eventType . '" would leave ' . Bucket::label($bucket)
                    . ' at ' . number_format($value, 2) . '.'
                    . ($context['trx_id'] ?? null ? ' Transaction: ' . $context['trx_id'] : '')
                );
            }
        }
    }

    /**
     * Hands out the next reference for this event type and day, e.g. CAP-20260916-000001.
     *
     * The counter row is locked for the rest of the posting transaction, so two
     * movements posted at the same moment cannot be given the same number.
     */
    private function nextReference(string $eventType, CarbonInterface $occurredAt): string
    {
        $prefix = LedgerEvent::referencePrefix($eventType);
        $day = $occurredAt->toDateString();

        $row = DB::table('ledger_reference_sequences')
            ->where('prefix', $prefix)
            ->where('day', $day)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            DB::table('ledger_reference_sequences')->insert([
                'prefix'      => $prefix,
                'day'         => $day,
                'next_number' => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $number = 1;
        } else {
            $number = (int) $row->next_number;
        }

        DB::table('ledger_reference_sequences')
            ->where('prefix', $prefix)
            ->where('day', $day)
            ->update(['next_number' => $number + 1, 'updated_at' => now()]);

        return sprintf('%s-%s-%06d', $prefix, str_replace('-', '', $day), $number);
    }
}
