<?php

namespace App\Accounting\Services;

use App\Accounting\Ledger\AccountType;
use App\Accounting\Models\AccountingPeriod;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\LotResult;
use App\GoldTrading\Models\TradingExpense;
use App\GoldTrading\Services\LotCostBasis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * What the company actually made, read out of its own books.
 *
 * Every figure here comes from posted journal lines. Revenue is what account
 * 4000 was credited, cost of sales is what 5000 was debited, and deal costs are
 * what the 6000s were debited. Nothing is read from a wallet, a distribution, a
 * precomputed investor return or a second calculation kept alongside the ledger.
 * That is the point of the whole phase: the company's result is what its
 * accounting says it is, and if the accounting is wrong the answer should be
 * wrong in a way somebody can find, rather than quietly right for the wrong
 * reason.
 *
 * Reversals need no special handling. A reversing journal posts the mirror of
 * what it undoes, so summing every line nets it out on its own.
 *
 * Two things this deliberately does not do.
 *
 * It does not call a result final because the gold has been sold. A deal whose
 * last gram is gone still has costs arriving, and a figure that might still move
 * is an interim figure whatever it looks like. `realized_at` on the deal's
 * result is the only thing that makes it final, and recording that is a separate,
 * deliberate act.
 *
 * It does not touch an investor. Gold is a company asset and investor money is a
 * company liability; what portion of this result, if any, becomes an investor's
 * is Phase 3F's question and is not an input to this one.
 */
class RealizedTradingResult
{
    private const EPSILON = 0.00000001;

    public const REVENUE = '4000';
    public const COGS    = '5000';

    /**
     * The expense accounts that belong in a trading result.
     *
     * 6000 to 6899 are the costs of doing business. 6900 is FX, which is a
     * consequence of holding currency rather than of trading gold, and 7000 is
     * the investors' share, which is a distribution of the result and must never
     * be one of its inputs — including it would let the answer depend on itself.
     */
    private const EXPENSE_FROM = '6000';
    private const EXPENSE_TO   = '6899';

    public function __construct(private readonly LotCostBasis $costBasis)
    {
    }

    /**
     * One deal's realized result.
     *
     * @param  array{from?:string,to?:string,period_id?:int}  $scope
     */
    public function forLot(GoldLot $lot, array $scope = []): array
    {
        // Reload rather than reuse. A caller may have been holding this lot since
        // before a cost was posted against it, and a relation loaded then would
        // still describe the world as it was — which is how a result comes to be
        // computed from facts that have since changed.
        $lot->load(['processings', 'sales', 'expenses']);

        $revenue = $this->credited(self::REVENUE, $lot, $scope);
        $cogs = $this->debited(self::COGS, $lot, $scope);
        $expenses = $this->expenseAccounts($lot, $scope);
        $expensesTotal = round(array_sum(array_column($expenses, 'amount_usd')), 8);

        $grossProfit = round($revenue - $cogs, 8);
        $net = round($grossProfit - $expensesTotal, 8);

        $basis = $this->costBasis->forLot($lot);
        $state = $this->state($lot, $basis, $scope);

        return [
            'lot_code'    => $lot->lot_code,
            'gold_lot_id' => $lot->id,

            // Straight out of the ledger.
            'revenue_usd'              => $revenue,
            'cost_of_goods_sold_usd'   => $cogs,
            'gross_profit_usd'         => $grossProfit,
            'ordinary_expenses_usd'    => $expensesTotal,
            'net_realized_usd'         => $net,
            'expenses_by_account'      => $expenses,

            // What the gold cost, from the one place that decides it.
            'capitalised_cost_usd'        => $basis['capitalised_cost_usd'],
            'cost_basis_usd'              => $basis['cost_basis_usd'],
            'refined_grams'               => $basis['refined_grams'],
            'sold_grams'                  => $basis['sold_grams'],
            'remaining_grams'             => $basis['remaining_grams'],
            'remaining_value_at_cost_usd' => $basis['remaining_value_at_cost_usd'],

            // Whether this figure can be relied on, and if not, why not.
            'stage'               => $state['stage'],
            'is_final'            => $state['is_final'],
            'trading_complete'    => $state['trading_complete'],
            'expenses_finalised'  => $state['expenses_finalised'],
            'result_recorded'     => $state['result_recorded'],
            'period_closed'       => $state['period_closed'],
            'unposted_costs_usd'  => $state['unposted_costs_usd'],
            'posted_to_ledger'    => $lot->purchase_journal_id !== null,
            'qualification'       => $state['qualification'],
        ];
    }

    /**
     * The company's realized result across every deal.
     *
     * Deal results are added up, and costs that belong to no deal are shown
     * separately rather than being pushed into one. A deal's result is what that
     * gold earned; the office rent is not part of it, and pretending otherwise
     * would make every deal's number depend on how the overheads were shared out.
     *
     * @param  array{from?:string,to?:string,period_id?:int}  $scope
     */
    public function forCompany(array $scope = []): array
    {
        $lots = GoldLot::query()
            ->whereIn('id', $this->lotIdsWithActivity($scope))
            ->orderBy('purchase_date')
            ->get();

        $results = [];

        foreach ($lots as $lot) {
            $results[] = $this->forLot($lot, $scope);
        }

        $tradingRevenue = round(array_sum(array_column($results, 'revenue_usd')), 8);
        $tradingCogs = round(array_sum(array_column($results, 'cost_of_goods_sold_usd')), 8);
        $tradingExpenses = round(array_sum(array_column($results, 'ordinary_expenses_usd')), 8);
        $tradingResult = round(array_sum(array_column($results, 'net_realized_usd')), 8);

        // The same accounts read without a lot filter. Anything here that the
        // deals above did not account for belongs to no deal.
        $ledgerRevenue = $this->credited(self::REVENUE, null, $scope);
        $ledgerCogs = $this->debited(self::COGS, null, $scope);
        $ledgerExpenses = round(array_sum(array_column($this->expenseAccounts(null, $scope), 'amount_usd')), 8);

        $unattributedExpenses = round($ledgerExpenses - $tradingExpenses, 8);
        $unattributedRevenue = round($ledgerRevenue - $tradingRevenue, 8);

        $interim = array_values(array_filter($results, fn ($r) => ! $r['is_final']));

        return [
            'scope'  => $this->describeScope($scope),
            'lots'   => $results,

            'trading_revenue_usd'   => $tradingRevenue,
            'trading_cogs_usd'      => $tradingCogs,
            'trading_gross_usd'     => round($tradingRevenue - $tradingCogs, 8),
            'trading_expenses_usd'  => $tradingExpenses,
            'trading_result_usd'    => $tradingResult,

            'unattributed_expenses_usd' => $unattributedExpenses,
            'operating_result_usd'      => round($tradingResult - $unattributedExpenses, 8),

            // Proof that adding the deals up did not lose or duplicate anything.
            'ledger_revenue_usd'  => $ledgerRevenue,
            'ledger_cogs_usd'     => $ledgerCogs,
            'ledger_expenses_usd' => $ledgerExpenses,
            'revenue_unattributed_usd' => $unattributedRevenue,
            'reconciles' => abs($unattributedRevenue) <= self::EPSILON
                && abs($ledgerCogs - $tradingCogs) <= self::EPSILON,

            // How much of this is still allowed to move.
            'interim_lots'      => array_column($interim, 'lot_code'),
            'interim_value_usd' => round(array_sum(array_column($interim, 'net_realized_usd')), 8),
            'is_final'          => $interim === [],
        ];
    }

    /**
     * Whether a deal's result can be relied on yet, and what is holding it up.
     *
     * The four states the business actually has are kept apart here: the gold is
     * all sold, the costs are all in, somebody has stood behind the figures, and
     * the period has been closed. They happen in that order and they are not the
     * same event.
     */
    private function state(GoldLot $lot, array $basis, array $scope): array
    {
        $tradingComplete = $basis['sold_grams'] > 0 && round($basis['remaining_grams'], 4) <= 0;
        $expensesFinalised = $lot->expensesFinalised();

        // Costs that exist against this deal but have not reached the ledger.
        // Until they do, the result above simply does not know about them.
        $unposted = round((float) $lot->expenses
            ->filter(fn (TradingExpense $e) => $e->countsAsDealExpense() && $e->journal_id === null)
            ->sum('amount_usd'), 8);

        $record = LotResult::where('gold_lot_id', $lot->id)->first();
        $recorded = $record !== null && $record->realized_at !== null;

        $period = $this->periodFor($lot);
        $periodClosed = $period !== null && $period->isClosed();

        $stage = match (true) {
            $lot->purchase_journal_id === null && $record?->status === LotResult::STATUS_DISTRIBUTED
                => 'historical',
            $lot->purchase_journal_id === null  => 'not in the ledger',
            ! $tradingComplete                  => 'trading in progress',
            ! $expensesFinalised || $unposted > self::EPSILON
                => 'trading complete, expenses pending',
            ! $recorded                         => 'ready to record',
            $periodClosed                       => 'realized, period closed',
            default                             => 'realized',
        };

        $qualification = match ($stage) {
            'historical' => 'Closed and distributed before the general ledger existed. '
                . 'Its figures are attribution records, not accounting, until the historical backfill.',
            'not in the ledger' => 'This deal has never been posted, so the ledger holds nothing for it.',
            'trading in progress' => 'Gold from this deal is still held. The result covers only what has been sold.',
            'trading complete, expenses pending' => $unposted > self::EPSILON
                ? 'Interim: ' . number_format($unposted, 2) . ' of recorded costs have not reached the ledger yet.'
                : 'Interim: the costs of this deal have not been declared complete, so the result may still fall.',
            'ready to record' => 'The figures are complete but nobody has recorded them yet.',
            default => null,
        };

        return [
            'stage'              => $stage,
            'trading_complete'   => $tradingComplete,
            'expenses_finalised' => $expensesFinalised,
            'result_recorded'    => $recorded,
            'period_closed'      => $periodClosed,
            'unposted_costs_usd' => $unposted,

            // Final means nothing further can move it: the gold is gone, the costs
            // are in and declared complete, and somebody has recorded the figures.
            'is_final'      => $tradingComplete && $expensesFinalised
                && $unposted <= self::EPSILON && $recorded,
            'qualification' => $qualification,
        ];
    }

    /** The accounting period a deal's result belongs to: the one its last sale fell in. */
    public function periodFor(GoldLot $lot): ?AccountingPeriod
    {
        $lastSale = $lot->sales()
            ->where('status', 'settled')
            ->orderByDesc('sale_date')
            ->value('sale_date');

        if (! $lastSale) {
            return null;
        }

        return AccountingPeriod::covering(Carbon::parse($lastSale));
    }

    /** What each expense account carries for this deal, so a total can be checked. */
    private function expenseAccounts(?GoldLot $lot, array $scope): array
    {
        $rows = $this->lines($scope)
            // Every account code is four digits, so a string range is the same
            // range as a numeric one and needs no cast that each database spells
            // differently.
            ->whereBetween('accounts.code', [self::EXPENSE_FROM, self::EXPENSE_TO])
            ->where('accounts.type', AccountType::EXPENSE)
            ->when($lot, fn ($q) => $q->where('journal_lines.gold_lot_id', $lot->id))
            ->groupBy('accounts.code', 'accounts.name')
            ->select('accounts.code', 'accounts.name',
                DB::raw('SUM(journal_lines.debit) as d'), DB::raw('SUM(journal_lines.credit) as c'))
            ->orderBy('accounts.code')
            ->get();

        $accounts = [];

        foreach ($rows as $row) {
            $amount = round((float) $row->d - (float) $row->c, 8);

            if (abs($amount) <= self::EPSILON) {
                continue;
            }

            $accounts[] = ['code' => $row->code, 'name' => $row->name, 'amount_usd' => $amount];
        }

        return $accounts;
    }

    /** Deals with anything at all in the ledger inside this scope. */
    private function lotIdsWithActivity(array $scope): array
    {
        return $this->lines($scope)
            ->whereNotNull('journal_lines.gold_lot_id')
            ->distinct()
            ->pluck('journal_lines.gold_lot_id')
            ->all();
    }

    private function credited(string $code, ?GoldLot $lot, array $scope): float
    {
        $sums = $this->forAccount($code, $lot, $scope);

        return round((float) $sums->c - (float) $sums->d, 8);
    }

    private function debited(string $code, ?GoldLot $lot, array $scope): float
    {
        $sums = $this->forAccount($code, $lot, $scope);

        return round((float) $sums->d - (float) $sums->c, 8);
    }

    private function forAccount(string $code, ?GoldLot $lot, array $scope): object
    {
        return $this->lines($scope)
            ->where('accounts.code', $code)
            ->when($lot, fn ($q) => $q->where('journal_lines.gold_lot_id', $lot->id))
            ->selectRaw('SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c')
            ->first() ?? (object) ['d' => 0, 'c' => 0];
    }

    /** Every posted journal line, narrowed to the period or dates asked for. */
    private function lines(array $scope): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id');

        if (! empty($scope['period_id'])) {
            $query->where('journals.accounting_period_id', $scope['period_id']);
        }

        if (! empty($scope['from'])) {
            $query->whereDate('journals.journal_date', '>=', Carbon::parse($scope['from'])->toDateString());
        }

        if (! empty($scope['to'])) {
            $query->whereDate('journals.journal_date', '<=', Carbon::parse($scope['to'])->toDateString());
        }

        return $query;
    }

    private function describeScope(array $scope): string
    {
        if (! empty($scope['period_id'])) {
            return AccountingPeriod::find($scope['period_id'])?->label() ?? 'one period';
        }

        if (! empty($scope['from']) || ! empty($scope['to'])) {
            return ($scope['from'] ?? 'the beginning') . ' to ' . ($scope['to'] ?? 'today');
        }

        return 'all periods';
    }
}
