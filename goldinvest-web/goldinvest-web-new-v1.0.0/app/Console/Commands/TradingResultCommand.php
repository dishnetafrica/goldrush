<?php

namespace App\Console\Commands;

use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Services\RealizedResultRecorder;
use App\Accounting\Services\RealizedTradingResult;
use App\GoldTrading\Models\GoldLot;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * What the company actually made, read out of its own books.
 *
 * Reporting is separate from recording on purpose. Looking at a figure and
 * standing behind it are different acts, and a command that did both would make
 * the second one happen every time somebody did the first.
 */
class TradingResultCommand extends Command
{
    protected $signature = 'trading
                            {action=result : result|realize|expenses-final|reopen-expenses}
                            {lot? : lot code}
                            {--period= : an accounting period code, e.g. 2026-09}
                            {--from= : first journal date to include}
                            {--to= : last journal date to include}
                            {--reason= : why the costs of a deal are being reopened}
                            {--note= }';

    protected $description = 'Report or record the company\'s realized trading result';

    public function handle(RealizedTradingResult $results, RealizedResultRecorder $recorder): int
    {
        try {
            return match (strtolower((string) $this->argument('action'))) {
                'result'           => $this->report($results),
                'realize'          => $this->realize($recorder, $results),
                'expenses-final'   => $this->finalise($recorder),
                'reopen-expenses'  => $this->reopen($recorder),
                default => throw new \InvalidArgumentException(
                    'Action must be result, realize, expenses-final or reopen-expenses.'
                ),
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function report(RealizedTradingResult $results): int
    {
        $scope = $this->scope();

        if ($this->argument('lot')) {
            return $this->reportLot($results, $this->lot(), $scope);
        }

        $company = $results->forCompany($scope);

        $this->line('REALIZED TRADING RESULT   ' . $company['scope']);
        $this->line(str_repeat('-', 78));

        if ($company['lots'] === []) {
            $this->warn('No gold trading has been posted to the general ledger in this scope.');
            $this->line('A result read from the books is empty because the books are, which is not the same');
            $this->line('as the company having traded nothing.');

            return self::SUCCESS;
        }

        $this->table(
            ['Deal', 'Revenue', 'Cost of sales', 'Gross', 'Expenses', 'Realized', 'Stage'],
            array_map(fn ($r) => [
                $r['lot_code'],
                Money::format($r['revenue_usd']),
                Money::format($r['cost_of_goods_sold_usd']),
                Money::format($r['gross_profit_usd']),
                Money::format($r['ordinary_expenses_usd']),
                Money::format($r['net_realized_usd']),
                $r['is_final'] ? $r['stage'] : $r['stage'] . ' *',
            ], $company['lots'])
        );

        $this->line('');
        $this->line('  Revenue                 ' . $this->right($company['trading_revenue_usd']));
        $this->line('  Cost of gold sold       ' . $this->right(-$company['trading_cogs_usd']));
        $this->line('  ' . str_repeat('-', 40));
        $this->line('  Gross trading profit    ' . $this->right($company['trading_gross_usd']));
        $this->line('  Deal expenses           ' . $this->right(-$company['trading_expenses_usd']));
        $this->line('  ' . str_repeat('-', 40));
        $this->line('  Realized trading result ' . $this->right($company['trading_result_usd']));

        if (abs($company['unattributed_expenses_usd']) > 0.000001) {
            $this->line('');
            $this->line('  Costs belonging to no deal ' . $this->right(-$company['unattributed_expenses_usd']));
            $this->line('  Operating result           ' . $this->right($company['operating_result_usd']));
        }

        $this->line('');
        $this->line($company['reconciles']
            ? '  Agrees with the ledger: every posting on 4000 and 5000 is accounted for above.'
            : '  DOES NOT AGREE with the ledger. Revenue unattributed to any deal: '
              . Money::exact($company['revenue_unattributed_usd']));

        if (! $company['is_final']) {
            $this->newLine();
            $this->warn('* Not final. ' . implode(', ', $company['interim_lots'])
                . ' may still move; ' . Money::format($company['interim_value_usd'])
                . ' of the figure above is interim.');
        }

        return self::SUCCESS;
    }

    private function reportLot(RealizedTradingResult $results, GoldLot $lot, array $scope): int
    {
        $r = $results->forLot($lot, $scope);

        $this->line('REALIZED RESULT   ' . $r['lot_code']);
        $this->line(str_repeat('-', 60));
        $this->line('  Revenue (4000)          ' . $this->right($r['revenue_usd']));
        $this->line('  Cost of gold sold (5000)' . $this->right(-$r['cost_of_goods_sold_usd']));
        $this->line('  ' . str_repeat('-', 40));
        $this->line('  Gross trading profit    ' . $this->right($r['gross_profit_usd']));

        foreach ($r['expenses_by_account'] as $account) {
            $this->line('  ' . str_pad($account['code'] . ' ' . $account['name'], 24)
                . $this->right(-$account['amount_usd']));
        }

        $this->line('  ' . str_repeat('-', 40));
        $this->line('  Realized net result     ' . $this->right($r['net_realized_usd']));

        $this->newLine();
        $this->table(['', ''], [
            ['Cost basis', Money::format($r['cost_basis_usd'])
                . ' (of which ' . Money::format($r['capitalised_cost_usd']) . ' capitalised)'],
            ['Refined / sold / held', number_format($r['refined_grams'], 4) . ' g / '
                . number_format($r['sold_grams'], 4) . ' g / ' . number_format($r['remaining_grams'], 4) . ' g'],
            ['Gold still held, at cost', Money::format($r['remaining_value_at_cost_usd'])],
            ['Trading complete', $r['trading_complete'] ? 'yes' : 'no'],
            ['Costs declared complete', $r['expenses_finalised'] ? 'yes' : 'no'],
            ['Costs not yet in the ledger', Money::format($r['unposted_costs_usd'])],
            ['Result recorded', $r['result_recorded'] ? 'yes' : 'no'],
            ['Period closed', $r['period_closed'] ? 'yes' : 'no'],
            ['Stage', $r['stage']],
        ]);

        if ($r['qualification']) {
            $this->warn($r['qualification']);
        } elseif ($r['is_final']) {
            $this->info('This figure is final.');
        }

        return self::SUCCESS;
    }

    private function realize(RealizedResultRecorder $recorder, RealizedTradingResult $results): int
    {
        $lot = $this->lot();
        $before = $lot->result()->first();

        $record = $recorder->record($lot, null, array_filter(['notes' => $this->option('note')]));

        if ($before && $before->realized_at !== null) {
            $this->warn('Already recorded on ' . $record->realized_at->format('d M Y H:i')
                . '; nothing was recorded a second time.');
        } else {
            $this->info('Recorded the realized result of ' . $lot->lot_code . '.');
        }

        $this->line('  Revenue        ' . $this->right((float) $record->revenue_usd));
        $this->line('  Cost of sales  ' . $this->right(-(float) $record->cost_of_goods_sold_usd));
        $this->line('  Expenses       ' . $this->right(-(float) $record->expenses_usd));
        $this->line('  Realized       ' . $this->right((float) $record->net_profit_usd));
        $this->line('  Period         ' . ($record->period?->code ?? 'none'));
        $this->newLine();
        $this->line('The figures are now fixed. A cost arriving later is posted to the ledger in the period');
        $this->line('it belongs to; this record is not edited.');
        $this->line('What share of it, if any, is an investor\'s is a separate question and is not decided here.');

        return self::SUCCESS;
    }

    private function finalise(RealizedResultRecorder $recorder): int
    {
        $lot = $this->lot();
        $already = $lot->expensesFinalised();

        $recorder->finaliseExpenses($lot);

        $this->info($already
            ? $lot->lot_code . ' already had its costs declared complete.'
            : 'The costs of ' . $lot->lot_code . ' are declared complete.');

        if (! $already) {
            $this->line('Its result is no longer provisional for want of costs. Record it with: trading realize '
                . $lot->lot_code);
        }

        return self::SUCCESS;
    }

    private function reopen(RealizedResultRecorder $recorder): int
    {
        $lot = $this->lot();
        $recorder->reopenExpenses($lot, (string) $this->option('reason'));

        $this->info('The costs of ' . $lot->lot_code . ' are open again; its result is provisional once more.');

        return self::SUCCESS;
    }

    private function lot(): GoldLot
    {
        if (! $this->argument('lot')) {
            throw new \InvalidArgumentException('Name the deal: trading ' . $this->argument('action') . ' BOR-2026-10-02');
        }

        $lot = GoldLot::where('lot_code', $this->argument('lot'))->first();

        if (! $lot) {
            throw new \InvalidArgumentException('No deal found with code ' . $this->argument('lot') . '.');
        }

        return $lot;
    }

    private function scope(): array
    {
        $scope = array_filter([
            'from' => $this->option('from'),
            'to'   => $this->option('to'),
        ]);

        if ($this->option('period')) {
            $period = AccountingPeriod::where('code', $this->option('period'))->first();

            if (! $period) {
                throw new \InvalidArgumentException('No accounting period with code ' . $this->option('period') . '.');
            }

            $scope['period_id'] = $period->id;
        }

        return $scope;
    }

    private function right(float $amount): string
    {
        return str_pad(Money::format($amount), 16, ' ', STR_PAD_LEFT);
    }
}
