<?php

namespace App\Investor\Services;

use App\GoldTrading\Models\CapitalAllocation;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\Flow;
use App\Investor\Models\LedgerEntry;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Support\Facades\DB;

/**
 * Checks that the ledger still tells the truth, in four ways:
 *
 *   1. Internally — the running balances follow from the movements.
 *   2. Against transactions — nothing has happened that the ledger has not seen.
 *   3. Against user_wallets — the ledger agrees with the money the app will spend.
 *   4. Against gold_capital_allocations — committed capital matches open deals.
 *
 * Nothing downstream may produce an investor-facing document unless all four
 * pass. A statement that looks professional and is wrong is worse than no
 * statement.
 */
class LedgerReconciler
{
    private const TOLERANCE = 0.005;

    public function __construct(
        private readonly TransactionLedgerMapper $mapper,
        private readonly LedgerSynchroniser $synchroniser,
    ) {
    }

    public function forUser(User $user): array
    {
        $entries = LedgerEntry::forUser($user->id)->chronological()->get();
        $position = $this->synchroniser->currentPosition($user->id);

        $checks = [
            'internal'    => $this->checkInternal($entries),
            'transactions' => $this->checkTransactions($user, $entries),
            'wallet'      => $this->checkWallet($user, $position),
            'allocations' => $this->checkAllocations($user, $position),
        ];

        $totals = $this->totals($entries);

        return [
            'user'       => $user->username,
            'entries'    => $entries->count(),
            'position'   => $position,
            'total'      => round(array_sum($position), 8),
            'totals'     => $totals,
            'checks'     => $checks,
            'passed'     => collect($checks)->every(fn ($c) => $c['passed']),
            'discrepancies' => collect($checks)->flatMap(fn ($c) => $c['discrepancies'])->values()->all(),
        ];
    }

    /** Opening position, external flows and internal movement over a period. */
    public function totals($entries): array
    {
        $externalIn = 0.0;
        $externalOut = 0.0;
        $internal = 0.0;

        foreach ($entries as $entry) {
            match ($entry->flow) {
                Flow::EXTERNAL_IN  => $externalIn += $entry->amount_usd,
                Flow::EXTERNAL_OUT => $externalOut += abs($entry->amount_usd),
                Flow::INTERNAL     => $internal += abs($entry->amount_usd),
                default            => null,
            };
        }

        return [
            'external_in'  => round($externalIn, 8),
            'external_out' => round($externalOut, 8),
            // Counted as the gross moved between buckets; by construction the signed
            // sum of every internal leg is zero, which is checked below.
            'internal_gross' => round($internal, 8),
        ];
    }

    private function checkInternal($entries): array
    {
        $running = Bucket::zeroed();
        $discrepancies = [];
        $expectedSeq = 0;
        $internalNet = 0.0;

        foreach ($entries as $entry) {
            $expectedSeq++;

            if ((int) $entry->seq !== $expectedSeq) {
                $discrepancies[] = 'Sequence jumps at entry #' . $entry->id
                    . ': expected seq ' . $expectedSeq . ', found ' . $entry->seq . '.';
                $expectedSeq = (int) $entry->seq;
            }

            $running[$entry->bucket] = round($running[$entry->bucket] + $entry->amount_usd, 8);

            if ($entry->flow === Flow::INTERNAL) {
                $internalNet += $entry->amount_usd;
            }

            foreach ([
                Bucket::AVAILABLE => 'balance_available',
                Bucket::PROFIT    => 'balance_profit',
                Bucket::COMMITTED => 'balance_committed',
            ] as $bucket => $column) {
                if (abs($running[$bucket] - (float) $entry->$column) > self::TOLERANCE) {
                    $discrepancies[] = 'Entry #' . $entry->id . ' (' . $entry->reference . ') records '
                        . Bucket::label($bucket) . ' as ' . number_format((float) $entry->$column, 2)
                        . ' but the movements before it give ' . number_format($running[$bucket], 2) . '.';
                }
            }
        }

        if (abs($internalNet) > self::TOLERANCE) {
            $discrepancies[] = 'Internal movements do not net to zero: they sum to '
                . number_format($internalNet, 8) . '. Total position has been changed by a transfer between buckets.';
        }

        return $this->result($discrepancies, 'Running balances follow from the movements, and internal transfers net to zero');
    }

    private function checkTransactions(User $user, $entries): array
    {
        $discrepancies = [];

        $transactions = DB::table('transactions')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->orderBy('id')
            ->get();

        // Anything the mapper still wants to post means the ledger has fallen behind.
        $running = $this->synchroniser->currentPosition($user->id);

        foreach ($transactions as $trx) {
            $entry = $entries->where('transaction_id', (int) $trx->id)->sortByDesc('seq')->first();
            $stage = is_array($entry?->meta) ? ($entry->meta['stage'] ?? null) : null;

            try {
                $pending = $this->mapper->map($trx, $user->id, $running, $stage);
            } catch (\Throwable $e) {
                $discrepancies[] = 'Transaction ' . $trx->trx_id . ': ' . $e->getMessage();
                continue;
            }

            if ($pending !== []) {
                $discrepancies[] = 'Transaction ' . $trx->trx_id . ' (' . $trx->type . ') is not fully recorded: '
                    . count($pending) . ' movement(s) still to post. Run ledger:sync.';
            }
        }

        $knownIds = $transactions->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($entries->whereNotNull('transaction_id') as $entry) {
            if (! in_array((int) $entry->transaction_id, $knownIds, true)) {
                $discrepancies[] = 'Ledger entry ' . $entry->reference
                    . ' cites transaction #' . $entry->transaction_id . ', which does not exist for this investor.';
            }
        }

        return $this->result(
            $discrepancies,
            $transactions->count() . ' transaction(s) all accounted for in the ledger'
        );
    }

    private function checkWallet(User $user, array $position): array
    {
        $currency = Currency::where('default', true)->first();

        $wallet = UserWallet::where('user_id', $user->id)
            ->whereHas('currency', fn ($q) => $q->where('code', $currency?->code))
            ->first();

        if (! $wallet) {
            return $this->result(['No default-currency wallet found for ' . $user->username . '.'], '');
        }

        $discrepancies = [];

        foreach ([
            [Bucket::AVAILABLE, (float) $wallet->balance, 'balance'],
            [Bucket::PROFIT, (float) $wallet->profit_balance, 'profit_balance'],
        ] as [$bucket, $walletValue, $column]) {
            if (abs($position[$bucket] - $walletValue) > self::TOLERANCE) {
                $discrepancies[] = 'Ledger ' . Bucket::label($bucket) . ' is '
                    . number_format($position[$bucket], 2) . ' but user_wallets.' . $column . ' is '
                    . number_format($walletValue, 2) . ' (difference '
                    . number_format($position[$bucket] - $walletValue, 2) . ').';
            }
        }

        return $this->result($discrepancies, 'Ledger agrees with the wallet the application spends from');
    }

    private function checkAllocations(User $user, array $position): array
    {
        $committed = CapitalAllocation::committedFor($user->id);
        $discrepancies = [];

        if (abs($position[Bucket::COMMITTED] - $committed) > self::TOLERANCE) {
            $discrepancies[] = 'Ledger committed capital is ' . number_format($position[Bucket::COMMITTED], 2)
                . ' but open locked allocations total ' . number_format($committed, 2) . '.';
        }

        return $this->result($discrepancies, 'Committed capital matches the open gold deals');
    }

    private function result(array $discrepancies, string $passMessage): array
    {
        return [
            'passed'        => $discrepancies === [],
            'message'       => $discrepancies === [] ? $passMessage : $discrepancies[0],
            'discrepancies' => $discrepancies,
        ];
    }
}
