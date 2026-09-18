<?php

namespace App\Investor\Services;

use App\Investor\Exceptions\LedgerException;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\LedgerEvent;
use App\Investor\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Brings an investor's ledger up to date with the transactions table.
 *
 * The platform writes transactions with the query builder rather than the
 * Eloquent model, so there are no model events to hook: nothing can push a
 * movement into the ledger at the moment it happens. Instead the ledger pulls,
 * and the reconciler's job is to notice if the pull ever falls behind. That is
 * a deliberate trade: a ledger that lags visibly beats one that is silently
 * incomplete.
 *
 * The same code does the historical backfill, because a backfill is only a sync
 * that starts from nothing.
 */
class LedgerSynchroniser
{
    public function __construct(
        private readonly TransactionLedgerMapper $mapper,
        private readonly LedgerRecorder $recorder,
    ) {
    }

    /**
     * @return array{scanned: int, movements: int, entries: int, skipped: int, events: array}
     */
    public function syncUser(User $user, bool $rebuild = false): array
    {
        if ($rebuild) {
            $this->clearRebuildable($user);
        }

        $transactions = DB::table('transactions')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->orderBy('id')
            ->get();

        $report = ['scanned' => $transactions->count(), 'movements' => 0, 'entries' => 0, 'skipped' => 0, 'events' => []];

        foreach ($transactions as $trx) {
            $postedStage = $this->postedStage((int) $trx->id, $user->id);
            $running = $this->currentPosition($user->id);

            $movements = $this->mapper->map($trx, $user->id, $running, $postedStage);

            if ($movements === []) {
                $report['skipped']++;
                continue;
            }

            foreach ($movements as $movement) {
                $context = $movement['context'];
                $context['meta'] = array_merge($context['meta'] ?? [], [
                    'stage'              => $movement['stage'],
                    'source_transaction' => $trx->trx_id,
                ]);

                $entries = $this->recorder->post($user->id, $movement['event'], $movement['legs'], $context);

                $report['movements']++;
                $report['entries'] += count($entries);
                $report['events'][$movement['event']] = ($report['events'][$movement['event']] ?? 0) + 1;
            }
        }

        return $report;
    }

    /** The investor's position right now, straight off the last ledger entry. */
    public function currentPosition(int $userId): array
    {
        $last = LedgerEntry::where('user_id', $userId)->orderByDesc('seq')->first();

        if (! $last) {
            return Bucket::zeroed();
        }

        return [
            Bucket::AVAILABLE => (float) $last->balance_available,
            Bucket::PROFIT    => (float) $last->balance_profit,
            Bucket::COMMITTED => (float) $last->balance_committed,
        ];
    }

    /** The furthest stage already recorded for a transaction, or null if it is new. */
    private function postedStage(int $transactionId, int $userId): ?string
    {
        $entry = LedgerEntry::where('user_id', $userId)
            ->where('transaction_id', $transactionId)
            ->orderByDesc('seq')
            ->first();

        return is_array($entry?->meta) ? ($entry->meta['stage'] ?? null) : null;
    }

    /**
     * Clears the entries a rebuild is allowed to recreate.
     *
     * A rebuild replays the transactions table, so anything that was derived from
     * it can be thrown away and derived again. Corrections cannot: they were a
     * human decision that exists nowhere else, and deleting one would destroy the
     * audit trail this table is for. So a rebuild refuses to run once any exist.
     *
     * This is the only sanctioned path that removes ledger rows, and it goes via
     * the query builder because the model rightly refuses to delete itself.
     */
    private function clearRebuildable(User $user): void
    {
        $corrections = LedgerEntry::where('user_id', $user->id)
            ->whereIn('event_type', [LedgerEvent::BUCKET_CORRECTION])
            ->count();

        if ($corrections > 0) {
            throw new LedgerException(
                'Refusing to rebuild ' . $user->username . "'s ledger: it holds " . $corrections
                . ' correction entries that exist nowhere else and cannot be derived again.'
            );
        }

        DB::table('investor_ledger_entries')->where('user_id', $user->id)->delete();
    }
}
