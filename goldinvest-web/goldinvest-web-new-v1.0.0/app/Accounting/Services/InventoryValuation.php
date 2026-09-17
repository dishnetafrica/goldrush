<?php

namespace App\Accounting\Services;

use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldSale;
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
 * rather than expensed (decision D4). That is where this and
 * LotResultCalculator part company: the calculator treats processing charges as
 * a deal expense. The two agree while those charges are zero, and divergence()
 * reports the gap rather than letting it pass unnoticed.
 */
class InventoryValuation
{
    public const UNREFINED = '1100';
    public const REFINED   = '1110';

    public function __construct(private readonly LotResultCalculator $calculator)
    {
    }

    public function forLot(GoldLot $lot): array
    {
        $lot->loadMissing(['processings', 'sales']);

        $grossGrams = (float) $lot->gross_grams;
        $wasteGrams = (float) $lot->processings->sum('waste_grams');
        $refinedGrams = round($grossGrams - $wasteGrams, 4);

        $capitalised = (float) $lot->processings->where('cost_capitalised', true)->sum('cost_usd');
        $costBasis = round((float) $lot->total_cost_usd + $capitalised, 8);

        $costPerRefinedGram = $refinedGrams > 0 ? round($costBasis / $refinedGrams, 8) : 0.0;

        $settled = $lot->sales->where('status', GoldSale::STATUS_SETTLED);
        $soldGrams = (float) $settled->sum('grams_sold');
        $remainingGrams = round($refinedGrams - $soldGrams, 4);

        // When a lot is sold out, the last sale carries whatever cost is left
        // rather than a rounded multiple, so nothing is stranded in inventory.
        $cogs = $soldGrams > 0
            ? ($remainingGrams <= 0 ? $costBasis : round($costPerRefinedGram * $soldGrams, 8))
            : 0.0;

        return [
            'lot'                   => $lot,
            'gross_grams'           => $grossGrams,
            'waste_grams'           => $wasteGrams,
            'refined_grams'         => $refinedGrams,
            'sold_grams'            => $soldGrams,
            'remaining_grams'       => $remainingGrams,
            'purchase_cost_usd'     => round((float) $lot->total_cost_usd, 8),
            'capitalised_cost_usd'  => round($capitalised, 8),
            'cost_basis_usd'        => $costBasis,
            'cost_per_refined_gram' => $costPerRefinedGram,
            'cogs_to_date_usd'      => $cogs,
            'remaining_value_usd'   => round($costBasis - $cogs, 8),
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
     * Where this valuation and the investor-facing deal result disagree, and why.
     *
     * Capitalised processing costs sit in inventory here and in expenses there.
     * While they are zero the two agree exactly. When they are not, the deal's
     * net profit is the same either way once a lot is fully sold, but the split
     * between cost of goods sold and expenses differs — and so does the value of
     * anything still unsold.
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
                : 'Processing costs are capitalised into inventory here and treated as deal expenses by '
                  . 'LotResultCalculator. Align the two before closing a period on this lot.',
        ];
    }
}
