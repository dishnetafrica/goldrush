<?php

namespace App\Console\Commands;

use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Services\LotResultCalculator;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Prints the real result of a gold lot: what it cost, what it sold for, what
 * was actually made, and what each investor's share of that would be.
 */
class GoldLotReportCommand extends Command
{
    protected $signature = 'gold:lot-report
                            {lot? : lot code, omit to report every lot}
                            {--investor-share=0 : percentage of net profit owed to investors}';

    protected $description = 'Report cost, proceeds and real profit for gold lots';

    public function handle(LotResultCalculator $calculator): int
    {
        $query = GoldLot::query()->with(['processings', 'sales', 'expenses', 'allocations']);

        if ($code = $this->argument('lot')) {
            $query->where('lot_code', $code);
        }

        $lots = $query->orderBy('purchase_date')->get();

        if ($lots->isEmpty()) {
            $this->warn('No gold lots found.');

            return self::SUCCESS;
        }

        $share = (float) $this->option('investor-share');

        foreach ($lots as $lot) {
            $r = $calculator->forLot($lot);

            $this->line("<options=bold>{$lot->lot_code}</> — {$lot->project_name}");
            $this->line("{$lot->location}   purchased {$lot->purchase_date->toDateString()}");
            $this->newLine();

            $this->table(['Weight', 'Grams'], [
                ['Purchased', $this->g($r['gross_grams'])],
                ['Refining waste (' . $this->n($r['waste_percent'], 2) . '%)', $this->g($r['waste_grams'])],
                ['Refined, in hand', $this->g($r['refined_grams'])],
                ['Sold', $this->g($r['sold_grams'])],
                ['Still in stock', $this->g($r['remaining_grams'])],
            ]);

            $this->table(['Money (USD)', 'Amount'], [
                ['Capital cost of the gold', $this->m($r['cost_usd'])],
                ['Cost per refined gram', $this->m($r['cost_per_refined_gram_usd'], 4)],
                ['Break-even sale price per gram', $this->m($r['break_even_price_per_gram_usd'], 4)],
                ['Sale proceeds', $this->m($r['proceeds_usd'])],
                ['Cost of gold sold', $this->m($r['cost_of_goods_sold_usd'])],
                ['Gross profit', $this->m($r['gross_profit_usd'])],
                ['Expenses recorded', $this->m($r['expenses_usd'])],
                ['<options=bold>Net profit</>', '<options=bold>' . $this->m($r['net_profit_usd']) . '</>'],
                ['Return on capital', $this->n($r['roi_percent'], 2) . ' %'],
                ['Inventory still held, at cost', $this->m($r['remaining_value_at_cost_usd'])],
                ['Investor capital allocated', $this->m($r['allocated_capital_usd'])],
            ]);

            if ($r['expenses_usd'] <= 0) {
                $this->warn('No expenses are recorded against this lot. Transport, refining, security and travel costs will reduce the net profit shown above.');
            }

            if ($share > 0) {
                $split = $calculator->splitProfit($r['net_profit_usd'], $share);
                $rows = [];
                foreach ($calculator->investorBreakdown($lot, $split['investor_profit_usd']) as $line) {
                    $user = User::find($line['user_id']);
                    $rows[] = [
                        $user?->username ?? ('user #' . $line['user_id']),
                        $this->m($line['amount_usd']),
                        $this->n($line['share_percent'], 4) . ' %',
                        $this->m($line['profit_usd']),
                    ];
                }
                $rows[] = ['<options=bold>Company retains</>', '', '', '<options=bold>' . $this->m($split['company_profit_usd']) . '</>'];
                $this->line("Profit split at {$share}% to investors:");
                $this->table(['Investor', 'Capital', 'Share', 'Profit'], $rows);
            } else {
                $this->line('Pass --investor-share=<percent> to see how the net profit would be split.');
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function g(float $v): string
    {
        return number_format($v, 4) . ' g';
    }

    private function m(float $v, int $dp = 2): string
    {
        return number_format($v, $dp);
    }

    private function n(float $v, int $dp): string
    {
        return number_format($v, $dp);
    }
}
