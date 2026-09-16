<?php

namespace App\Console\Commands;

use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Services\LotResultCalculator;
use Illuminate\Console\Command;

/**
 * Records a sale out of a lot. A lot can be sold in several goes, so this adds a
 * sale rather than replacing one; the calculator only ever charges the grams
 * actually sold against profit and leaves the rest valued at cost.
 */
class GoldSellCommand extends Command
{
    protected $signature = 'gold:sell
                            {lot : lot code}
                            {--code= : sale code (default derived from the lot)}
                            {--date= : sale date, YYYY-MM-DD (default today)}
                            {--grams= : grams sold}
                            {--all : sell everything still held in this lot}
                            {--price-per-gram= : USD actually received per gram}
                            {--reference-rate= : international rate per gram used as the basis}
                            {--discount= : discount off the reference rate, in percent}
                            {--buyer= }
                            {--location= }
                            {--note= }';

    protected $description = 'Record a sale of gold out of a lot';

    public function handle(LotResultCalculator $calculator): int
    {
        $lot = GoldLot::where('lot_code', $this->argument('lot'))->first();

        if (! $lot) {
            $this->error('No lot found with code ' . $this->argument('lot'));

            return self::FAILURE;
        }

        $result = $calculator->forLot($lot);
        $remaining = (float) $result['remaining_grams'];

        $grams = $this->option('all') ? $remaining : (float) $this->option('grams');

        if ($grams <= 0) {
            $this->error('Give the weight sold: --grams=23.92, or --all.');

            return self::FAILURE;
        }

        if ($grams > $remaining + 0.0001) {
            $this->error('This lot only holds ' . number_format($remaining, 4) . ' g of unsold gold.');

            return self::FAILURE;
        }

        // The price is either stated outright, or derived from the international
        // rate less the discount that was negotiated.
        if ($this->option('price-per-gram') !== null) {
            $price = (float) $this->option('price-per-gram');
        } elseif ($this->option('reference-rate') !== null && $this->option('discount') !== null) {
            $price = (float) $this->option('reference-rate') * (1 - (float) $this->option('discount') / 100);
        } else {
            $this->error('Give the price: --price-per-gram=127.97, or --reference-rate=142.19 --discount=10');

            return self::FAILURE;
        }

        if ($price <= 0) {
            $this->error('The price per gram must be greater than zero.');

            return self::FAILURE;
        }

        $proceeds = $grams * $price;
        $saleCount = GoldSale::where('gold_lot_id', $lot->id)->count();
        $code = $this->option('code') ?: $lot->lot_code . '-S' . ($saleCount + 1);

        if (GoldSale::where('sale_code', $code)->exists()) {
            $this->error('A sale with code ' . $code . ' already exists.');

            return self::FAILURE;
        }

        GoldSale::create([
            'sale_code'          => $code,
            'gold_lot_id'        => $lot->id,
            'sale_date'          => $this->option('date') ?: now()->toDateString(),
            'buyer_name'         => $this->option('buyer'),
            'location'           => $this->option('location'),
            'grams_sold'         => $grams,
            'reference_rate_usd' => $this->option('reference-rate') !== null ? (float) $this->option('reference-rate') : null,
            'discount_percent'   => $this->option('discount') !== null ? (float) $this->option('discount') : null,
            'price_basis'        => $this->option('discount') !== null
                ? 'International less ' . $this->option('discount') . '%'
                : null,
            'price_per_gram_usd' => $price,
            'gross_proceeds_usd' => $proceeds,
            'settlement_currency' => 'USD',
            'fx_rate_to_usd'     => 1,
            'status'             => 'settled',
            'notes'              => $this->option('note'),
        ]);

        $stillHeld = $remaining - $grams;
        $lot->update(['status' => $stillHeld > 0.0001 ? 'partially_sold' : 'sold']);

        $this->newLine();
        $this->info('Recorded sale ' . $code);
        $this->table(['Field', 'Value'], [
            ['Grams sold', number_format($grams, 4)],
            ['Price per gram', number_format($price, 4) . ' USD'],
            ['Proceeds', number_format($proceeds, 2) . ' USD'],
            ['Still held', number_format($stillHeld, 4) . ' g'],
        ]);

        $this->newLine();
        $this->line('Run  gold:report ' . $lot->lot_code . '  to see the profit this leaves.');

        return self::SUCCESS;
    }
}
