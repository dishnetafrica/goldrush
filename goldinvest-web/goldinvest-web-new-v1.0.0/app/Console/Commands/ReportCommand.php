<?php

namespace App\Console\Commands;

use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Reports\ClosePackBuilder;
use App\Accounting\Reports\Control;
use App\Accounting\Reports\ControlReports;
use App\Accounting\Reports\FinancialStatements;
use App\Accounting\Reports\InvestorLiabilityReport;
use App\Accounting\Reports\ReportAudit;
use App\Accounting\Reports\ReportScope;
use App\Accounting\Reports\TradingReports;
use App\Accounting\Services\RealizedTradingResult;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * The Phase 3G reports from the console, where every phase is verified first.
 *
 * Read-only. The only thing any of these writes is the audit row saying the
 * report was rendered.
 */
class ReportCommand extends Command
{
    protected $signature = 'report
                            {action : trial-balance|pl|balance-sheet|cash-flow|gold|investors|period|controls|dashboard|pack}
                            {--period= : an accounting period code, e.g. 2026-09}
                            {--from= : start of a date range}
                            {--to= : end of a date range}
                            {--as-at= : balance sheet date}
                            {--source-period= : P&L: show only appropriations of this period\'s result}
                            {--lot= : one deal}
                            {--user= : one investor id}
                            {--show-zero : trial balance: include accounts with nothing in them}';

    protected $description = 'Phase 3G accounting reports, read from the posted ledger';

    public function handle(
        FinancialStatements $statements, TradingReports $trading, InvestorLiabilityReport $investors,
        ControlReports $controls, ClosePackBuilder $packs, ReportAudit $audit,
    ): int {
        try {
            $scope = ReportScope::fromInput([
                'period' => $this->option('period'), 'from' => $this->option('from'), 'to' => $this->option('to'),
                'as_at' => $this->option('as-at'), 'source_period' => $this->option('source-period'),
                'lot' => $this->option('lot'), 'user' => $this->option('user'),
            ]);

            $action = strtolower((string) $this->argument('action'));

            $report = match ($action) {
                'trial-balance' => $this->trialBalance($statements->trialBalance($scope, (bool) $this->option('show-zero'))),
                'pl', 'profit-loss' => $this->profitLoss($statements->profitAndLoss($scope)),
                'balance-sheet' => $this->balanceSheet($statements->balanceSheet($scope)),
                'cash-flow'     => $this->cashFlow($statements->cashFlow($scope)),
                'gold'          => $this->gold($trading->goldTrading($scope)),
                'investors'     => $this->investors($investors->company($scope)),
                'period'        => $this->period($trading->periodClose($this->periodOrFail($scope))),
                'controls'      => $this->controls($controls->all($scope)),
                'dashboard'     => $this->dashboard($trading->dashboard()),
                'pack'          => $this->pack($packs, $this->periodOrFail($scope)),
                default         => throw new \InvalidArgumentException('Unknown report ' . $action . '.'),
            };

            if ($report !== null) {
                $audit->record($report['report'], $report['scope'] ?? [], $report['controls'] ?? [], ! $scope->isFinal());
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function periodOrFail(ReportScope $scope): AccountingPeriod
    {
        return $scope->period ?? throw new \InvalidArgumentException('Name the period: --period=2026-09');
    }

    private function banner(array $r, string $title): void
    {
        $this->line(strtoupper($title) . '   ' . ($r['scope']['label'] ?? ''));
        $this->line('Status: ' . ($r['scope']['status'] ?? ''));

        if (! empty($r['empty'])) {
            $this->warn('The ledger holds no journals in this scope. Every figure below is zero because nothing has been posted, not because nothing happened.');
        }

        $this->line(str_repeat('-', 78));
    }

    private function controlsTable(array $controls): void
    {
        if ($controls === []) {
            return;
        }

        $this->newLine();
        $this->line('Controls');
        $this->table(['Control', 'Left', 'Right', 'Difference', 'Status'], array_map(fn ($c) => [
            $c['name'],
            $c['right_label'] === '' ? $c['left_label'] : Money::exact($c['left']),
            $c['right_label'] === '' ? '' : Money::exact($c['right']),
            $c['right_label'] === '' ? '' : Money::exact($c['difference']),
            $c['status'] . ($c['caption'] ? ' - ' . $c['caption'] : ''),
        ], $controls));
    }

    private function trialBalance(array $tb): array
    {
        $this->banner($tb, 'Trial balance');
        $this->table(['Code', 'Account', 'Opening', 'Debits', 'Credits', 'Closing'], array_map(fn ($r) => [
            $r['code'], $r['name'], Money::format($r['opening']), Money::format($r['debits']), Money::format($r['credits']), Money::format($r['closing']),
        ], $tb['rows']));
        $t = $tb['totals'];
        $this->line('  Period debits ' . Money::exact($t['debits']) . '  credits ' . Money::exact($t['credits'])
            . '  difference ' . Money::exact($t['difference']) . '  ' . ($tb['balanced'] ? 'BALANCED' : 'UNBALANCED'));
        $this->line('  Journals: ' . $tb['journals']['total'] . ' (' . $tb['journals']['reversed'] . ' reversed)');
        $this->controlsTable($tb['controls']);

        return $tb;
    }

    private function profitLoss(array $pl): array
    {
        $this->banner($pl, 'Profit and loss');
        $t = $pl['trading'];
        $this->line('TRADING RESULT');
        $this->line('  4000 Gold sales revenue            ' . $this->right($t['revenue_usd']));
        $this->line('  5000 Cost of gold sold             ' . $this->right(-$t['cogs_usd']));
        $this->line('  Gross trading profit               ' . $this->right($t['gross_usd']));
        foreach ($t['expenses'] as $e) {
            $this->line('  ' . $e['code'] . ' ' . str_pad($e['name'], 30) . ' ' . $this->right(-$e['amount_usd']));
        }
        $this->line('  REALIZED TRADING RESULT            ' . $this->right($t['realized_result_usd']) . '   (of which deals '
            . Money::format($t['deal_result_usd']) . ', overheads ' . Money::format($t['overheads_usd']) . ')');
        if (abs($pl['fx']['net_usd']) > Control::EPSILON) {
            $this->line('  FX gain 4100 / loss 6900           ' . $this->right($pl['fx']['net_usd']));
        }
        $this->newLine();
        $this->line('APPROPRIATION (declared in this scope' . ($pl['appropriation']['source_period_filter'] ? '; source period ' . $pl['appropriation']['source_period_filter'] : '') . ')');
        $this->line('  7000 Investor profit share         ' . $this->right(-$pl['appropriation']['amount_usd']));
        foreach ($pl['appropriation']['distributions'] as $d) {
            $this->line('    ' . $d['caption'] . ' ' . ($d['status'] === 'reversed' ? '(REVERSED) ' : '') . Money::format($d['net_usd'])
                . ' dated ' . $d['journal_date'] . ' in ' . $d['declared_in']);
        }
        $this->line('  RESULT AFTER APPROPRIATION         ' . $this->right($pl['result_after_appropriation_usd']));
        foreach ($pl['appropriation']['appropriated_elsewhere'] as $d) {
            $this->line('  Note: this period\'s result was appropriated in ' . $d['declared_in'] . ' under ' . $d['reference']
                . ' (' . Money::format($d['pool_usd']) . ', ' . $d['status'] . '); the trading result above is unchanged by it.');
        }
        $this->controlsTable($pl['controls']);

        return $pl;
    }

    private function balanceSheet(array $bs): array
    {
        $this->banner($bs, 'Balance sheet as at ' . $bs['as_at']);
        foreach (['assets' => 'ASSETS', 'liabilities' => 'LIABILITIES', 'equity' => 'EQUITY'] as $key => $title) {
            $this->line($title);
            foreach ($bs[$key] as $a) {
                if (abs($a['balance']) > Control::EPSILON || $a['code'] === '1090' || in_array($a['code'], ['2000', '2010'], true)) {
                    $this->line('  ' . $a['code'] . ' ' . str_pad($a['name'], 38) . $this->right($a['balance'])
                        . ($a['code'] === '1090' ? '   ' . $bs['suspense']['caption'] : ''));
                }
            }
        }
        $this->line('  Current period result              ' . $this->right($bs['result']['current_usd'])
            . '   (trading ' . Money::format($bs['result']['trading_usd']) . ' - appropriation ' . Money::format($bs['result']['appropriation_usd']) . ')');
        $t = $bs['totals'];
        $this->line('  Assets ' . Money::exact($t['assets_usd']) . ' = liabilities ' . Money::exact($t['liabilities_usd']) . ' + equity '
            . Money::exact($t['equity_usd']) . ' + result ' . Money::exact($bs['result']['current_usd']) . '   ' . ($bs['balanced'] ? 'HOLDS' : 'DOES NOT HOLD: ' . Money::exact($t['difference_usd'])));
        $this->controlsTable($bs['controls']);

        return $bs;
    }

    private function cashFlow(array $cf): array
    {
        $this->banner($cf, 'Cash flow');
        $this->line('  Opening cash and bank              ' . $this->right($cf['opening_usd']));
        foreach (['operating', 'financing', 'suspense', 'other'] as $section) {
            foreach ($cf['classes'] as $c) {
                if ($c['section'] === $section && abs($c['amount']) > Control::EPSILON) {
                    $this->line('  ' . str_pad(ucfirst($section) . ': ' . $c['label'], 60) . $this->right($c['amount']));
                }
            }
        }
        $this->line('  Net movement                       ' . $this->right($cf['net_movement_usd']));
        $this->line('  Transfers between company accounts ' . $this->right($cf['transfers_total_usd']) . '   memo only, in no class');
        $this->line('  Closing cash and bank              ' . $this->right($cf['closing_usd']));
        $this->controlsTable($cf['controls']);

        return $cf;
    }

    private function gold(array $g): array
    {
        $this->banner($g, 'Gold trading / lot performance');
        $this->table(['Deal', 'Refined g', 'Sold g', 'Left g', 'Basis', 'Capitalised', 'COGS', 'Inventory', 'Revenue', 'Expenses', 'Result', 'Stage', 'Recorded'],
            array_map(fn ($l) => [
                $l['lot_code'], number_format($l['physical']['refined_grams'], 4), number_format($l['physical']['sold_grams'], 4),
                number_format($l['physical']['remaining_grams'], 4), Money::format($l['cost']['basis_usd']), Money::format($l['cost']['capitalised_usd']),
                Money::format($l['cost']['cogs_usd']), Money::format($l['cost']['inventory_usd']), Money::format($l['result']['revenue_usd']),
                Money::format($l['result']['expenses_usd']), Money::format($l['result']['net_usd']), $l['result']['stage'],
                $l['recorded'] ? Money::format($l['recorded']['net_profit_usd']) : '-',
            ], $g['lots']));

        if ($g['historical'] !== []) {
            $this->newLine();
            $this->warn($g['historical_label']);
            foreach ($g['historical'] as $h) {
                $this->line('  ' . $h['lot_code'] . ': attributed ' . Money::format($h['recorded']['net_profit_usd']) . ' (investors '
                    . Money::format($h['recorded']['investor_profit_usd']) . ') - ' . $h['note']);
            }
        }
        $this->controlsTable($g['controls']);

        return $g;
    }

    private function investors(array $il): array
    {
        $this->banner($il, 'Investor liability');
        $this->table(['Investor', 'Available', 'Profit', 'Committed', 'Total', 'Wallet bal', 'Wallet profit', 'Δ wallet'], array_map(fn ($r) => [
            $r['investor']['username'], Money::format($r['closing']['available']), Money::format($r['closing']['profit']),
            Money::format($r['closing']['committed']), Money::format($r['closing']['total']), Money::format($r['wallet']['balance']),
            Money::format($r['wallet']['profit_balance']), Money::exact($r['wallet']['difference']),
        ], $il['rows']));
        $this->newLine();
        $this->line('  Investor ledger capital (available + committed)   ' . $this->right($il['totals']['available'] + $il['totals']['committed']));
        $this->line('  GL 2000 Investor Capital Payable                  ' . $this->right($il['gl']['capital_2000_usd']));
        $this->line('  Investor ledger profit                            ' . $this->right($il['totals']['profit']));
        $this->line('  GL 2010 Investor Profit Payable                   ' . $this->right($il['gl']['profit_2010_usd']));
        foreach ($il['distribution_lines'] as $l) {
            $this->line('  ' . $l['distribution'] . ' ' . $l['user'] . ' ' . Money::exact($l['amount_usd']) . ' -> ' . $l['ledger_reference']
                . ' ' . ($l['matched'] ? 'matched' : 'NOT MATCHED'));
        }
        $this->controlsTable($il['controls']);

        return $il;
    }

    private function period(array $pc): array
    {
        $this->banner($pc, 'Period close report ' . $pc['period']['code']);
        $p = $pc['period'];
        $this->table(['', ''], [
            ['Status', $p['status']], ['Close reference', $p['close_reference'] ?? '-'], ['Closed by / at', ($p['closed_by'] ?? '-') . ' / ' . ($p['closed_at'] ?? '-')],
            ['Reason', $p['close_reason'] ?? '-'], ['Reopened', $p['reopened_at'] ? $p['reopened_at'] . ' - ' . $p['reopen_reason'] : '-'],
            ['Revenue / COGS / expenses', Money::format($pc['result']['ledger_revenue_usd']) . ' / ' . Money::format($pc['result']['ledger_cogs_usd']) . ' / ' . Money::format($pc['result']['ledger_expenses_usd'])],
            ['Realized trading result', Money::format($pc['result']['operating_result_usd'])],
            ['Investor pool (allocation)', Money::format($pc['allocation']['investor_pool_usd']) . ' - ' . ($pc['allocation']['distributable'] ? 'distributable' : (implode('; ', $pc['allocation']['refusals']) ?: 'nothing to distribute'))],
            ['Distributions', implode(', ', array_map(fn ($d) => $d['reference'] . ' ' . $d['status'] . ' ' . Money::format($d['pool_usd']) . ' in ' . $d['declared_in'], $pc['distributions'])) ?: '-'],
            ['2010 at period end / now', Money::format($pc['payable_2010']['at_period_end']) . ' / ' . Money::format($pc['payable_2010']['now'])],
        ]);
        foreach ($pc['blockers'] as $b) {
            $this->warn('  blocker: ' . $b);
        }
        $this->controlsTable($pc['controls']);

        return $pc;
    }

    private function controls(array $c): array
    {
        $this->banner($c, 'Reconciliation and control reports');
        foreach ($c['groups'] as $group => $controls) {
            $this->line($group);
            $this->controlsTable($controls);
        }
        $this->newLine();
        $this->line('  ' . count($c['exceptions']) . ' control exception(s), ' . count($c['pre_backfill']) . ' pre-backfill difference(s) (D2/D3 open).');

        return $c;
    }

    private function dashboard(array $d): array
    {
        $this->line('MANAGEMENT DASHBOARD as at ' . $d['as_at']);
        if ($d['empty']) {
            $this->warn('The ledger holds no journals yet.');
        }
        $this->table(['Tile', 'Value', 'Source'], [
            ['Cash on hand', Money::format($d['cash']['cash_on_hand_usd']), '1000-series balances'],
            ['Bank', Money::format($d['cash']['bank_usd']), '1010-series balances'],
            ['Suspense', Money::format($d['suspense']['balance']), '1090 - ' . $d['suspense']['caption']],
            ['Gold held', number_format($d['gold']['remaining_grams'], 4) . ' g at cost ' . Money::format($d['gold']['inventory_usd']), 'LotCostBasis; GL 1100+1110 ' . Money::format($d['gold']['gl_1100_usd'] + $d['gold']['gl_1110_usd'])],
            ['Open period', $d['open_period'] ? $d['open_period']['code'] . ' result ' . Money::format($d['open_period']['trading_result_usd']) . ' (' . $d['open_period']['status_label'] . ')' : '-', 'RealizedTradingResult'],
            ['Last closed', $d['last_closed'] ? $d['last_closed']['code'] . ' ' . $d['last_closed']['close_reference'] . ' result ' . Money::format($d['last_closed']['trading_result_usd']) : 'none', 'close snapshot'],
            ['Investor capital payable', Money::format($d['investor_liability']['capital_2000_usd']), 'GL 2000'],
            ['Investor profit payable', Money::format($d['investor_liability']['profit_2010_usd']), 'GL 2010'],
            ['Outstanding expenses', $d['expenses_outstanding']['count'] . ' / ' . Money::format($d['expenses_outstanding']['total_usd']), 'gold_trading_expenses draft/submitted/approved'],
            ['Awaiting', 'costs ' . count($d['awaiting']['costs_to_finalise']) . ', record ' . count($d['awaiting']['results_to_record']) . ', allocation blocked ' . count($d['awaiting']['allocation_blocked']), 'RealizedTradingResult / InvestorAllocation'],
            ['Ledger health', $d['ledger_health']['investors'] . ' investor(s), ' . $d['ledger_health']['failing'] . ' failing, ' . $d['ledger_health']['wallet_mismatch'] . ' wallet mismatch', 'ledger:check'],
            ['Historical deals', $d['historical_deals'] . ' (' . RealizedTradingResult::HISTORICAL_ATTRIBUTION . ')', 'gold_lot_results'],
        ]);

        return ['report' => 'dashboard', 'scope' => ['as_at' => $d['as_at']], 'controls' => []];
    }

    private function pack(ClosePackBuilder $packs, AccountingPeriod $period): array
    {
        $existing = $packs->existing($period);
        $pack = $packs->build($period);
        $this->info(($existing ? 'Existing pack ' : 'Generated pack ') . $pack->reference . ' for ' . $period->code . ' (' . $pack->close_reference . ')');
        $this->line('  ' . $pack->file_path . '  ' . $pack->file_bytes . ' bytes  sha256 ' . $pack->file_hash);
        $this->line('  controls ' . ($pack->controls_passed ? 'all passed' : 'with exceptions or pre-backfill differences') . '; intact ' . ($pack->intact() ? 'yes' : 'NO'));

        return ['report' => 'close-pack', 'scope' => ['period' => $period->code], 'controls' => []];
    }

    private function right(float $amount): string
    {
        return str_pad(Money::format($amount), 16, ' ', STR_PAD_LEFT);
    }
}
