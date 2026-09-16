<?php

namespace App\Investor\Services;

use App\GoldTrading\Models\CapitalAllocation;
use App\GoldTrading\Models\ProfitDistribution;
use App\Investor\Exceptions\ReconciliationFailed;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\Flow;
use App\Investor\Ledger\LedgerEvent;
use App\Investor\Models\LedgerEntry;
use App\Investor\Support\Money;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Support\Carbon;

/**
 * Assembles an investor's account statement from the ledger.
 *
 * The statement is an accounting document, not a rendering of the wallet. Every
 * figure on it comes from investor_ledger_entries and can be followed back to
 * the transaction and the deal that produced it. The wallet is used once, as a
 * thing to check against, never as a source.
 *
 * Nothing is returned unless it reconciles. build() either hands back a
 * statement whose arithmetic has been proved against the ledger at full stored
 * precision, or it throws.
 */
class StatementBuilder
{
    public function __construct(private readonly LedgerReconciler $reconciler)
    {
    }

    public function build(User $user, ?string $from = null, ?string $to = null): array
    {
        $periodStart = $from ? Carbon::parse($from)->startOfDay() : null;
        $periodEnd   = $to ? Carbon::parse($to)->endOfDay() : null;

        $all = LedgerEntry::forUser($user->id)->chronological()->get();

        $before = $periodStart
            ? $all->filter(fn ($e) => $e->occurred_at->lt($periodStart))
            : $all->take(0);

        $within = $all->filter(function ($e) use ($periodStart, $periodEnd) {
            if ($periodStart && $e->occurred_at->lt($periodStart)) {
                return false;
            }

            return ! ($periodEnd && $e->occurred_at->gt($periodEnd));
        })->values();

        $opening = $this->positionAfter($before->last());
        $closing = $this->positionAfter($within->last() ?? $before->last());

        $movements = $this->movements($within);
        $totals = $this->totals($within);

        // A statement whose period ends today is making a claim about money the
        // application can spend right now, so it is held to the wallet as well.
        $isCurrent = $periodEnd === null || $periodEnd->gte(Carbon::now()->startOfDay());

        $failures = $this->verify($user, $opening, $closing, $totals, $within, $isCurrent);

        if ($failures !== []) {
            throw new ReconciliationFailed($failures);
        }

        $currency = Currency::where('default', true)->first();

        return [
            'investor' => [
                'name'     => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: $user->username,
                'username' => $user->username,
                'email'    => $user->email,
                'account'  => $this->accountNumber($user),
            ],
            'period' => [
                'start'    => $periodStart,
                'end'      => $periodEnd,
                'label'    => $this->periodLabel($periodStart, $periodEnd, $within),
                'interim'  => $isCurrent,
            ],
            'currency'  => $currency?->code ?? 'USD',
            'opening'   => $opening,
            'closing'   => $closing,
            'movements' => $movements,
            'totals'    => $totals,
            'deals'     => $this->deals($user),
            'narrative' => $this->narrative($user, $within, $closing),
            'generated_at' => Carbon::now(),
            'entry_count'  => $within->count(),
        ];
    }

    /** The three buckets immediately after a given entry, or zero before any. */
    private function positionAfter(?LedgerEntry $entry): array
    {
        if (! $entry) {
            return Bucket::zeroed() + ['total' => 0.0];
        }

        $position = [
            Bucket::AVAILABLE => (float) $entry->balance_available,
            Bucket::PROFIT    => (float) $entry->balance_profit,
            Bucket::COMMITTED => (float) $entry->balance_committed,
        ];

        return $position + ['total' => array_sum($position)];
    }

    /**
     * One row per movement rather than per leg. An investor thinks of putting
     * money into a deal as one event, not three bucket adjustments.
     */
    private function movements($entries): array
    {
        $rows = [];

        foreach ($entries->groupBy('group_uuid') as $legs) {
            $first = $legs->first();
            $last = $legs->last();
            $internal = $first->flow === Flow::INTERNAL;

            $in = (float) $legs->where('amount_usd', '>', 0)->sum('amount_usd');
            $out = abs((float) $legs->where('amount_usd', '<', 0)->sum('amount_usd'));

            $rows[] = [
                'date'        => $first->occurred_at,
                'event'       => $first->event_type,
                'label'       => LedgerEvent::label($first->event_type),
                'description' => $first->description,
                'reference'   => $first->reference,
                'trx_id'      => $first->trx_id,
                'flow'        => $first->flow,
                'internal'    => $internal,
                'marker'      => $first->flow === Flow::MARKER,
                'money_in'    => $internal ? null : ($in > 0 ? $in : null),
                'money_out'   => $internal ? null : ($out > 0 ? $out : null),
                'moved'       => $internal ? $in : null,
                'legs'        => $legs->map(fn ($l) => [
                    'bucket' => $l->bucket,
                    'amount' => (float) $l->amount_usd,
                ])->all(),
                'balance_available' => (float) $last->balance_available,
                'balance_profit'    => (float) $last->balance_profit,
                'balance_committed' => (float) $last->balance_committed,
                'balance_total'     => (float) $last->balance_available + (float) $last->balance_profit + (float) $last->balance_committed,
                'gold_lot_id'       => $first->gold_lot_id,
            ];
        }

        return $rows;
    }

    private function totals($entries): array
    {
        $in = 0.0;
        $out = 0.0;
        $internal = 0.0;

        foreach ($entries as $entry) {
            match ($entry->flow) {
                Flow::EXTERNAL_IN  => $in += (float) $entry->amount_usd,
                Flow::EXTERNAL_OUT => $out += abs((float) $entry->amount_usd),
                Flow::INTERNAL     => $internal += max(0.0, (float) $entry->amount_usd),
                default            => null,
            };
        }

        $byEvent = [];

        foreach ($entries->where('flow', '!=', Flow::INTERNAL) as $entry) {
            $byEvent[$entry->event_type] = ($byEvent[$entry->event_type] ?? 0) + (float) $entry->amount_usd;
        }

        return [
            'external_in'  => $in,
            'external_out' => $out,
            // Gross value shifted between buckets, counted once rather than twice:
            // the negative legs mirror the positive ones.
            'internal'     => $internal,
            'net_movement' => $in - $out,
            'by_event'     => $byEvent,
        ];
    }

    /**
     * The checks that decide whether this statement may exist at all.
     *
     * @return array<int, string> empty when the statement is sound
     */
    private function verify(User $user, array $opening, array $closing, array $totals, $within, bool $isCurrent): array
    {
        $failures = [];

        // 1. Opening + what came in - what went out = closing. Internal movements
        //    are excluded by construction, which is the whole point of the flow.
        $expected = $opening['total'] + $totals['external_in'] - $totals['external_out'];

        if (! Money::equal($expected, $closing['total'])) {
            $failures[] = 'Opening ' . Money::exact($opening['total'])
                . ' + in ' . Money::exact($totals['external_in'])
                . ' - out ' . Money::exact($totals['external_out'])
                . ' = ' . Money::exact($expected)
                . ', but the ledger closes at ' . Money::exact($closing['total']) . '.';
        }

        // 2. The three buckets must account for the whole position.
        $sum = $closing[Bucket::AVAILABLE] + $closing[Bucket::PROFIT] + $closing[Bucket::COMMITTED];

        if (! Money::equal($sum, $closing['total'])) {
            $failures[] = 'Available + Profit + Committed = ' . Money::exact($sum)
                . ' does not equal the total position ' . Money::exact($closing['total']) . '.';
        }

        // 3. Internal movements must not have moved the needle.
        $internalNet = 0.0;

        foreach ($within->where('flow', Flow::INTERNAL) as $entry) {
            $internalNet += (float) $entry->amount_usd;
        }

        if (! Money::equal($internalNet, 0.0)) {
            $failures[] = 'Internal movements net to ' . Money::exact($internalNet)
                . ' instead of zero, so a transfer between buckets changed the total position.';
        }

        // 4. A current statement is also held against the money the app can spend,
        //    and against the deals holding committed capital.
        if ($isCurrent) {
            $report = $this->reconciler->forUser($user);

            if (! $report['passed']) {
                foreach ($report['discrepancies'] as $d) {
                    $failures[] = $d;
                }
            }

            $wallet = $this->wallet($user);

            if ($wallet) {
                if (! Money::equal((float) $wallet->balance, $closing[Bucket::AVAILABLE])) {
                    $failures[] = 'Statement closes Available at ' . Money::exact($closing[Bucket::AVAILABLE])
                        . ' but the wallet holds ' . Money::exact((float) $wallet->balance) . '.';
                }

                if (! Money::equal((float) $wallet->profit_balance, $closing[Bucket::PROFIT])) {
                    $failures[] = 'Statement closes Profit at ' . Money::exact($closing[Bucket::PROFIT])
                        . ' but the wallet holds ' . Money::exact((float) $wallet->profit_balance) . '.';
                }
            }
        }

        return $failures;
    }

    /**
     * Every deal this investor's money has been attributed to.
     *
     * Allocations made before capital locking existed never moved a balance and
     * have no ledger movements behind them. They are shown as what they are —
     * a historical attribution — rather than dressed up as committed capital,
     * which would make the deal history disagree with the ledger.
     */
    private function deals(User $user): array
    {
        $allocations = CapitalAllocation::with('lot')
            ->where('user_id', $user->id)
            ->orderBy('allocated_at')
            ->get();

        $distributions = ProfitDistribution::where('user_id', $user->id)
            ->get()
            ->keyBy('gold_lot_id');

        return $allocations->map(function (CapitalAllocation $allocation) use ($distributions) {
            $lot = $allocation->lot;
            $distribution = $distributions->get($allocation->gold_lot_id);
            $ledgerBacked = (bool) $allocation->locked_balance;

            return [
                'reference'      => $lot->lot_code ?? ('#' . $allocation->gold_lot_id),
                'name'           => $lot->project_name ?? '-',
                'location'       => $lot->location ?? null,
                'date'           => $lot->purchase_date ?? $allocation->allocated_at,
                'capital'        => (float) $allocation->amount_usd,
                'gold_grams'     => $lot?->gross_grams !== null ? (float) $lot->gross_grams : null,
                'investor_share' => $distribution?->profit_share_percent,
                'investor_profit' => $distribution ? (float) $distribution->amount_usd : null,
                'capital_returned' => $allocation->status === CapitalAllocation::STATUS_RETURNED
                    ? (float) $allocation->amount_usd
                    : null,
                'ledger_backed' => $ledgerBacked,
                'status'        => $this->dealStatus($allocation, $distribution, $ledgerBacked),
            ];
        })->all();
    }

    private function dealStatus(CapitalAllocation $allocation, ?ProfitDistribution $distribution, bool $ledgerBacked): string
    {
        if (! $ledgerBacked) {
            return 'Historical deal attribution';
        }

        return match (true) {
            $allocation->status === CapitalAllocation::STATUS_RETURNED && $distribution !== null => 'Completed',
            $allocation->status === CapitalAllocation::STATUS_RETURNED => 'Capital returned',
            default => 'Capital committed',
        };
    }

    /** Plain-language figures for the "what happened to my money" section. */
    private function narrative(User $user, $within, array $closing): array
    {
        $deposits = 0.0;
        $profit = 0.0;
        $committed = 0.0;
        $returned = 0.0;
        $withdrawn = 0.0;

        foreach ($within as $entry) {
            $amount = (float) $entry->amount_usd;

            match ($entry->event_type) {
                LedgerEvent::DEPOSIT, LedgerEvent::ADMIN_ADJUSTMENT => $deposits += max(0.0, $amount),
                LedgerEvent::PROFIT_CREDITED => $profit += $amount,
                LedgerEvent::CAPITAL_ALLOCATED => $committed += $entry->bucket === Bucket::COMMITTED ? $amount : 0.0,
                LedgerEvent::CAPITAL_RETURNED => $returned += $entry->bucket === Bucket::COMMITTED ? abs($amount) : 0.0,
                LedgerEvent::WITHDRAWAL_REQUESTED => $withdrawn += abs($amount),
                default => null,
            };
        }

        return [
            'deposits'         => $deposits,
            'profit_earned'    => $profit,
            'capital_deployed' => $committed,
            'capital_returned' => $returned,
            'withdrawn'        => $withdrawn,
            'available'        => $closing[Bucket::AVAILABLE],
            'profit_balance'   => $closing[Bucket::PROFIT],
            'committed'        => $closing[Bucket::COMMITTED],
            'total'            => $closing['total'],
        ];
    }

    private function wallet(User $user): ?UserWallet
    {
        $currency = Currency::where('default', true)->first();

        return UserWallet::where('user_id', $user->id)
            ->whereHas('currency', fn ($q) => $q->where('code', $currency?->code))
            ->first();
    }

    private function accountNumber(User $user): string
    {
        return 'INV-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);
    }

    private function periodLabel(?Carbon $start, ?Carbon $end, $within): string
    {
        $first = $within->first()?->occurred_at;
        $last = $within->last()?->occurred_at;

        $from = $start ?? $first;
        $to = $end ?? $last;

        if (! $from || ! $to) {
            return 'No activity';
        }

        return $from->format('d M Y') . ' to ' . $to->format('d M Y');
    }
}
