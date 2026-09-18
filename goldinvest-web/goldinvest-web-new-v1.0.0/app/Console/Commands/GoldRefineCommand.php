<?php

namespace App\Console\Commands;

use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldProcessing;
use Illuminate\Console\Command;

/**
 * Records refining. Waste is the part that matters for the money: grams burnt off
 * are paid for but never sold, so they raise the cost of every gram that survives.
 * The calculator handles that; this command only has to record it honestly.
 */
class GoldRefineCommand extends Command
{
    protected $signature = 'gold:refine
                            {lot : lot code}
                            {--date= : date refined, YYYY-MM-DD (default today)}
                            {--input-grams= : grams put in (default the lot\'s gross grams)}
                            {--waste-percent= : percentage lost in refining}
                            {--waste-grams= : grams lost, if you know the weight instead of the percentage}
                            {--method=Cleaning to 24KT}
                            {--purity=24K : purity that came out}
                            {--cost= : refining cost in USD, if charged separately}
                            {--note= }';

    protected $description = 'Record refining of a lot, including waste';

    public function handle(): int
    {
        $lot = GoldLot::where('lot_code', $this->argument('lot'))->first();

        if (! $lot) {
            $this->error('No lot found with code ' . $this->argument('lot'));

            return self::FAILURE;
        }

        $input = $this->option('input-grams') !== null
            ? (float) $this->option('input-grams')
            : (float) $lot->gross_grams;

        if ($input <= 0) {
            $this->error('There are no grams to refine.');

            return self::FAILURE;
        }

        if ($this->option('waste-grams') !== null) {
            $wasteGrams = (float) $this->option('waste-grams');
            $wastePercent = $wasteGrams / $input * 100;
        } elseif ($this->option('waste-percent') !== null) {
            $wastePercent = (float) $this->option('waste-percent');
            $wasteGrams = $input * $wastePercent / 100;
        } else {
            $this->error('Give the loss: --waste-percent=8 or --waste-grams=2');

            return self::FAILURE;
        }

        if ($wasteGrams < 0 || $wasteGrams > $input) {
            $this->error('The waste must be between 0 and the grams put in.');

            return self::FAILURE;
        }

        $output = $input - $wasteGrams;

        $processing = GoldProcessing::create([
            'gold_lot_id'   => $lot->id,
            'processed_at'  => $this->option('date') ?: now()->toDateString(),
            'method'        => $this->option('method'),
            'input_grams'   => $input,
            'waste_grams'   => $wasteGrams,
            'waste_percent' => $wastePercent,
            'output_grams'  => $output,
            'output_purity' => $this->option('purity'),
            'cost_usd'      => (float) ($this->option('cost') ?? 0),
            'notes'         => $this->option('note'),
        ]);

        $lot->update(['status' => 'in_stock']);

        $costPerRefinedGram = $output > 0 ? (float) $lot->total_cost_usd / $output : 0;

        $this->newLine();
        $this->info('Recorded refining for ' . $lot->lot_code);
        $this->table(['Field', 'Value'], [
            ['Grams in', number_format($input, 4)],
            ['Waste', number_format($wasteGrams, 4) . ' g  (' . number_format($wastePercent, 2) . ' %)'],
            ['Grams out', number_format($output, 4)],
            ['Cost per refined gram', number_format($costPerRefinedGram, 4) . ' USD'],
        ]);

        $this->newLine();
        $this->warn('Break-even: this lot must sell above ' . number_format($costPerRefinedGram, 2)
            . ' USD per gram before expenses, because the waste is paid for but never sold.');

        return self::SUCCESS;
    }
}
