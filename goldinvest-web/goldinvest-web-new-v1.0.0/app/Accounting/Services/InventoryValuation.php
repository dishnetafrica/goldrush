<?php

namespace App\Accounting\Services;

use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Services\LotCostBasis;
use App\GoldTrading\Services\LotResultCalculator;
use Illuminate\Support\Facades\DB;

/**
 * What a lot of gold cost, what it is worth at cost, and what the ledger says.
 *
 * Grams are lost in refining; dollars are not. The cost simply attaches to
 * fewer grams, which is why a lot bought at 80.00 a gram carries 86.96 a gram
 * after an 8% loss. Booking the lost grams as an expense would charge that cost
 * twice, once through the raised unit cost and again as a write-off.
 *
 * Directly attributable processing costs are added to the cost of the gold
 * rather than expensed (decision D4). Both this and LotResultCalculator read
 * that cost from LotCostBasis, so they cannot disagree about it; divergence()
 * remains as a standing check that they are still wired to the same source.
 */
class InventoryValuation
{
    public const UNREFINED = '1100';
    public const REFINED   = '1110';

    public function __construct(
        private readonly LotCostBasis $costBasis,
        private readonly LotResultCalculator $calculator,
    ) {
    }

    public function forLot(GoldLot $lot): array
    {
        // The same cost basis the investor-facing deal result uses. Two systems
        // that must agree are given one thing to read rather than two formulas
        // to keep in step.
        $basis = $this->costBasis->forLot($lot);

        return [
            'lot'                   => $lot,
            'gross_grams'           => $basis['gross_grams'],
            'waste_grams'           => $basis['waste_grams'],
            'refined_grams'         => $basis['refined_grams'],
            'sold_grams'            => $basis['sold_grams'],
            'remaining_grams'       => $basis['remaining_grams'],
            'purchase_cost_usd'     => $basis['purchase_cost_usd'],
            'capitalised_cost_usd'  => $basis['capitalised_cost_usd'],
            'cost_basis_usd'        => $basis['cost_basis_usd'],
            'cost_per_refined_gram' => $basis['cost_per_refined_gram_usd'],
            'cogs_to_date_usd'      => $basis['cost_of_goods_sold_usd'],
            'remaining_value_usd'   => $basis['remaining_value_at_cost_usd'],
            'gl_unrefined_usd'      => $this->glBalance($lot, self::UNREFINED),
            'gl_refined_usd'        => $this->glBalance($lot, self::REFINED),
        ];
    }

    /** What the general ledger holds for this lot in one inventory account. */
    public function glBalance(GoldLot $lot, string $accountCode): float
    {
        $sums = DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('accounts.code', $accountCode)
            ->where('journal_lines.gold_lot_id', $lot->id)
            ->selectRaw('SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c')
            ->first();

        return round((float) ($sums->d ?? 0) - (float) ($sums->c ?? 0), 8);
    }

    /** Inventory the ledger holds for a lot, refined and unrefined together. */
    public function glInventory(GoldLot $lot): float
    {
        return round($this->glBalance($lot, self::UNREFINED) + $this->glBalance($lot, self::REFINED), 8);
    }

    /**
     * Proves this valuation and the investor-facing deal result still agree.
     *
     * They read the same cost basis, so a difference here means something has
     * been rewired to compute its own — the failure this check exists to catch
     * before it reaches a period close.
     */
    public function divergence(GoldLot $lot): array
    {
        $valuation = $this->forLot($lot);
        $result = $this->calculator->forLot($lot);

        $cogsGap = round($valuation['cogs_to_date_usd'] - (float) $result['cost_of_goods_sold_usd'], 8);

        return [
            'capitalised_cost_usd' => $valuation['capitalised_cost_usd'],
            'gl_cogs_usd'          => $valuation['cogs_to_date_usd'],
            'calculator_cogs_usd'  => round((float) $result['cost_of_goods_sold_usd'], 8),
            'cogs_difference_usd'  => $cogsGap,
            'agrees'               => abs($cogsGap) <= 0.00000001,
            'reason'               => abs($cogsGap) <= 0.00000001
                ? null
                : 'Cost of sales differs between the ledger and the deal result. They are meant to read '
                  . 'the same cost basis, so one of them has been rewired. Resolve before closing a period.',
        ];
    }
}
