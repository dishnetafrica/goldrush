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
     * Split a lot's result between its investors and the company, using the terms
     * agreed for that deal. Terms are never assumed: an investor is paid on their
     * own agreed percentage if one is recorded, otherwise on the lot's percentage,
     * otherwise on the override passed in. With none of those, nothing is split.
     *
     * Two different percentages are at work and must not be confused:
     *   capital_share_percent - how much of the lot's capital this investor funded
     *   profit_share_percent  - the agreed cut of the profit their capital earned
     *
     * Expense policy:
     *   deal_before_split - expenses reduce net profit first, so both sides carry
     *                       them in proportion to the split (the default)
     *   company_share     - investors are paid out of profit before expenses, and
     *                       the company absorbs every cost from its own share
     *
     * @return array{
     *   applied_expense_policy:string, profit_pool_usd:float,
     *   investor_profit_usd:float, company_profit_usd:float,
     *   investors:array<int,array{user_id:int,capital_usd:float,capital_share_percent:float,profit_share_percent:float,profit_usd:float}>,
     *   missing_terms:bool
     * }
     */
    public function splitResult(GoldLot $lot, array $result, ?float $overrideSharePercent = null): array
    {
        $lot->loadMissing('allocations');

        $policy = $lot->expense_policy ?: GoldLot::EXPENSES_DEAL_BEFORE_SPLIT;

        // What the investors' cut is calculated from.
        $pool = $policy === GoldLot::EXPENSES_COMPANY_SHARE
            ? (float) $result['gross_profit_usd']
            : (float) $result['net_profit_usd'];

        $totalCapital = (float) $lot->allocations->sum('amount_usd');
        $investors = [];
        $investorTotal = 0.0;
        $missingTerms = false;

        foreach ($lot->allocations as $allocation) {
            $capitalShare = $totalCapital > 0
                ? $this->round(((float) $allocation->amount_usd / $totalCapital) * 100, 6)
                : 0.0;

            $profitShare = $allocation->share_percent
                ?? $lot->investor_share_percent
                ?? $overrideSharePercent;

            if ($profitShare === null) {
                $missingTerms = true;
                $profitShare = 0.0;
            }

            $profit = $this->round($pool * ($capitalShare / 100) * ((float) $profitShare / 100), 8);
            $investorTotal += $profit;

            $investors[] = [
                'user_id'                => (int) $allocation->user_id,
                'capital_usd'            => (float) $allocation->amount_usd,
                'capital_share_percent'  => $capitalShare,
                'profit_share_percent'   => (float) $profitShare,
                'profit_usd'             => $profit,
            ];
        }

        $investorTotal = $this->round($investorTotal, 8);

        return [
            'applied_expense_policy' => $policy,
            'profit_pool_usd'        => $this->round($pool, 8),
            'investor_profit_usd'    => $investorTotal,
            // The company always carries the expenses under company_share.
            'company_profit_usd'     => $this->round((float) $result['net_profit_usd'] - $investorTotal, 8),
            'investors'              => $investors,
            'missing_terms'          => $missingTerms,
        ];
    }

    private function round(float $value, int $precision): float
    {
        return round($value, $precision);
    }
}
