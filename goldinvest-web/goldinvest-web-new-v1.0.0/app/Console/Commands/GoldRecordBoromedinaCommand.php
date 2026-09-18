<?php

namespace App\Console\Commands;

use App\GoldTrading\Models\CapitalAllocation;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldProcessing;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Services\LotResultCalculator;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Records the Boromedina (Western Bahr El Ghazal, South Sudan) deal of 08-09-2026
 * exactly as it appears in the source spreadsheet, so the numbers the system
 * produces can be compared line by line with the sheet.
 *
 * Idempotent: re-running updates the same lot instead of duplicating it.
 */
class GoldRecordBoromedinaCommand extends Command
{
    protected $signature = 'gold:record-boromedina
                            {--investor= : username or email of the investor whose capital funded the lot}
                            {--trx= : transactions.trx_id that brought the capital in}
                            {--lot=BOR-2026-09-08 : lot code to use}';

    protected $description = 'Record the Boromedina 08-09-2026 gold deal from the source spreadsheet';

    // Figures taken straight from the spreadsheet.
    private const PURCHASE_DATE      = '2026-09-08';
    private const LOCATION           = 'Boromedina, Western Bahr El Ghazal State, South Sudan';
    private const PRICE_PER_GRAM_SSP = 600000.0;   // C10
    private const FX_SSP_PER_USD     = 7500.0;     // C7
    private const CAPITAL_USD        = 2000.0;     // C15
    private const WASTE_PERCENT      = 8.0;        // implied by C20 = C18 * 8 / 100
    private const INTL_RATE_USD      = 142.19;     // C6
    private const SALE_PRICE_USD     = 127.97;     // C25, international less 10%
    private const SALE_LOCATION      = 'Juba, South Sudan';

    public function handle(LotResultCalculator $calculator): int
    {
        $investor = $this->resolveInvestor();
        if (! $investor) {
            return self::FAILURE;
        }

        $lotCode = (string) $this->option('lot');

        // Derived exactly as the spreadsheet derives them.
        $capitalLocal   = self::CAPITAL_USD * self::FX_SSP_PER_USD;            // 15,000,000 SSP
        $grossGrams     = $capitalLocal / self::PRICE_PER_GRAM_SSP;            // 25.00 g
        $wasteGrams     = $grossGrams * (self::WASTE_PERCENT / 100);           // 2.00 g
        $refinedGrams   = $grossGrams - $wasteGrams;                           // 23.00 g
        $priceUsdPerG   = self::PRICE_PER_GRAM_SSP / self::FX_SSP_PER_USD;     // 80.00 USD/g
        $proceedsUsd    = self::SALE_PRICE_USD * $refinedGrams;                // 2,943.31 USD

        DB::beginTransaction();
        try {
            $lot = GoldLot::updateOrCreate(
                ['lot_code' => $lotCode],
                [
                    'purchase_date'        => self::PURCHASE_DATE,
                    'project_name'         => 'Gold Purchase Project - Boromedina',
                    'location'             => self::LOCATION,
                    'supplier_name'        => null,
                    'purity_in'            => 'unrefined',
                    'gross_grams'          => $grossGrams,
                    'purchase_currency'    => 'SSP',
                    'price_per_gram_local' => self::PRICE_PER_GRAM_SSP,
                    'fx_rate_to_usd'       => self::FX_SSP_PER_USD,
                    'price_per_gram_usd'   => $priceUsdPerG,
                    'total_cost_local'     => $capitalLocal,
                    'total_cost_usd'       => self::CAPITAL_USD,
                    'reference_rate_note'  => 'International rate at purchase: ' . self::INTL_RATE_USD . ' USD/g',
                    'status'               => GoldLot::STATUS_SOLD,
                    'notes'                => 'Recorded from the source spreadsheet dated ' . self::PURCHASE_DATE . '.',
                ]
            );

            $lot->processings()->delete();
            GoldProcessing::create([
                'gold_lot_id'   => $lot->id,
                'processed_at'  => self::PURCHASE_DATE,
                'method'        => 'Cleaning to 24KT',
                'input_grams'   => $grossGrams,
                'waste_grams'   => $wasteGrams,
                'waste_percent' => self::WASTE_PERCENT,
                'output_grams'  => $refinedGrams,
                'output_purity' => '24K',
                'cost_usd'      => 0,
                'notes'         => 'Waste taken at ' . self::WASTE_PERCENT . '% of purchased weight, per the spreadsheet.',
            ]);

            GoldSale::updateOrCreate(
                ['sale_code' => $lotCode . '-S1'],
                [
                    'gold_lot_id'          => $lot->id,
                    'sale_date'            => self::PURCHASE_DATE,
                    'buyer_name'           => null,
                    'location'             => self::SALE_LOCATION,
                    'grams_sold'           => $refinedGrams,
                    'reference_rate_usd'   => self::INTL_RATE_USD,
                    'discount_percent'     => 10,
                    'price_basis'          => 'International rate less 10%',
                    'price_per_gram_usd'   => self::SALE_PRICE_USD,
                    'gross_proceeds_usd'   => $proceedsUsd,
                    'settlement_currency'  => 'USD',
                    'fx_rate_to_usd'       => 1,
                    'gross_proceeds_local' => $proceedsUsd,
                    'status'               => GoldSale::STATUS_SETTLED,
                ]
            );

            CapitalAllocation::updateOrCreate(
                ['gold_lot_id' => $lot->id, 'user_id' => $investor->id],
                [
                    'source_trx_id' => $this->option('trx'),
                    'amount_usd'    => self::CAPITAL_USD,
                    'allocated_at'  => self::PURCHASE_DATE,
                    'status'        => 'allocated',
                    'notes'         => 'Investor capital deployed into this lot. No wallet balance was changed.',
                ]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed to record the deal: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Lot {$lotCode} recorded, funded by {$investor->username} ({$investor->email}).");
        $this->newLine();
        $this->call('gold:lot-report', ['lot' => $lotCode]);

        return self::SUCCESS;
    }

    private function resolveInvestor(): ?User
    {
        $needle = $this->option('investor');

        if ($needle) {
            $user = User::where('username', $needle)->orWhere('email', $needle)->first();
            if (! $user) {
                $this->error("No user matches --investor={$needle}");

                return null;
            }

            return $user;
        }

        if (User::count() === 1) {
            return User::first();
        }

        $this->error('Several users exist. Pass --investor=<username|email> to say whose capital funded this lot.');

        return null;
    }
}
