<?php

namespace App\Accounting\Reports;

use App\Accounting\Ledger\AccountType;
use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\InvestorDistribution;
use App\Accounting\Services\RealizedTradingResult;
use Illuminate\Support\Carbon;

/**
 * The four financial statements, every figure read from posted journal lines.
 *
 * The trading result on the profit and loss is the one RealizedTradingResult
 * produces, reached by the same code, so the statement and the close cannot
 * disagree. Account 7000 is read afterwards, in its own section, from a query
 * that the trading result never sees. The balance sheet is the trial balance
 * arranged by type, and says so when it does not balance. The cash flow
 * classifies each movement on a cash account by the account on the other side
 * of its journal, and a journal whose every line is a cash account is a
 * transfer: shown once, as a memo, and in no class at all.
 */
class FinancialStatements
{
    public const APPROPRIATION = '7000';
    public const FX_GAIN = '4100';
    public const FX_LOSS = '6900';
    public const SUSPENSE = '1090';

    public function __construct(
        private readonly LedgerFigures $ledger,
        private readonly RealizedTradingResult $results,
    ) {
    }

    // ------------------------------------------------------------ trial balance

    public function trialBalance(ReportScope $scope, bool $showZero = false): array
    {
        $rows = $this->ledger->accountsTable($scope);

        $openingDr = $openingCr = $periodDr = $periodCr = $closingDr = $closingCr = 0.0;
        $inconsistent = [];

        foreach ($rows as $row) {
            $debitNormal = $row['normal_balance'] === AccountType::DEBIT;
            $periodDr += $row['debits'];
            $periodCr += $row['credits'];

            // A signed balance is shown on its natural side; a negative one has
            // flipped and is shown on the other.
            [$oDr, $oCr] = $this->sides($row['opening'], $debitNormal);
            [$cDr, $cCr] = $this->sides($row['closing'], $debitNormal);
            $openingDr += $oDr;
            $openingCr += $oCr;
            $closingDr += $cDr;
            $closingCr += $cCr;

            if (! $row['consistent']) {
                $inconsistent[] = $row['code'];
            }
        }

        $periodDiff = round($periodDr - $periodCr, 8);
        $closingDiff = round($closingDr - $closingCr, 8);

        $controls = [
            Control::make('tb_period', 'Period debits equal period credits', 'Debits', $periodDr, 'Credits', $periodCr),
            Control::make('tb_closing', 'Closing debit balances equal closing credit balances', 'Debit balances', $closingDr, 'Credit balances', $closingCr),
            Control::flag('tb_consistent', 'Opening plus movements reproduces every closing balance', $inconsistent === [],
                $inconsistent === [] ? 'every account consistent' : 'inconsistent: ' . implode(', ', $inconsistent)),
        ];

        if (! $showZero) {
            $rows = array_values(array_filter($rows, fn ($r) => abs($r['opening']) > Control::EPSILON
                || abs($r['debits']) > Control::EPSILON || abs($r['credits']) > Control::EPSILON || abs($r['closing']) > Control::EPSILON));
        }

        $journals = $this->ledger->journalCounts($scope);

        return [
            'report'   => 'trial-balance',
            'scope'    => $scope->toArray(),
            'rows'     => $rows,
            'totals'   => [
                'opening_debits' => round($openingDr, 8), 'opening_credits' => round($openingCr, 8),
                'debits' => round($periodDr, 8), 'credits' => round($periodCr, 8), 'difference' => $periodDiff,
                'closing_debits' => round($closingDr, 8), 'closing_credits' => round($closingCr, 8), 'closing_difference' => $closingDiff,
            ],
            'balanced' => abs($periodDiff) <= Control::EPSILON && abs($closingDiff) <= Control::EPSILON,
            'journals' => $journals,
            'empty'    => $journals['total'] === 0,
            'controls' => $controls,
        ];
    }

    /** Which side of the trial balance a signed balance sits on: [debit, credit]. */
    private function sides(float $signed, bool $debitNormal): array
    {
        $onDebitSide = ($debitNormal && $signed >= 0) || (! $debitNormal && $signed < 0);

        return $onDebitSide ? [abs($signed), 0.0] : [0.0, abs($signed)];
    }

    // ---------------------------------------------------------- profit and loss

    public function profitAndLoss(ReportScope $scope): array
    {
        // The trading result, from the service that owns it. 7000 is not an
        // input here and cannot be: the service's expense range stops at 6899.
        $company = $this->results->forCompany($scope->ledgerScope());

        $expenses = $this->ledger->rangeMovement('6000', '6899', AccountType::EXPENSE, $scope);
        $fxGain = $this->ledger->movement(self::FX_GAIN, $scope)['net'];
        $fxLoss = $this->ledger->movement(self::FX_LOSS, $scope)['net'];

        $realized = $company['operating_result_usd'];

        // Now, and only now, the appropriation: a separate query on 7000, and the
        // distributions declared in this scope that explain it.
        $appropriation = $this->ledger->movement(self::APPROPRIATION, $scope)['net'];
        $declared = $this->distributionsDeclaredIn($scope);
        $listed = $scope->sourcePeriod
            ? array_values(array_filter($declared, fn ($d) => $d['source_period'] === $scope->sourcePeriod))
            : $declared;
        $declaredTotal = round(array_sum(array_column($declared, 'net_usd')), 8);

        $appropriatedElsewhere = $scope->period ? $this->appropriationsOf($scope->period) : [];

        $afterAppropriation = round($realized + $fxGain - $fxLoss - $appropriation, 8);

        $controls = [
            Control::make('pl_revenue', 'Revenue on the statement is the 4000 credited in the ledger',
                'Statement revenue', $company['ledger_revenue_usd'], 'Ledger 4000', $this->ledger->movement('4000', $scope)['net']),
            Control::make('pl_cogs', 'Cost of sales on the statement is the 5000 debited in the ledger',
                'Statement COGS', $company['ledger_cogs_usd'], 'Ledger 5000', $this->ledger->movement('5000', $scope)['net']),
            Control::make('pl_expenses', 'Expenses on the statement are 6000-6899, nothing else',
                'Statement expenses', $company['ledger_expenses_usd'], 'Ledger 6000-6899', $expenses['total']),
            Control::make('pl_result', 'Trading result equals the realized trading result service',
                'Statement result', round($company['ledger_revenue_usd'] - $company['ledger_cogs_usd'] - $company['ledger_expenses_usd'], 8),
                'RealizedTradingResult', $realized),
            Control::make('pl_appropriation', 'Appropriation posted to 7000 equals the distributions declared in scope',
                'Ledger 7000', $appropriation, 'Distributions declared', $declaredTotal, false,
                'A 7000 movement that no distribution explains, or a distribution missing its journal.'),
        ];

        $journals = $this->ledger->journalCounts($scope);

        return [
            'report'  => 'profit-loss',
            'scope'   => $scope->toArray(),
            'trading' => [
                'revenue_usd'           => $company['ledger_revenue_usd'],
                'cogs_usd'              => $company['ledger_cogs_usd'],
                'gross_usd'             => round($company['ledger_revenue_usd'] - $company['ledger_cogs_usd'], 8),
                'expenses'              => $expenses['accounts'],
                'expenses_usd'          => $company['ledger_expenses_usd'],
                'deal_result_usd'       => $company['trading_result_usd'],
                'overheads_usd'         => $company['unattributed_expenses_usd'],
                'realized_result_usd'   => $realized,
                'deals'                 => $company['lots'],
                'interim_lots'          => $company['interim_lots'],
                'is_final'              => $company['is_final'],
            ],
            'fx' => ['gain_usd' => $fxGain, 'loss_usd' => $fxLoss, 'net_usd' => round($fxGain - $fxLoss, 8)],
            'appropriation' => [
                'account'                 => self::APPROPRIATION,
                'amount_usd'              => $appropriation,
                'distributions'           => $listed,
                'distributions_all'       => $declared,
                'source_period_filter'    => $scope->sourcePeriod,
                'declared_total_usd'      => $declaredTotal,
                'appropriated_elsewhere'  => $appropriatedElsewhere,
            ],
            'result_after_appropriation_usd' => $afterAppropriation,
            'journals' => $journals,
            'empty'    => $journals['total'] === 0,
            'controls' => $controls,
        ];
    }

    /**
     * Distributions whose appropriation journal (or reversal) is dated in this
     * scope, each with the period whose result it appropriated.
     */
    public function distributionsDeclaredIn(ReportScope $scope): array
    {
        $out = [];

        foreach (InvestorDistribution::with(['period', 'journal', 'lines'])->orderBy('id')->get() as $d) {
            $journal = $d->journal;
            $reversal = $d->reversal_journal_id ? \App\Accounting\Models\Journal::find($d->reversal_journal_id) : null;

            $posted = $journal && $this->journalInScope($journal, $scope);
            $reversed = $reversal && $this->journalInScope($reversal, $scope);

            if (! $posted && ! $reversed) {
                continue;
            }

            $out[] = [
                'reference'         => $d->reference,
                'status'            => $d->status,
                'source_period'     => $d->period?->code,
                'journal'           => $journal?->reference,
                'journal_date'      => $journal?->journal_date?->toDateString(),
                'declared_in'       => $journal ? AccountingPeriod::find($journal->accounting_period_id)?->code : null,
                'pool_usd'          => (float) $d->pool_usd,
                'posted_in_scope'   => $posted,
                'reversal_journal'  => $reversal?->reference,
                'reversed_in_scope' => $reversed,
                'net_usd'           => round(($posted ? (float) $d->pool_usd : 0) - ($reversed ? (float) $d->pool_usd : 0), 8),
                'investors'         => $d->investors_count,
                'close_reference'   => $d->period?->close_reference,
                'caption'           => 'appropriation of ' . ($d->period?->code ?? '?') . ' result, ' . $d->reference,
            ];
        }

        return $out;
    }

    /** Where a period's own result was appropriated, whatever period that was declared in. */
    public function appropriationsOf(AccountingPeriod $period): array
    {
        return InvestorDistribution::with('journal')->where('accounting_period_id', $period->id)->orderBy('id')->get()
            ->map(fn ($d) => [
                'reference'    => $d->reference,
                'status'       => $d->status,
                'pool_usd'     => (float) $d->pool_usd,
                'journal'      => $d->journal?->reference,
                'journal_date' => $d->journal?->journal_date?->toDateString(),
                'declared_in'  => $d->journal ? AccountingPeriod::find($d->journal->accounting_period_id)?->code : null,
            ])->all();
    }

    // ------------------------------------------------------------ balance sheet

    public function balanceSheet(ReportScope $scope): array
    {
        $asAt = $scope->end()->toDateString();
        $byType = $this->ledger->balancesByType($asAt);

        $assets = $byType[AccountType::ASSET] ?? [];
        $liabilities = $byType[AccountType::LIABILITY] ?? [];
        $equity = $byType[AccountType::EQUITY] ?? [];

        // Result accounts to date, split so the reader sees what the trading
        // result was before the appropriation and after it.
        $plAccounts = array_merge($byType[AccountType::REVENUE] ?? [], $byType[AccountType::COGS] ?? [], $byType[AccountType::EXPENSE] ?? []);
        $trading = 0.0;
        $fx = 0.0;
        $appropriation = 0.0;

        foreach ($byType[AccountType::REVENUE] ?? [] as $a) {
            if ($a['code'] === self::FX_GAIN) { $fx += $a['balance']; } else { $trading += $a['balance']; }
        }
        foreach ($byType[AccountType::COGS] ?? [] as $a) {
            $trading -= $a['balance'];
        }
        foreach ($byType[AccountType::EXPENSE] ?? [] as $a) {
            if ($a['code'] === self::APPROPRIATION) { $appropriation += $a['balance']; }
            elseif ($a['code'] === self::FX_LOSS) { $fx -= $a['balance']; }
            else { $trading -= $a['balance']; }
        }

        $currentResult = round($trading + $fx - $appropriation, 8);

        $totalAssets = round(array_sum(array_column($assets, 'balance')), 8);
        $totalLiabilities = round(array_sum(array_column($liabilities, 'balance')), 8);
        $totalEquity = round(array_sum(array_column($equity, 'balance')), 8);
        $rightSide = round($totalLiabilities + $totalEquity + $currentResult, 8);

        $suspense = $this->ledger->balanceAt(self::SUSPENSE, $asAt);
        $capital = $this->ledger->balanceAt('2000', $asAt);
        $profit = $this->ledger->balanceAt('2010', $asAt);

        $controls = [
            Control::make('bs_equation', 'Assets equal liabilities plus equity plus current result',
                'Assets', $totalAssets, 'Liabilities + equity + result', $rightSide),
            Control::flag('bs_separate', '2000 and 2010 are separate lines, never merged',
                true, 'capital ' . number_format($capital, 8, '.', '') . ' / profit ' . number_format($profit, 8, '.', '')),
            Control::flag('bs_suspense', '1090 Suspense is shown whatever its balance while D2/D3 is open',
                true, 'shown: ' . number_format($suspense, 8, '.', '')),
        ];

        return [
            'report'      => 'balance-sheet',
            'scope'       => $scope->toArray() + ['as_at' => $asAt],
            'as_at'       => $asAt,
            'assets'      => $assets,
            'liabilities' => $liabilities,
            'equity'      => $equity,
            'result'      => [
                'trading_usd'       => round($trading, 8),
                'fx_usd'            => round($fx, 8),
                'appropriation_usd' => round($appropriation, 8),
                'current_usd'       => $currentResult,
                'note'              => 'No retained-earnings sweep exists at close (G3, open); results to date are carried here.',
            ],
            'totals' => [
                'assets_usd'      => $totalAssets,
                'liabilities_usd' => $totalLiabilities,
                'equity_usd'      => $totalEquity,
                'right_side_usd'  => $rightSide,
                'difference_usd'  => round($totalAssets - $rightSide, 8),
            ],
            'suspense' => [
                'code'    => self::SUSPENSE,
                'balance' => $suspense,
                'caption' => 'Unresolved: origin of funds not established (D2/D3). Shown, never netted, never hidden.',
            ],
            'investor_liability' => [
                'capital_2000_usd' => $capital,
                'profit_2010_usd'  => $profit,
                'note'             => 'Investor money is a liability, split between capital and profit. It is never an asset, never gold, never a share of a lot.',
            ],
            'balanced' => abs($totalAssets - $rightSide) <= Control::EPSILON,
            'journals' => $this->ledger->totalJournals($asAt),
            'empty'    => $this->ledger->totalJournals($asAt) === 0,
            'controls' => $controls,
        ];
    }

    // ---------------------------------------------------------------- cash flow

    public function cashFlow(ReportScope $scope): array
    {
        $classes = [
            'gold_purchases'   => ['section' => 'operating', 'label' => 'Gold purchases and capitalised costs (1100/1110; 5000 nets against inventory)', 'amount' => 0.0],
            'gold_sales'       => ['section' => 'operating', 'label' => 'Gold sales and buyer settlements (4000/1300)', 'amount' => 0.0],
            'expenses_paid'    => ['section' => 'operating', 'label' => 'Deal and operating expenses paid (6000-6899)', 'amount' => 0.0],
            'accruals_settled' => ['section' => 'operating', 'label' => 'Accrued expenses settled (2100)', 'amount' => 0.0],
            'fx'               => ['section' => 'operating', 'label' => 'FX differences (4100/6900)', 'amount' => 0.0],
            'investor_capital' => ['section' => 'financing', 'label' => 'Investor capital received / returned (2000)', 'amount' => 0.0],
            'investor_paid'    => ['section' => 'financing', 'label' => 'Investor profit and withdrawals paid (2010/2200)', 'amount' => 0.0,
                'caption' => 'G1 open: investor withdrawals are in the investor ledger but not yet bridged to the GL, so this line cannot yet fall when profit is paid out.'],
            'owner_capital'    => ['section' => 'financing', 'label' => 'Owner capital (3000/3100)', 'amount' => 0.0],
            'suspense'         => ['section' => 'suspense', 'label' => 'Unidentified receipts / payments (1090)', 'amount' => 0.0,
                'caption' => 'Shown separately, never netted. D2/D3 open.'],
            'other'            => ['section' => 'other', 'label' => 'Other', 'amount' => 0.0],
        ];
        $transfers = [];
        $transferTotal = 0.0;
        $rows = [];

        foreach ($this->ledger->cashJournals($scope) as $entry) {
            $journal = $entry['journal'];
            $lines = $entry['lines'];

            $cashLines = array_filter($lines, fn ($l) => $l->control_of === Account::CONTROL_CASH);
            $counterLines = array_filter($lines, fn ($l) => $l->control_of !== Account::CONTROL_CASH);

            // Every line on a cash account: money moved between the company's own
            // accounts. It is not a flow, so it goes in no class, and it is shown
            // once as a memo so the reader knows it happened.
            if ($counterLines === []) {
                $amount = round(array_sum(array_map(fn ($l) => (float) $l->debit, $cashLines)), 8);
                $transfers[] = ['journal' => $journal->reference, 'date' => $journal->journal_date->toDateString(),
                    'memo' => $journal->memo, 'amount_usd' => $amount];
                $transferTotal += $amount;
                continue;
            }

            // Double entry makes this exact: the cash lines and the counter lines of
            // one journal sum to zero, so each counter line's credit minus debit is
            // precisely the cash it explains. A sale journal that also carries the
            // cost of sales (Dr 5000 / Cr 1110) nets those two lines to nothing
            // inside the inventory class and leaves the proceeds where they belong.
            foreach ($counterLines as $line) {
                $amount = round((float) $line->credit - (float) $line->debit, 8);
                $class = $this->classify($line->code);
                $classes[$class]['amount'] = round($classes[$class]['amount'] + $amount, 8);
                $rows[] = ['journal' => $journal->reference, 'date' => $journal->journal_date->toDateString(),
                    'memo' => $journal->memo, 'counter' => $line->code . ' ' . $line->name, 'class' => $class, 'amount_usd' => $amount];
            }
        }

        $sections = ['operating' => 0.0, 'financing' => 0.0, 'suspense' => 0.0, 'other' => 0.0];

        foreach ($classes as $c) {
            $sections[$c['section']] = round($sections[$c['section']] + $c['amount'], 8);
        }

        $netMovement = round(array_sum($sections), 8);

        $opening = $this->cashAndBank($scope->openingDate(), $scope->start() === null);
        $closing = $this->cashAndBank($scope->end()->toDateString(), false);

        $controls = [
            Control::make('cf_reconciles', 'Opening cash and bank plus classified movements equals closing',
                'Opening + movements', round($opening + $netMovement, 8), 'Closing cash and bank', $closing),
            Control::flag('cf_transfers', 'Transfers between company accounts are excluded from every class and shown once',
                true, count($transfers) . ' transfer(s), ' . number_format($transferTotal, 2) . ' shown as memo only'),
        ];

        $journals = $this->ledger->journalCounts($scope);

        return [
            'report'        => 'cash-flow',
            'scope'         => $scope->toArray(),
            'opening_usd'   => $opening,
            'classes'       => $classes,
            'sections'      => $sections,
            'net_movement_usd' => $netMovement,
            'closing_usd'   => $closing,
            'transfers'     => $transfers,
            'transfers_total_usd' => round($transferTotal, 8),
            'rows'          => $rows,
            'journals'      => $journals,
            'empty'         => $journals['total'] === 0,
            'controls'      => $controls,
        ];
    }

    /** Sum of every cash-control account balance as at a date. */
    public function cashAndBank(?string $asAt, bool $nothingYet): float
    {
        if ($nothingYet && $asAt === null) {
            return 0.0;
        }

        $total = 0.0;

        foreach (Account::where('control_of', Account::CONTROL_CASH)->get() as $account) {
            $total += $this->ledger->balanceAt($account->code, $asAt);
        }

        return round($total, 8);
    }

    private function classify(string $code): string
    {
        return match (true) {
            in_array($code, ['1100', '1110', '5000'], true) => 'gold_purchases',
            in_array($code, ['4000', '1300'], true) => 'gold_sales',
            $code >= '6000' && $code <= '6899'      => 'expenses_paid',
            $code === '2100'                        => 'accruals_settled',
            in_array($code, ['4100', '6900'], true) => 'fx',
            $code === '2000'                        => 'investor_capital',
            in_array($code, ['2010', '2200'], true) => 'investor_paid',
            in_array($code, ['3000', '3100'], true) => 'owner_capital',
            $code === '1090'                        => 'suspense',
            default                                 => 'other',
        };
    }

    private function journalInScope(\App\Accounting\Models\Journal $journal, ReportScope $scope): bool
    {
        if ($scope->period) {
            return (int) $journal->accounting_period_id === (int) $scope->period->id;
        }

        $date = $journal->journal_date->toDateString();

        return (! $scope->from || $date >= $scope->from) && $date <= $scope->end()->toDateString();
    }
}
