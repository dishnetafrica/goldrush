<?php

namespace App\GoldTrading\Services;

use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldSale;

/**
 * Works out what a gold lot actually earned.
 *
 * The rule this class exists to enforce: proceeds are not profit. Profit is
 * proceeds minus the cost of the gold actually sold, minus the costs of doing
 * the deal. Waste is a loss of grams, so it raises the cost of every gram that
 * survives refining; it is not a separate expense line.
 */
class LotResultCalculator
{
    /**
     * @return array<string,float|string> keys: gross_grams, waste_grams, refined_grams,
     *  sold_grams, remaining_grams, cost_usd, cost_per_refined_gram_usd, cost_of_goods_sold_usd,
     *  proceeds_usd, expenses_usd, gross_profit_usd, net_profit_usd, roi_percent,
     *  break_even_price_per_gram_usd, remaining_value_at_cost_usd, allocated_capital_usd
     */
    public function forLot(GoldLot $lot): array
    {
        $lot->loadMissing(['processings', 'sales', 'expenses', 'allocations']);

        $grossGrams = (float) $lot->gross_grams;
        $wasteGrams = (float) $lot->processings->sum('waste_grams');
        $refinedGrams = $this->round($grossGrams - $wasteGrams, 4);

        $settledSales = $lot->sales->where('status', GoldSale::STATUS_SETTLED);
        $soldGrams = (float) $settledSales->sum('grams_sold');
        $remainingGrams = $this->round($refinedGrams - $soldGrams, 4);

        $costUsd = (float) $lot->total_cost_usd;

        // Cost basis per gram AFTER refining loss. 2,000 USD over 23 g is 86.96/g, not 80/g.
        $costPerRefinedGram = $refinedGrams > 0 ? $this->round($costUsd / $refinedGrams, 8) : 0.0;

        // Only the gold actually sold is charged to profit. Unsold grams stay as inventory.
        $cogsUsd = $soldGrams > 0
            ? ($remainingGrams <= 0 ? $costUsd : $this->round($costPerRefinedGram * $soldGrams, 8))
            : 0.0;

        $proceedsUsd = (float) $settledSales->sum('gross_proceeds_usd');

        // Refining charges plus every expense booked against this lot or its sales.
        $saleIds = $lot->sales->pluck('id')->all();
        $expensesUsd = (float) $lot->processings->sum('cost_usd')
            + (float) $lot->expenses->sum('amount_usd')
            + (float) \App\GoldTrading\Models\TradingExpense::query()
                ->whereIn('gold_sale_id', $saleIds ?: [0])
                ->whereNull('gold_lot_id')
                ->sum('amount_usd');

        $grossProfitUsd = $this->round($proceedsUsd - $cogsUsd, 8);
        $netProfitUsd = $this->round($grossProfitUsd - $expensesUsd, 8);

        $roiPercent = $costUsd > 0 ? $this->round(($netProfitUsd / $costUsd) * 100, 4) : 0.0;

        // What a gram must fetch to cover cost and expenses.
        $breakEven = $refinedGrams > 0
            ? $this->round(($costUsd + $expensesUsd) / $refinedGrams, 8)
            : 0.0;

        return [
            'lot_code'                      => $lot->lot_code,
            'gross_grams'                   => $grossGrams,
            'waste_grams'                   => $wasteGrams,
            'waste_percent'                 => $grossGrams > 0 ? $this->round(($wasteGrams / $grossGrams) * 100, 4) : 0.0,
            'refined_grams'                 => $refinedGrams,
            'sold_grams'                    => $soldGrams,
            'remaining_grams'               => $remainingGrams,
            'cost_usd'                      => $this->round($costUsd, 8),
            'cost_per_refined_gram_usd'     => $costPerRefinedGram,
            'cost_of_goods_sold_usd'        => $cogsUsd,
            'proceeds_usd'                  => $this->round($proceedsUsd, 8),
            'expenses_usd'                  => $this->round($expensesUsd, 8),
            'gross_profit_usd'              => $grossProfitUsd,
            'net_profit_usd'                => $netProfitUsd,
            'roi_percent'                   => $roiPercent,
            'break_even_price_per_gram_usd' => $breakEven,
            'remaining_value_at_cost_usd'   => $this->round($costPerRefinedGram * max($remainingGrams, 0), 8),
            'allocated_capital_usd'         => $this->round((float) $lot->allocations->sum('amount_usd'), 8),
        ];
    }

    /**
     * Split a lot's net profit between investors and the company.
     * The share is a policy decision and must be supplied, never assumed.
     */
    public function splitProfit(float $netProfitUsd, float $investorSharePercent): array
    {
        $investor = $this->round($netProfitUsd * ($investorSharePercent / 100), 8);

        return [
            'investor_profit_usd' => $investor,
            'company_profit_usd'  => $this->round($netProfitUsd - $investor, 8),
        ];
    }

    /**
     * Each investor's slice of the investor pool, in proportion to the capital they funded.
     *
     * @return array<int,array{user_id:int,amount_usd:float,share_percent:float,profit_usd:float}>
     */
    public function investorBreakdown(GoldLot $lot, float $investorProfitUsd): array
    {
        $lot->loadMissing('allocations');
        $total = (float) $lot->allocations->sum('amount_usd');

        if ($total <= 0) {
            return [];
        }

        $rows = [];
        foreach ($lot->allocations as $allocation) {
            $sharePercent = $this->round(((float) $allocation->amount_usd / $total) * 100, 6);
            $rows[] = [
                'user_id'       => (int) $allocation->user_id,
                'amount_usd'    => (float) $allocation->amount_usd,
                'share_percent' => $sharePercent,
                'profit_usd'    => $this->round($investorProfitUsd * ($sharePercent / 100), 8),
            ];
        }

        return $rows;
    }

    private function round(float $value, int $precision): float
    {
        return round($value, $precision);
    }
}
