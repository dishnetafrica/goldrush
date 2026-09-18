<?php

namespace App\Accounting\Reports;

use App\Investor\Support\Money;

/**
 * A report as rows, at exact stored precision, for a CSV export.
 *
 * The header carries the scope, the interim/final status and the control
 * outcomes, so a file opened later cannot pass for a final figure when it was
 * an interim one.
 */
class CsvExport
{
    public function rows(array $report): array
    {
        $rows = [
            ['report', $report['report']],
            ['scope', $report['scope']['label'] ?? ($report['as_at'] ?? '')],
            ['status', $report['scope']['status'] ?? ''],
            ['generated_at', now()->toDateTimeString()],
        ];

        foreach ($report['controls'] ?? [] as $c) {
            $rows[] = ['control', $c['name'], $c['status'], Money::exact($c['difference'])];
        }

        $rows[] = [];

        return array_merge($rows, match ($report['report']) {
            'trial-balance' => $this->trialBalance($report),
            'profit-loss'   => $this->profitLoss($report),
            'balance-sheet' => $this->balanceSheet($report),
            'cash-flow'     => $this->cashFlow($report),
            'gold-trading'  => $this->gold($report),
            'investor-liability' => $this->investors($report),
            'period-close'  => $this->periodClose($report),
            'controls'      => $this->controls($report),
            default         => [],
        });
    }

    public function string(array $report): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($this->rows($report) as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $out = stream_get_contents($handle);
        fclose($handle);

        return $out;
    }

    private function trialBalance(array $r): array
    {
        $rows = [['code', 'account', 'type', 'opening', 'debits', 'credits', 'closing']];
        foreach ($r['rows'] as $x) {
            $rows[] = [$x['code'], $x['name'], $x['type'], Money::exact($x['opening']), Money::exact($x['debits']), Money::exact($x['credits']), Money::exact($x['closing'])];
        }
        $rows[] = ['totals', '', '', '', Money::exact($r['totals']['debits']), Money::exact($r['totals']['credits']), Money::exact($r['totals']['difference'])];

        return $rows;
    }

    private function profitLoss(array $r): array
    {
        $t = $r['trading'];
        $rows = [['section', 'line', 'account', 'amount']];
        $rows[] = ['trading', 'Gold sales revenue', '4000', Money::exact($t['revenue_usd'])];
        $rows[] = ['trading', 'Cost of gold sold', '5000', Money::exact($t['cogs_usd'])];
        foreach ($t['expenses'] as $e) {
            $rows[] = ['trading', $e['name'], $e['code'], Money::exact($e['amount_usd'])];
        }
        $rows[] = ['trading', 'REALIZED TRADING RESULT', '', Money::exact($t['realized_result_usd'])];
        $rows[] = ['fx', 'FX gain', '4100', Money::exact($r['fx']['gain_usd'])];
        $rows[] = ['fx', 'FX loss', '6900', Money::exact($r['fx']['loss_usd'])];
        $rows[] = ['appropriation', 'Investor profit share', '7000', Money::exact($r['appropriation']['amount_usd'])];
        foreach ($r['appropriation']['distributions'] as $d) {
            $rows[] = ['appropriation', $d['caption'], $d['journal'], Money::exact($d['net_usd'])];
        }
        $rows[] = ['result', 'RESULT AFTER APPROPRIATION', '', Money::exact($r['result_after_appropriation_usd'])];

        return $rows;
    }

    private function balanceSheet(array $r): array
    {
        $rows = [['section', 'code', 'account', 'balance']];
        foreach (['assets', 'liabilities', 'equity'] as $s) {
            foreach ($r[$s] as $a) {
                $rows[] = [$s, $a['code'], $a['name'], Money::exact($a['balance'])];
            }
        }
        $rows[] = ['equity', '', 'Current period result', Money::exact($r['result']['current_usd'])];
        $rows[] = ['totals', '', 'Assets', Money::exact($r['totals']['assets_usd'])];
        $rows[] = ['totals', '', 'Liabilities + equity + result', Money::exact($r['totals']['right_side_usd'])];

        return $rows;
    }

    private function cashFlow(array $r): array
    {
        $rows = [['section', 'class', 'amount']];
        $rows[] = ['opening', 'Cash and bank', Money::exact($r['opening_usd'])];
        foreach ($r['classes'] as $c) {
            $rows[] = [$c['section'], $c['label'], Money::exact($c['amount'])];
        }
        $rows[] = ['memo', 'Transfers between company accounts (excluded)', Money::exact($r['transfers_total_usd'])];
        $rows[] = ['closing', 'Cash and bank', Money::exact($r['closing_usd'])];

        return $rows;
    }

    private function gold(array $r): array
    {
        $rows = [['deal', 'refined_g', 'sold_g', 'remaining_g', 'purchase', 'capitalised', 'basis', 'cogs', 'inventory', 'revenue', 'expenses', 'result', 'stage', 'recorded']];
        foreach ($r['lots'] as $l) {
            $rows[] = [$l['lot_code'], $l['physical']['refined_grams'], $l['physical']['sold_grams'], $l['physical']['remaining_grams'],
                Money::exact($l['cost']['purchase_usd']), Money::exact($l['cost']['capitalised_usd']), Money::exact($l['cost']['basis_usd']),
                Money::exact($l['cost']['cogs_usd']), Money::exact($l['cost']['inventory_usd']), Money::exact($l['result']['revenue_usd']),
                Money::exact($l['result']['expenses_usd']), Money::exact($l['result']['net_usd']), $l['result']['stage'],
                $l['recorded'] ? Money::exact($l['recorded']['net_profit_usd']) : ''];
        }
        foreach ($r['historical'] as $h) {
            $rows[] = [$h['lot_code'], '', '', '', '', '', '', '', '', '', '', '', $h['label'], Money::exact($h['recorded']['net_profit_usd'])];
        }

        return $rows;
    }

    private function investors(array $r): array
    {
        $rows = [['investor', 'available', 'profit', 'committed', 'total', 'wallet_balance', 'wallet_profit', 'wallet_difference']];
        foreach ($r['rows'] as $x) {
            $rows[] = [$x['investor']['username'], Money::exact($x['closing']['available']), Money::exact($x['closing']['profit']),
                Money::exact($x['closing']['committed']), Money::exact($x['closing']['total']), Money::exact($x['wallet']['balance']),
                Money::exact($x['wallet']['profit_balance']), Money::exact($x['wallet']['difference'])];
        }
        $rows[] = ['GL 2000', Money::exact($r['gl']['capital_2000_usd'])];
        $rows[] = ['GL 2010', Money::exact($r['gl']['profit_2010_usd'])];

        return $rows;
    }

    private function periodClose(array $r): array
    {
        $p = $r['period'];

        return [
            ['period', $p['code']], ['status', $p['status']], ['close_reference', $p['close_reference'] ?? ''],
            ['closed_by', $p['closed_by'] ?? ''], ['closed_at', $p['closed_at'] ?? ''],
            ['revenue', Money::exact($r['result']['ledger_revenue_usd'])], ['cogs', Money::exact($r['result']['ledger_cogs_usd'])],
            ['expenses', Money::exact($r['result']['ledger_expenses_usd'])], ['realized_result', Money::exact($r['result']['operating_result_usd'])],
            ['investor_pool', Money::exact($r['allocation']['investor_pool_usd'])],
        ];
    }

    private function controls(array $r): array
    {
        $rows = [['group', 'control', 'left', 'right', 'difference', 'status', 'caption']];
        foreach ($r['groups'] as $group => $controls) {
            foreach ($controls as $c) {
                $rows[] = [$group, $c['name'], Money::exact($c['left']), Money::exact($c['right']), Money::exact($c['difference']), $c['status'], $c['caption'] ?? ''];
            }
        }

        return $rows;
    }
}
