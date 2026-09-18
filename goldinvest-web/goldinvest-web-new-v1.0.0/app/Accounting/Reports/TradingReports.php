<?php

namespace App\Accounting\Reports;

use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\BankStatementLine;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\InvestorDistribution;
use App\Accounting\Services\InventoryValuation;
use App\Accounting\Services\InvestorAllocation;
use App\Accounting\Services\PeriodCloseService;
use App\Accounting\Services\RealizedTradingResult;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\LotResult;
use App\GoldTrading\Models\TradingExpense;
use App\Investor\Models\LedgerEntry;
use App\Investor\Services\LedgerReconciler;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Gold trading performance, the period close report and the dashboard.
 *
 * The hierarchy is fixed: the posted general ledger is the financial
 * authority; gold_lot_results is the recorded deal-result artifact, kept and
 * shown as a control against it. For a recorded deal both are shown side by
 * side, and where they differ the report raises a CONTROL EXCEPTION. It never
 * corrects either.
 */
class TradingReports
{
    public function __construct(
        private readonly LedgerFigures $ledger,
        private readonly RealizedTradingResult $results,
        private readonly InventoryValuation $valuation,
        private readonly PeriodCloseService $close,
        private readonly InvestorAllocation $allocation,
        private readonly FinancialStatements $statements,
        private readonly LedgerReconciler $reconciler,
    ) {
    }

    // ------------------------------------------------------------ gold trading

    public function goldTrading(ReportScope $scope): array
    {
        $query = GoldLot::with(['allocations', 'result'])->orderBy('purchase_date')->orderBy('id');

        if ($scope->lot) {
            $query->where('lot_code', $scope->lot);
        }

        $current = [];
        $historical = [];
        $controls = [];

        foreach ($query->get() as $lot) {
            $record = $lot->result;
            $isHistorical = $lot->purchase_journal_id === null && $record?->status === LotResult::STATUS_DISTRIBUTED;

            if ($isHistorical) {
                $historical[] = [
                    'lot_code'   => $lot->lot_code,
                    'purchase_date' => $lot->purchase_date->toDateString(),
                    'label'      => RealizedTradingResult::HISTORICAL_ATTRIBUTION,
                    'recorded'   => [
                        'net_profit_usd'      => (float) $record->net_profit_usd,
                        'investor_profit_usd' => (float) $record->investor_profit_usd,
                        'company_profit_usd'  => (float) $record->company_profit_usd,
                        'closed_at'           => $record->closed_at?->toDateString(),
                    ],
                    'gl'         => null,
                    'note'       => 'Excluded from every accounting total. No revenue, cost of sales or realized result exists in the ledger for this deal until the historical backfill (3H). D2/D3 open.',
                ];
                continue;
            }

            $v = $this->valuation->forLot($lot);
            $r = $this->results->forLot($lot);
            $d = $this->valuation->divergence($lot);

            $glInventory = round($v['gl_unrefined_usd'] + $v['gl_refined_usd'], 8);
            $glCogs = $this->ledger->lotBalance('5000', $lot->id);

            $lotControls = [
                Control::make('inv_' . $lot->lot_code, 'Inventory in the ledger equals the cost basis of what remains',
                    'GL 1100+1110', $glInventory, 'LotCostBasis remaining', $v['remaining_value_usd']),
                Control::make('cogs_' . $lot->lot_code, 'Cost of sales in the ledger equals the cost basis of what was sold',
                    'GL 5000', $glCogs, 'LotCostBasis COGS', $v['cogs_to_date_usd']),
            ];

            $recorded = null;

            if ($record && $record->realized_at !== null) {
                $recorded = [
                    'result_reference' => 'LotResult#' . $record->id,
                    'realized_at'      => $record->realized_at->toDateTimeString(),
                    'period'           => $record->period?->code,
                    'revenue_usd'      => (float) $record->revenue_usd,
                    'cogs_usd'         => (float) $record->cost_of_goods_sold_usd,
                    'expenses_usd'     => (float) $record->expenses_usd,
                    'net_profit_usd'   => (float) $record->net_profit_usd,
                ];

                // The recorded artifact against the GL-derived figure. A difference
                // is an exception to be investigated, never a correction to be made.
                $lotControls[] = Control::make('rec_' . $lot->lot_code, 'Recorded deal result equals the GL-derived result',
                    'gold_lot_results', (float) $record->net_profit_usd, 'GL 4000-5000-(6000-6899)', $r['net_realized_usd'],
                    false, 'The recorded artifact and the ledger disagree. Investigate; do not adjust either from a report.');
                $lotControls[] = Control::make('recrev_' . $lot->lot_code, 'Recorded revenue equals GL 4000 for the deal',
                    'gold_lot_results', (float) $record->revenue_usd, 'GL 4000', $r['revenue_usd']);
            }

            $controls = array_merge($controls, $lotControls);

            $current[] = [
                'lot_code'      => $lot->lot_code,
                'purchase_date' => $lot->purchase_date->toDateString(),
                'status'        => $lot->status,
                'physical' => [
                    'gross_grams' => $v['gross_grams'], 'waste_grams' => $v['waste_grams'],
                    'waste_pct'   => $v['gross_grams'] > 0 ? round($v['waste_grams'] / $v['gross_grams'] * 100, 4) : 0.0,
                    'refined_grams' => $v['refined_grams'], 'sold_grams' => $v['sold_grams'], 'remaining_grams' => $v['remaining_grams'],
                ],
                'cost' => [
                    'purchase_usd'       => $v['purchase_cost_usd'],
                    'capitalised_usd'    => $v['capitalised_cost_usd'],
                    'basis_usd'          => $v['cost_basis_usd'],
                    'per_refined_gram'   => $v['cost_per_refined_gram'],
                    'cogs_usd'           => $v['cogs_to_date_usd'],
                    'inventory_usd'      => $v['remaining_value_usd'],
                    'gl_inventory_usd'   => $glInventory,
                    'gl_cogs_usd'        => $glCogs,
                ],
                'result' => [
                    'revenue_usd'    => $r['revenue_usd'],
                    'cogs_usd'       => $r['cost_of_goods_sold_usd'],
                    'expenses'       => $r['expenses_by_account'],
                    'expenses_usd'   => $r['ordinary_expenses_usd'],
                    'net_usd'        => $r['net_realized_usd'],
                    'stage'          => $r['stage'],
                    'is_final'       => $r['is_final'],
                    'qualification'  => $r['qualification'],
                    'unposted_costs_usd' => $r['unposted_costs_usd'],
                ],
                'recorded' => $recorded,
                'terms' => [
                    'investor_share_pct' => $lot->investor_share_percent,
                    'expense_policy'     => $lot->expense_policy,
                    'capital_usd'        => round((float) $lot->allocations->sum('amount_usd'), 8),
                    'investors'          => $lot->allocations->count(),
                ],
                'divergence' => $d,
                'controls'   => $lotControls,
            ];
        }

        return [
            'report'     => 'gold-trading',
            'scope'      => $scope->toArray(),
            'lots'       => $current,
            'historical' => $historical,
            'historical_label' => strtoupper(RealizedTradingResult::HISTORICAL_ATTRIBUTION),
            'totals' => [
                'inventory_usd' => round(array_sum(array_map(fn ($l) => $l['cost']['inventory_usd'], $current)), 8),
                'remaining_grams' => round(array_sum(array_map(fn ($l) => $l['physical']['remaining_grams'], $current)), 4),
                'realized_usd'  => round(array_sum(array_map(fn ($l) => $l['result']['net_usd'], $current)), 8),
            ],
            'empty'    => $current === [] && $historical === [],
            'controls' => $controls,
        ];
    }

    // ------------------------------------------------------------ period close

    public function periodClose(AccountingPeriod $period): array
    {
        $scope = ReportScope::forPeriod($period);
        $company = $this->results->forCompany(['period_id' => $period->id]);
        $blockers = $period->isClosed() ? [] : $this->close->blockers($period);

        $snapshot = $period->snapshot;
        $snapshotChecks = [];

        if ($snapshot !== null) {
            foreach (['trading_revenue_usd', 'trading_cogs_usd', 'trading_expenses_usd', 'trading_result_usd',
                'unattributed_expenses_usd', 'operating_result_usd'] as $key) {
                $snapshotChecks[] = Control::make('snap_' . $key, 'Close snapshot ' . $key . ' equals the ledger now',
                    'Snapshot', (float) ($snapshot[$key] ?? 0), 'Ledger now', (float) $company[$key],
                    false, 'The ledger has changed since the close. A closed period that moved is a control exception.');
            }
        }

        $allocation = $this->allocation->forPeriod($period);
        $distributions = InvestorDistribution::with(['journal', 'lines'])
            ->where('accounting_period_id', $period->id)->orderBy('id')->get()
            ->map(fn ($d) => [
                'reference'  => $d->reference, 'status' => $d->status, 'pool_usd' => (float) $d->pool_usd,
                'journal'    => $d->journal?->reference, 'journal_date' => $d->journal?->journal_date?->toDateString(),
                'declared_in' => $d->journal ? AccountingPeriod::find($d->journal->accounting_period_id)?->code : null,
                'investors'  => $d->investors_count,
                'reversed_at' => $d->reversed_at?->toDateTimeString(), 'reversal_reason' => $d->reversal_reason,
                'lines'      => $d->lines->map(fn ($l) => [
                    'user'          => User::find($l->user_id)?->username ?? ('user #' . $l->user_id),
                    'amount_usd'    => (float) $l->amount_usd, 'ledger_reference' => $l->ledger_reference, 'trx_id' => $l->trx_id,
                ])->all(),
            ])->all();

        $pending = TradingExpense::query()
            ->whereBetween('expense_date', [$period->starts_on->toDateString(), $period->ends_on->toDateString()])
            ->whereIn('status', [TradingExpense::STATUS_DRAFT, TradingExpense::STATUS_SUBMITTED, TradingExpense::STATUS_APPROVED])
            ->get()->map(fn ($e) => ['reference' => $e->reference, 'status' => $e->status, 'amount_usd' => (float) $e->amount_usd,
                'description' => $e->description])->all();

        $unmatched = BankStatementLine::where('status', BankStatementLine::UNMATCHED)
            ->whereBetween('value_date', [$period->starts_on->toDateString(), $period->ends_on->toDateString()])
            ->get()->map(fn ($l) => ['date' => $l->value_date->toDateString(), 'amount' => (float) $l->amount, 'description' => $l->description])->all();

        $tb = $this->statements->trialBalance($scope);

        $controls = array_merge($tb['controls'], $snapshotChecks);

        return [
            'report'   => 'period-close',
            'scope'    => $scope->toArray(),
            'period'   => [
                'code' => $period->code, 'label' => $period->label(), 'status' => $period->status,
                'close_reference' => $period->close_reference, 'close_reason' => $period->close_reason,
                'closed_by' => $period->closedBy?->username, 'closed_at' => $period->closed_at?->toDateTimeString(),
                'reopened_at' => $period->reopened_at?->toDateTimeString(), 'reopen_reason' => $period->reopen_reason,
                'is_closed' => $period->isClosed(),
            ],
            'blockers'  => $blockers,
            'result'    => $company,
            'snapshot'  => $snapshot,
            'snapshot_checks' => $snapshotChecks,
            'allocation' => $allocation,
            'distributions' => $distributions,
            'payable_2010' => [
                'at_period_end' => $this->ledger->balanceAt('2010', $period->ends_on->toDateString()),
                'now'           => $this->ledger->balanceAt('2010'),
                'note'          => 'G1 open: payments to investors are not yet bridged to the GL, so 2010 does not fall when profit is paid.',
            ],
            'pending_expenses' => $pending,
            'unmatched_bank_lines' => $unmatched,
            'trial_balance' => $tb['totals'] + ['balanced' => $tb['balanced']],
            'journals'  => $tb['journals'],
            'empty'     => $tb['empty'],
            'controls'  => $controls,
        ];
    }

    // --------------------------------------------------------------- dashboard

    public function dashboard(): array
    {
        $today = Carbon::now()->toDateString();
        $open = AccountingPeriod::covering($today) ?? AccountingPeriod::where('status', AccountingPeriod::OPEN)->orderByDesc('code')->first();
        $lastClosed = AccountingPeriod::whereIn('status', [AccountingPeriod::CLOSED, AccountingPeriod::LOCKED])->orderByDesc('code')->first();

        $gold = $this->goldTrading(new ReportScope());
        $openResult = $open ? $this->results->forCompany(['period_id' => $open->id]) : null;

        $expensesOutstanding = TradingExpense::whereIn('status', [TradingExpense::STATUS_DRAFT, TradingExpense::STATUS_SUBMITTED, TradingExpense::STATUS_APPROVED]);

        $banks = CashAccount::where('active', true)->orderBy('code')->get()->map(function ($a) {
            $last = \Illuminate\Support\Facades\DB::table('bank_reconciliations')->where('cash_account_id', $a->id)
                ->whereNotNull('completed_at')->orderByDesc('completed_at')->first();

            return [
                'code' => $a->code, 'name' => $a->name, 'type' => $a->type,
                'balance_usd' => $a->glAccount ? $this->ledger->balanceAt($a->glAccount->code) : 0.0,
                'unmatched' => BankStatementLine::where('cash_account_id', $a->id)->where('status', BankStatementLine::UNMATCHED)->count(),
                'last_reconciled' => $last?->completed_at,
            ];
        })->all();

        $investors = User::whereIn('id', LedgerEntry::select('user_id')->distinct())->orderBy('id')->get();
        $ledgerHealth = ['investors' => $investors->count(), 'failing' => 0, 'wallet_mismatch' => 0];

        foreach ($investors as $u) {
            $r = $this->reconciler->forUser($u);
            if (! $r['passed']) { $ledgerHealth['failing']++; }
            if (! $r['checks']['wallet']['passed']) { $ledgerHealth['wallet_mismatch']++; }
        }

        $awaiting = ['costs_to_finalise' => [], 'results_to_record' => [], 'allocation_blocked' => []];

        foreach ($gold['lots'] as $l) {
            if ($l['result']['stage'] === 'trading complete, expenses pending') { $awaiting['costs_to_finalise'][] = $l['lot_code']; }
            if ($l['result']['stage'] === 'ready to record') { $awaiting['results_to_record'][] = $l['lot_code']; }
        }

        if ($open) {
            $awaiting['allocation_blocked'] = array_column($this->allocation->forPeriod($open)['blocked'], 'lot_code');
        }

        return [
            'report' => 'dashboard',
            'as_at'  => $today,
            'cash' => [
                'cash_on_hand_usd' => $this->cashByType(CashAccount::TYPE_CASH),
                'bank_usd'         => $this->cashByType(CashAccount::TYPE_BANK),
                'total_usd'        => $this->statements->cashAndBank(null, false),
            ],
            'suspense' => ['balance' => $this->ledger->balanceAt('1090'),
                'caption' => 'Unresolved: origin of funds not established (D2/D3). Shown until resolved.'],
            'gold' => ['remaining_grams' => $gold['totals']['remaining_grams'], 'inventory_usd' => $gold['totals']['inventory_usd'],
                'gl_1100_usd' => $this->ledger->balanceAt('1100'), 'gl_1110_usd' => $this->ledger->balanceAt('1110')],
            'open_period' => $open ? ['code' => $open->code, 'status' => $open->status, 'label' => $open->label(),
                'trading_result_usd' => $openResult['operating_result_usd'], 'is_final' => false, 'status_label' => 'INTERIM - period ' . $open->status] : null,
            'last_closed' => $lastClosed ? ['code' => $lastClosed->code, 'close_reference' => $lastClosed->close_reference,
                'closed_at' => $lastClosed->closed_at?->toDateTimeString(),
                'trading_result_usd' => (float) ($lastClosed->snapshot['operating_result_usd'] ?? 0)] : null,
            'investor_liability' => ['capital_2000_usd' => $this->ledger->balanceAt('2000'), 'profit_2010_usd' => $this->ledger->balanceAt('2010')],
            'banks' => $banks,
            'expenses_outstanding' => ['count' => (clone $expensesOutstanding)->count(), 'total_usd' => round((float) $expensesOutstanding->sum('amount_usd'), 8)],
            'awaiting' => $awaiting,
            'ledger_health' => $ledgerHealth,
            'historical_deals' => count($gold['historical']),
            'journals' => $this->ledger->totalJournals(),
            'empty'    => $this->ledger->totalJournals() === 0,
            'controls' => [],
        ];
    }

    private function cashByType(string $type): float
    {
        $total = 0.0;

        foreach (CashAccount::where('type', $type)->with('glAccount')->get() as $a) {
            if ($a->glAccount) {
                $total += $this->ledger->balanceAt($a->glAccount->code);
            }
        }

        return round($total, 8);
    }
}
