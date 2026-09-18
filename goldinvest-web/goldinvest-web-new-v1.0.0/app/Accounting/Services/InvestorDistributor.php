<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\InvestorDistribution;
use App\Accounting\Models\InvestorDistributionLine;
use App\Accounting\Models\Journal;
use App\Accounting\Security\AccountingPermission;
use App\GoldTrading\Models\ProfitDistribution;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\LedgerEvent;
use App\Investor\Services\LedgerRecorder;
use App\Investor\Services\SequenceAllocator;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Turning a period's approved allocation into money the company owes and
 * credits the investors hold.
 *
 * Three things happen, in one transaction, or none of them do:
 *
 *   the company's books    Dr 7000 Investor Profit Share / Cr 2010 Investor Profit Payable
 *   the investor ledger    one profit credit per investor, through LedgerRecorder
 *   the wallet             profit_balance up by the same amount, through the same
 *                          transactions row every other credit goes through
 *
 * The credit enters by the door every credit enters by: a transactions row of
 * the type the ledger already maps, so that ledger:check counts it as accounted
 * for and ledger:sync, seeing it already posted, leaves it alone. There is no
 * second balance anywhere in this.
 *
 * The allocation is not recomputed here. It is read from InvestorAllocation,
 * which reads immutable recorded results, and the figures used are written into
 * the distribution's snapshot so that the distribution can be checked later
 * against what it was made from rather than against whatever the deals say now.
 *
 * The appropriation journal is dated when the distribution is declared, which
 * is after the period it appropriates has closed. A closed period accepts no
 * postings, and this does not make an exception for itself: the realized
 * result belongs to the closed month, the decision to appropriate it belongs
 * to the day it was taken. The distribution records both.
 */
class InvestorDistributor
{
    private const EPSILON = 0.00000001;

    public function __construct(
        private readonly InvestorAllocation $allocation,
        private readonly JournalPoster $journals,
        private readonly LedgerRecorder $ledger,
        private readonly SequenceAllocator $sequences,
    ) {
    }

    /**
     * Distribute a closed period's allocation.
     *
     * Asking twice distributes once: the distribution the period already has is
     * handed back, and nothing is posted, credited or written a second time.
     *
     * @param  array{date?:string, note?:string}  $context
     */
    public function distribute(AccountingPeriod $period, ?Admin $actor = null, array $context = []): InvestorDistribution
    {
        AccountingPermission::assert($actor, AccountingPermission::DISTRIBUTION_POST);

        $existing = $this->current($period);

        if ($existing) {
            return $existing;
        }

        // Every prerequisite, or the reasons. An open period, an interim result,
        // missing terms, missing capital, a negative pool: all refused here, by
        // name, before anything is written.
        $allocation = $this->allocation->assertDistributable($period);

        if (abs((float) $allocation['reserve_percent']) > self::EPSILON) {
            throw new PostingRefused(
                'A reserve of ' . $allocation['reserve_percent'] . '% is configured, but how a reserve is taken '
                . 'from the investors has not been decided. Distribution refused rather than guessed.'
            );
        }

        $lines = $this->linesFrom($allocation);
        $pool = round(array_sum(array_column($lines, 'amount')), 8);

        // The pool is the sum of the lines, by construction. If the allocation's
        // own figure disagrees, something upstream is rounding differently and
        // the distribution must not paper over it.
        if (abs($pool - (float) $allocation['investor_pool_usd']) > self::EPSILON) {
            throw new PostingRefused(
                'The investor lines sum to ' . Money::exact($pool) . ' but the allocation pool is '
                . Money::exact((float) $allocation['investor_pool_usd']) . '. Refused: the two must be identical.'
            );
        }

        $date = isset($context['date']) ? Carbon::parse($context['date']) : Carbon::now();
        $currency = Currency::where('default', true)->first();

        if (! $currency) {
            throw new PostingRefused('No default currency is configured.');
        }

        return DB::transaction(function () use ($period, $allocation, $lines, $pool, $date, $currency, $actor, $context) {
            // Somebody else may have got here first. The unique key would refuse
            // the insert anyway; this refuses it with a sentence.
            $period->refresh();

            if ($again = $this->current($period)) {
                return $again;
            }

            $reference = $this->reference($period);

            $journal = $this->journals->post([
                [
                    'account' => '7000',
                    'debit'   => $pool,
                    'memo'    => 'Investor share of the realized result of ' . $period->code,
                ],
                [
                    'account' => '2010',
                    'credit'  => $pool,
                    'memo'    => 'owed to ' . count($lines) . ' investor(s) under ' . $reference,
                ],
            ], [
                'date'        => $date->toDateString(),
                'memo'        => 'Investor distribution ' . $reference . ' for ' . $period->code,
                'source_type' => 'investor_distribution',
                'meta'        => ['reference' => $reference, 'period' => $period->code],
            ], $actor);

            $distribution = InvestorDistribution::create([
                'reference'            => $reference,
                'accounting_period_id' => $period->id,
                'journal_id'           => $journal->id,
                'company_result_usd'   => $allocation['company_trading_result_usd'],
                'gross_pool_usd'       => $allocation['gross_investor_pool_usd'],
                'reserve_usd'          => $allocation['reserve_usd'],
                'pool_usd'             => $pool,
                'investors_count'      => count($lines),
                'status'               => InvestorDistribution::POSTED,
                'active_seq'           => 0,
                'distributed_by'       => $actor?->id,
                'distributed_at'       => $date,
                'snapshot'             => [
                    'reference'      => $reference,
                    'period'         => $period->code,
                    'period_closed'  => $period->close_reference,
                    'distributed_at' => $date->toDateTimeString(),
                    'distributed_by' => $actor?->id,
                    'journal'        => $journal->reference,
                    'note'           => $context['note'] ?? null,
                    'allocation'     => $allocation,
                ],
            ]);

            foreach ($lines as $line) {
                $this->credit($distribution, $line, $period, $reference, $currency, $date, $actor);
            }

            return $distribution->fresh(['lines']);
        });
    }

    /**
     * Take a distribution back.
     *
     * The company's journal is reversed by the ledger's own rule. Each investor's
     * credit is answered by a credit of the opposite sign, linked to the entry it
     * undoes, through the same door it came in by. If an investor has already
     * moved the money on, the ledger refuses to take their profit below zero and
     * the whole reversal rolls back: a reversal that half-happens is worse than
     * one that does not.
     */
    public function reverse(InvestorDistribution $distribution, string $reason, ?Admin $actor = null): InvestorDistribution
    {
        AccountingPermission::assert($actor, AccountingPermission::DISTRIBUTION_POST);
        AccountingPermission::assert($actor, AccountingPermission::JOURNAL_REVERSE);

        if (trim($reason) === '') {
            throw new PostingRefused('Reversing a distribution needs a reason.');
        }

        $distribution->refresh();

        if ($distribution->status === InvestorDistribution::REVERSED) {
            throw new PostingRefused('Distribution ' . $distribution->reference . ' has already been reversed.');
        }

        $currency = Currency::where('default', true)->first();

        return DB::transaction(function () use ($distribution, $reason, $actor, $currency) {
            $reversal = $this->journals->reverse($distribution->journal, $reason, $actor);

            foreach ($distribution->lines as $line) {
                $this->uncredit($distribution, $line, $currency, $reason);
            }

            $distribution->forceFill([
                'status'              => InvestorDistribution::REVERSED,
                'active_seq'          => $distribution->id,
                'reversal_journal_id' => $reversal->id,
                'reversed_by'         => $actor?->id,
                'reversed_at'         => Carbon::now(),
                'reversal_reason'     => trim($reason),
            ])->save();

            return $distribution->fresh(['lines']);
        });
    }

    /** The posted distribution a period has, if it has one. */
    public function current(AccountingPeriod $period): ?InvestorDistribution
    {
        return InvestorDistribution::where('accounting_period_id', $period->id)
            ->where('active_seq', 0)
            ->with('lines')
            ->first();
    }

    /**
     * One investor's credit, through the door every credit uses.
     *
     * A transactions row of the type the ledger already maps, the wallet moved by
     * exactly that amount, and the ledger entry posted here and now with the
     * stage the synchroniser looks for - so that ledger:sync, coming along later,
     * finds the transaction already recorded and leaves it alone.
     */
    private function credit(
        InvestorDistribution $distribution,
        array $line,
        AccountingPeriod $period,
        string $reference,
        Currency $currency,
        Carbon $date,
        ?Admin $actor,
    ): void {
        $user = User::findOrFail($line['user_id']);
        $wallet = $this->wallet($user, $currency);
        $amount = round($line['amount'], 8);

        $trxId = generate_unique_string('transactions', 'trx_id', 16);

        // The wallet moves under a guard on its own row, so a concurrent change
        // cannot be silently overwritten.
        $affected = DB::table('user_wallets')->where('id', $wallet->id)
            ->update(['profit_balance' => DB::raw('profit_balance + ' . $amount), 'updated_at' => now()]);

        if ($affected !== 1) {
            throw new PostingRefused('The wallet of ' . $user->username . ' could not be credited.');
        }

        $newProfit = round((float) $wallet->profit_balance + $amount, 8);

        $transactionId = DB::table('transactions')->insertGetId([
            'type'              => ProfitDistribution::TRX_TYPE,
            'trx_id'            => $trxId,
            'user_type'         => 'USER',
            'user_id'           => $user->id,
            'wallet_id'         => $wallet->id,
            'request_amount'    => $amount,
            'request_currency'  => $currency->code,
            'exchange_rate'     => 1,
            'percent_charge'    => 0,
            'fixed_charge'      => 0,
            'total_charge'      => 0,
            'total_payable'     => $amount,
            'receive_amount'    => $amount,
            'receiver_type'     => 'USER',
            'receiver_id'       => $user->id,
            'available_balance' => $newProfit,
            'payment_currency'  => $currency->code,
            'remark'            => 'Trading profit for ' . $period->code . ' (' . $reference . ')',
            'details'           => json_encode([
                'distribution_reference' => $reference,
                'period'                 => $period->code,
                'net_profit_usd'         => $amount,
                'deals'                  => $line['deals'],
            ]),
            'status'     => 1,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $entries = $this->ledger->post($user->id, LedgerEvent::PROFIT_CREDITED, [
            ['bucket' => Bucket::PROFIT, 'amount' => $amount],
        ], [
            'occurred_at'    => $date,
            'trx_id'         => $trxId,
            'transaction_id' => $transactionId,
            'description'    => 'Trading profit for ' . $period->code,
            'meta'           => [
                'stage'                  => 'posted',
                'source_transaction'     => $trxId,
                'distribution_reference' => $reference,
                'period'                 => $period->code,
            ],
        ]);

        $entry = $entries[0];

        InvestorDistributionLine::create([
            'investor_distribution_id' => $distribution->id,
            'user_id'                  => $user->id,
            'amount_usd'               => $amount,
            'trx_id'                   => $trxId,
            'transaction_id'           => $transactionId,
            'ledger_entry_id'          => $entry->id,
            'ledger_reference'         => $entry->reference,
            'snapshot'                 => $line,
        ]);
    }

    /** The mirror of credit(): the same door, the opposite sign, linked to what it undoes. */
    private function uncredit(InvestorDistribution $distribution, InvestorDistributionLine $line, Currency $currency, string $reason): void
    {
        $user = User::findOrFail($line->user_id);
        $wallet = $this->wallet($user, $currency);
        $amount = round((float) $line->amount_usd, 8);

        if ((float) $wallet->profit_balance + self::EPSILON < $amount) {
            throw new PostingRefused(
                $user->username . ' holds ' . Money::format((float) $wallet->profit_balance) . ' of profit, less than the '
                . Money::format($amount) . ' this distribution credited. It has been moved on and cannot be taken back.'
            );
        }

        $trxId = generate_unique_string('transactions', 'trx_id', 16);

        $affected = DB::table('user_wallets')->where('id', $wallet->id)
            ->where('profit_balance', '>=', $amount)
            ->update(['profit_balance' => DB::raw('profit_balance - ' . $amount), 'updated_at' => now()]);

        if ($affected !== 1) {
            throw new PostingRefused('The wallet of ' . $user->username . ' changed while the reversal was being written.');
        }

        $transactionId = DB::table('transactions')->insertGetId([
            'type'              => ProfitDistribution::TRX_TYPE,
            'trx_id'            => $trxId,
            'user_type'         => 'USER',
            'user_id'           => $user->id,
            'wallet_id'         => $wallet->id,
            'request_amount'    => -$amount,
            'request_currency'  => $currency->code,
            'exchange_rate'     => 1,
            'percent_charge'    => 0,
            'fixed_charge'      => 0,
            'total_charge'      => 0,
            'total_payable'     => -$amount,
            'receive_amount'    => -$amount,
            'receiver_type'     => 'USER',
            'receiver_id'       => $user->id,
            'available_balance' => round((float) $wallet->profit_balance - $amount, 8),
            'payment_currency'  => $currency->code,
            'remark'            => 'Reversal of ' . $distribution->reference . ': ' . $reason,
            'details'           => json_encode([
                'distribution_reference' => $distribution->reference,
                'reverses_trx_id'        => $line->trx_id,
                'reason'                 => $reason,
            ]),
            'status'     => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $entries = $this->ledger->post($user->id, LedgerEvent::PROFIT_CREDITED, [
            ['bucket' => Bucket::PROFIT, 'amount' => -$amount],
        ], [
            'trx_id'            => $trxId,
            'transaction_id'    => $transactionId,
            'reverses_entry_id' => $line->ledger_entry_id,
            'description'       => 'Reversal of ' . $distribution->reference,
            'meta'              => [
                'stage'                  => 'posted',
                'source_transaction'     => $trxId,
                'distribution_reference' => $distribution->reference,
                'reversal'               => true,
            ],
        ]);

        $line->forceFill([
            'reversal_transaction_id'  => $transactionId,
            'reversal_ledger_entry_id' => $entries[0]->id,
        ])->save();
    }

    /**
     * Per investor, across every eligible deal, from the allocation as given.
     *
     * @return array<int, array{user_id:int, amount:float, deals:array}>
     */
    private function linesFrom(array $allocation): array
    {
        $byInvestor = [];

        foreach ($allocation['eligible'] as $deal) {
            foreach ($deal['investors'] as $i) {
                $uid = (int) $i['user_id'];
                $byInvestor[$uid]['user_id'] = $uid;
                $byInvestor[$uid]['amount'] = round(($byInvestor[$uid]['amount'] ?? 0) + (float) $i['allocation_usd'], 8);
                $byInvestor[$uid]['deals'][] = [
                    'lot_code'          => $deal['lot_code'],
                    'realized_usd'      => $deal['realized_usd'],
                    'capital_usd'       => $i['capital_usd'],
                    'capital_share_pct' => $i['capital_share_pct'],
                    'profit_share_pct'  => $i['profit_share_pct'],
                    'allocation_usd'    => $i['allocation_usd'],
                ];
            }
        }

        ksort($byInvestor);

        // An investor whose share rounds to nothing is not credited nothing.
        return array_values(array_filter($byInvestor, fn ($l) => $l['amount'] > self::EPSILON));
    }

    /** DIST-<period>-NNNNNN, numbered gaplessly per period end. */
    private function reference(AccountingPeriod $period): string
    {
        $allocated = $this->sequences->next('DIST', $period->ends_on);

        return 'DIST-' . $period->code . '-' . substr($allocated, -6);
    }

    private function wallet(User $user, Currency $currency): UserWallet
    {
        $wallet = UserWallet::where('user_id', $user->id)
            ->whereHas('currency', fn ($q) => $q->where('code', $currency->code))
            ->first();

        if (! $wallet) {
            throw new PostingRefused('No ' . $currency->code . ' wallet for ' . $user->username . '.');
        }

        return $wallet;
    }
}
