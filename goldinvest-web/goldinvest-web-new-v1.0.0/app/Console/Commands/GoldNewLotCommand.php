<?php

namespace App\Console\Commands;

use App\GoldTrading\Models\GoldLot;
use Illuminate\Console\Command;

/**
 * Records a gold purchase — any deal, not just the one the Boromedina sheet
 * described. This is the first step of every batch: without a lot there is
 * nothing to allocate capital to, refine, sell, or pay out.
 *
 * The price is given in whatever currency the gold was actually paid for in,
 * together with the exchange rate on the purchase day, because that is how the
 * field records it. The USD figures are derived, never typed in twice.
 */
class GoldNewLotCommand extends Command
{
    protected $signature = 'gold:new-lot
                            {code : short unique code for this deal, e.g. BOR-2026-10-02}
                            {--date= : purchase date, YYYY-MM-DD (default today)}
                            {--project= : project name}
                            {--location= : where the gold was bought}
                            {--supplier= : who it was bought from}
                            {--grams= : gross grams purchased}
                            {--currency=USD : currency actually paid in, e.g. SSP}
                            {--price-per-gram= : price per gram in that currency}
                            {--fx=1 : units of that currency per 1 USD on the purchase day}
                            {--purity=unrefined : purity as received}
                            {--reference-rate= : international rate per gram that day, for context}
                            {--note= }';

    protected $description = 'Record a gold purchase (a new deal)';

    public function handle(): int
    {
        $code = trim($this->argument('code'));

        if (GoldLot::where('lot_code', $code)->exists()) {
            $this->error('A lot with code ' . $code . ' already exists.');

            return self::FAILURE;
        }

        $grams = (float) $this->option('grams');
        $pricePerGramLocal = (float) $this->option('price-per-gram');
        $fx = (float) $this->option('fx');

        foreach (['grams' => $grams, 'price-per-gram' => $pricePerGramLocal, 'fx' => $fx] as $name => $value) {
            if ($value <= 0) {
                $this->error('--' . $name . ' must be greater than zero.');

                return self::FAILURE;
            }
        }

        $currency = strtoupper((string) $this->option('currency'));
        $pricePerGramUsd = $pricePerGramLocal / $fx;
        $totalCostLocal  = $grams * $pricePerGramLocal;
        $totalCostUsd    = $totalCostLocal / $fx;

        $lot = GoldLot::create([
            'lot_code'             => $code,
            'purchase_date'        => $this->option('date') ?: now()->toDateString(),
            'project_name'         => $this->option('project'),
            'location'             => $this->option('location'),
            'supplier_name'        => $this->option('supplier'),
            'purity_in'            => $this->option('purity'),
            'gross_grams'          => $grams,
            'purchase_currency'    => $currency,
            'price_per_gram_local' => $pricePerGramLocal,
            'fx_rate_to_usd'       => $fx,
            'price_per_gram_usd'   => $pricePerGramUsd,
            'total_cost_local'     => $totalCostLocal,
            'total_cost_usd'       => $totalCostUsd,
            'reference_rate_note'  => $this->option('reference-rate'),
            'status'               => 'purchased',
            'notes'                => $this->option('note'),
        ]);

        $this->newLine();
        $this->info('Recorded deal ' . $lot->lot_code);
        $this->table(['Field', 'Value'], [
            ['Purchase date', $lot->purchase_date->format('d M Y')],
            ['Gross grams', number_format($grams, 4)],
            ['Price per gram', number_format($pricePerGramLocal, 2) . ' ' . $currency],
            ['FX rate', number_format($fx, 4) . ' ' . $currency . ' per USD'],
            ['Price per gram (USD)', number_format($pricePerGramUsd, 4)],
            ['Total cost', number_format($totalCostLocal, 2) . ' ' . $currency],
            ['Total cost (USD)', number_format($totalCostUsd, 2)],
        ]);

        $this->newLine();
        $this->line('Next steps for this deal:');
        $this->line('  gold:allocate ' . $code . ' --investor=<username> --amount=<usd>');
        $this->line('  gold:refine   ' . $code . ' --waste-percent=<n>');
        $this->line('  gold:sell     ' . $code . ' --grams=<n> --price-per-gram=<usd>');
        $this->line('  gold:expense  --lot=' . $code . ' --category=transport --amount=<usd> --description="..."');
        $this->line('  gold:set-terms ' . $code . ' --investor-share=<n>');
        $this->line('  gold:lot-report ' . $code);
        $this->line('  gold:distribute ' . $code);

        return self::SUCCESS;
    }
}
