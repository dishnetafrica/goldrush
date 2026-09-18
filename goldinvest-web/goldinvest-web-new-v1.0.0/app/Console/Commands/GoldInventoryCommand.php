<?php

namespace App\Console\Commands;

use App\Accounting\Services\InventoryValuation;
use App\GoldTrading\Models\GoldLot;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * What gold the company holds, what it cost, and whether the ledger agrees.
 */
class GoldInventoryCommand extends Command
{
    protected $signature = 'gold:inventory {lot? : one lot code, or omit for all}';

    protected $description = 'Report gold inventory by lot, at cost, against the general ledger';

    public function handle(InventoryValuation $valuation): int
    {
        $lots = $this->argument('lot')
            ? GoldLot::where('lot_code', $this->argument('lot'))->get()
            : GoldLot::orderBy('purchase_date')->get();

        if ($lots->isEmpty()) {
            $this->warn('No lots found.');

            return self::SUCCESS;
        }

        $rows = [];
        $glTotal = 0.0;
        $valuedTotal = 0.0;
        $divergences = [];

        foreach ($lots as $lot) {
            $v = $valuation->forLot($lot);
            $gl = round($v['gl_unrefined_usd'] + $v['gl_refined_usd'], 8);

            $glTotal += $gl;
            $valuedTotal += $v['remaining_value_usd'];

            $rows[] = [
                $lot->lot_code,
                number_format($v['refined_grams'], 4),
                number_format($v['sold_grams'], 4),
                number_format($v['remaining_grams'], 4),
                Money::format($v['cost_per_refined_gram']),
                Money::format($v['remaining_value_usd']),
                Money::format($gl),
                abs($gl - $v['remaining_value_usd']) <= 0.005 ? 'agrees' : 'DIFFERS',
            ];

            $divergence = $valuation->divergence($lot);

            if (! $divergence['agrees']) {
                $divergences[] = $lot->lot_code . ': ' . Money::exact($divergence['cogs_difference_usd'])
                    . ' - ' . $divergence['reason'];
            }
        }

        $this->table(
            ['Lot', 'Refined g', 'Sold g', 'Held g', 'Cost/g', 'Held at cost', 'In the ledger', 'Check'],
            $rows
        );

        $this->line('  Inventory at cost : ' . Money::format($valuedTotal));
        $this->line('  General ledger    : ' . Money::format($glTotal));
        $this->line('  Difference        : ' . Money::exact(round($glTotal - $valuedTotal, 8)));

        if ($divergences !== []) {
            $this->newLine();
            $this->warn('Cost of sales differs from the investor-facing deal result:');

            foreach ($divergences as $line) {
                $this->line('  - ' . $line);
            }
        }

        return self::SUCCESS;
    }
}
