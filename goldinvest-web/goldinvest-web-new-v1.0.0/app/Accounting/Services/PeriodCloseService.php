<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\BankStatementLine;
use App\Accounting\Models\CashAccount;
use App\Accounting\Security\AccountingPermission;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\TradingExpense;
use App\Investor\Services\SequenceAllocator;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Standing behind a month.
 *
 * Closing a period is the company saying that the figures it contains are the
 * ones it will answer for. So it cannot happen while anything in the period is
 * still moving: a deal whose result is interim, a cost that has been approved
 * but not posted, a bank line nobody has matched, a trial balance that does not
 * balance. Each of those is a way the month's result would change after the
 * company had already stood behind it.
 *
 * What the close records is the company's own result and nothing else. What
 * share of that result becomes an investor's is a separate decision, taken
 * after the close under a rule this phase does not have, and writing a figure
 * for it here would be answering a question nobody has yet answered.
 *
 * Reopening follows the same discipline as every other waived control in this
 * ledger: a Super Admin, a reason, and a record of both on the period.
 */
class PeriodCloseService
{
    private const EPSILON = 0.00000001;

    public function __construct(
        private readonly SequenceAllocator $sequences,
        private readonly TrialBalance $trialBalance,
        private readonly RealizedTradingResult $results,
    ) {
    }

    /**
     * Everything that would stop this period being closed, in plain terms.
     *
     * Empty means it may close. Each entry is a reason, not a code, because the
     * person reading it has to know what to go and fix.
     *
     * @return array<int, string>
     */
    public function blockers(AccountingPeriod $period): array
    {
        $blockers = [];

        if ($period->isClosed()) {
            $blockers[] = 'Period ' . $period->code . ' is already ' . $period->status
                . ($period->close_reference ? ' (' . $period->close_reference . ')' : '') . '.';

            return $blockers;
        }

        // The books have to balance. A period that does not is not a period.
        $tb = $this->trialBalance->build(null, $period->id);

        if (! $tb['balanced']) {
            $blockers[] = 'The trial balance for ' . $period->code . ' does not balance: debits '
                . Money::format($tb['total_debits']) . ' against credits ' . Money::format($tb['total_credits'])
                . ', a difference of ' . Money::exact($tb['difference']) . '.';
        }

        // A deal whose gold has all gone but whose result is still interim would
        // change the month's figure after the close. It has to be recorded first.
        // A deal still holding gold is a different matter: its result belongs to
        // whichever month it finally sells in, and it carries forward.
        $company = $this->results->forCompany(['period_id' => $period->id]);

        foreach ($company['lots'] as $lot) {
            if ($lot['trading_complete'] && ! $lot['is_final']) {
                $blockers[] = 'Deal ' . $lot['lot_code'] . ' is sold out but its result is not final: '
                    . lcfirst((string) ($lot['qualification'] ?? $lot['stage'])) . '.';
            }
        }

        // A cost claimed for the month but not yet in its books would land there
        // after the close, or be lost. Neither is acceptable.
        $pending = TradingExpense::query()
            ->whereBetween('expense_date', [$period->starts_on->toDateString(), $period->ends_on->toDateString()])
            ->whereIn('status', [
                TradingExpense::STATUS_DRAFT, TradingExpense::STATUS_SUBMITTED, TradingExpense::STATUS_APPROVED,
            ])
            ->get();

        foreach ($pending as $expense) {
            $blockers[] = 'Expense ' . $expense->label() . ' (' . Money::format((float) $expense->amount_usd)
                . ') is dated in ' . $period->code . ' but is still ' . $expense->status . '. Post or reject it.';
        }

        // A bank line nobody has explained is money the books do not yet account
        // for. Reconciliation is 3B's; the close only refuses to look past it.
        $unmatched = BankStatementLine::query()
            ->where('status', BankStatementLine::UNMATCHED)
            ->whereBetween('value_date', [$period->starts_on->toDateString(), $period->ends_on->toDateString()])
            ->with('cashAccount')
            ->get();

        foreach ($unmatched as $line) {
            $blockers[] = 'Bank line of ' . Money::format((float) $line->amount) . ' on '
                . $line->value_date->format('d M Y') . ' at ' . ($line->cashAccount?->label() ?? 'a bank account')
                . ' is unmatched. Match or set it aside before closing.';
        }

        return $blockers;
    }

    /**
     * Close the period.
     *
     * Refused while anything blocks it. Recorded with who, when, why and what the
     * books said at that moment, so the close can be checked against the ledger
     * long afterwards rather than taken on trust.
     */
    public function close(AccountingPeriod $period, ?Admin $actor = null, ?string $reason = null): AccountingPeriod
    {
        AccountingPermission::assert($actor, AccountingPermission::PERIOD_CLOSE);

        $reason = trim((string) $reason);

        if ($reason === '') {
            throw new PostingRefused('Closing a period needs a reason or reference, which is recorded against it.');
        }

        $blockers = $this->blockers($period);

        if ($blockers !== []) {
            throw new PostingRefused(
                'Period ' . $period->code . ' cannot be closed:' . PHP_EOL . '  - ' . implode(PHP_EOL . '  - ', $blockers)
            );
        }

        $tb = $this->trialBalance->build(null, $period->id);
        $company = $this->results->forCompany(['period_id' => $period->id]);

        return DB::transaction(function () use ($period, $actor, $reason, $tb, $company) {
            // Work from what the database holds, not from whatever state the
            // caller's instance is in.
            $period->refresh();

            if ($period->isClosed()) {
                throw new PostingRefused('Period ' . $period->code . ' was closed by somebody else meanwhile.');
            }

            $period->forceFill([
                'status'          => AccountingPeriod::CLOSED,
                'closed_by'       => $actor?->id,
                'closed_at'       => Carbon::now(),
                'close_reference' => $this->sequences->next('PCL', Carbon::now()),
                'close_reason'    => $reason,
                'snapshot'        => [
                    'trading_revenue_usd'       => $company['trading_revenue_usd'],
                    'trading_cogs_usd'          => $company['trading_cogs_usd'],
                    'trading_gross_usd'         => $company['trading_gross_usd'],
                    'trading_expenses_usd'      => $company['trading_expenses_usd'],
                    'trading_result_usd'        => $company['trading_result_usd'],
                    'unattributed_expenses_usd' => $company['unattributed_expenses_usd'],
                    'operating_result_usd'      => $company['operating_result_usd'],
                    'deals'                     => array_map(fn ($l) => [
                        'lot_code'         => $l['lot_code'],
                        'net_realized_usd' => $l['net_realized_usd'],
                        'stage'            => $l['stage'],
                        'is_final'         => $l['is_final'],
                    ], $company['lots']),
                    'trial_balance' => [
                        'total_debits'  => $tb['total_debits'],
                        'total_credits' => $tb['total_credits'],
                        'difference'    => $tb['difference'],
                        'journals'      => $tb['journals'],
                    ],
                    'investor_allocation' => 'not determined at close; requires an approved allocation rule',
                ],
            ])->save();

            return $period;
        });
    }

    /**
     * Take a close back.
     *
     * Only a Super Admin, only with a reason, and both are recorded. The
     * snapshot of what was closed is kept: a reopened period's history includes
     * that it was once stood behind, and what the figures were then.
     */
    public function reopen(AccountingPeriod $period, string $reason, ?Admin $actor = null): AccountingPeriod
    {
        AccountingPermission::assert($actor, AccountingPermission::PERIOD_REOPEN);

        if (! $period->isClosed()) {
            throw new PostingRefused('Period ' . $period->code . ' is ' . $period->status . '; there is nothing to reopen.');
        }

        if ($period->status === AccountingPeriod::LOCKED) {
            throw new PostingRefused('Period ' . $period->code . ' is locked and cannot be reopened.');
        }

        if ($actor !== null && ! $actor->isSuperAdmin()) {
            throw new AccountingException('Only a Super Admin may reopen a closed period.');
        }

        if (trim($reason) === '') {
            throw new PostingRefused('Reopening a period needs a reason, which is recorded against it.');
        }

        $period->refresh();

        $period->forceFill([
            'status'        => AccountingPeriod::OPEN,
            'reopened_by'   => $actor?->id,
            'reopened_at'   => Carbon::now(),
            'reopen_reason' => trim($reason),
        ])->save();

        return $period;
    }
}
