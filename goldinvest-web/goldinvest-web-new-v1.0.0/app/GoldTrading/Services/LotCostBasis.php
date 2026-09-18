<?php

namespace App\GoldTrading\Services;

use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldSale;

/**
 * What a lot of gold cost, in one place.
 *
 * Both the company's general ledger and the investor-facing deal result have to
 * agree about this, and the only reliable way to make two things agree is to
 * give them one thing to read. Everything about a lot's cost — the basis, the
 * cost per surviving gram, the cost of what has been sold, the value of what
 * remains — is computed here and nowhere else.
 *
 * Two rules decide the numbers.
 *
 * Grams are lost in refining; dollars are not. The cost attaches to fewer
 * grams, so a lot bought at 80.00 a gram carries 86.96 after an 8% loss. The
 * lost grams are never written off, because that would charge the same cost
 * twice.
 *
 * Costs incurred to bring the gold to a saleable condition are part of what the
 * gold cost (decision D4). They belong in inventory, not in the period's
 * expenses, and having entered inventory they must never be counted as an
 * expense again. Costs that do not prepare inventory — transport, security,
 * travel — stay expenses and never touch this.
 */
class LotCostBasis
{
    /**
     * @return array{
     *   gross_grams:float, waste_grams:float, refined_grams:float,
     *   purchase_cost_usd:float, capitalised_cost_usd:float,
     *   capitalised_processing_usd:float, capitalised_expenses_usd:float, cost_basis_usd:float,
     *   cost_per_refined_gram_usd:float, sold_grams:float, remaining_grams:float,
     *   cost_of_goods_sold_usd:float, remaining_value_at_cost_usd:float,
     *   expensed_processing_usd:float
     * }
     */
    public function forLot(GoldLot $lot): array
    {
        $lot->loadMissing(['processings', 'sales', 'expenses']);

        $grossGrams = (float) $lot->gross_grams;
        $wasteGrams = (float) $lot->processings->sum('waste_grams');
        $refinedGrams = round($grossGrams - $wasteGrams, 4);

        // Only processing charges marked as capitalised become part of the gold's
        // cost. Anything else a processor charged for stays an expense.
        $capitalisedProcessing = (float) $lot->processings->where('cost_capitalised', true)->sum('cost_usd');
        $expensedProcessing = (float) $lot->processings->where('cost_capitalised', false)->sum('cost_usd');

        // Costs claimed through the expense workflow and marked as capitalised
        // join the cost of the gold too, but only once they have been posted.
        // Until then the general ledger does not hold them in inventory either,
        // and this basis agreeing with the ledger is the whole point of it.
        $capitalisedExpenses = (float) $lot->expenses
            ->filter(fn ($expense) => $expense->countsInCostBasis())
            ->sum('amount_usd');

        $capitalised = round($capitalisedProcessing + $capitalisedExpenses, 8);

        $purchaseCost = (float) $lot->total_cost_usd;
        $costBasis = round($purchaseCost + $capitalised, 8);

        $costPerRefinedGram = $refinedGrams > 0 ? round($costBasis / $refinedGrams, 8) : 0.0;

        $settled = $lot->sales->where('status', GoldSale::STATUS_SETTLED);
        $soldGrams = (float) $settled->sum('grams_sold');
        $remainingGrams = round($refinedGrams - $soldGrams, 4);

        // A lot sold out carries whatever cost is left rather than a rounded
        // multiple, so nothing is stranded in inventory by the eighth decimal.
        $cogs = $soldGrams > 0
            ? ($remainingGrams <= 0 ? $costBasis : round($costPerRefinedGram * $soldGrams, 8))
            : 0.0;

        return [
            'gross_grams'                 => $grossGrams,
            'waste_grams'                 => $wasteGrams,
            'refined_grams'               => $refinedGrams,
            'purchase_cost_usd'           => round($purchaseCost, 8),
            'capitalised_cost_usd'        => $capitalised,
            'capitalised_processing_usd'  => round($capitalisedProcessing, 8),
            'capitalised_expenses_usd'    => round($capitalisedExpenses, 8),
            'cost_basis_usd'              => $costBasis,
            'cost_per_refined_gram_usd'   => $costPerRefinedGram,
            'sold_grams'                  => $soldGrams,
            'remaining_grams'             => $remainingGrams,
            'cost_of_goods_sold_usd'      => $cogs,
            'remaining_value_at_cost_usd' => round($costBasis - $cogs, 8),
            'expensed_processing_usd'     => round($expensedProcessing, 8),
        ];
    }
}
