<?php

namespace App\Http\Controllers\User;

use App\GoldTrading\Models\CapitalAllocation;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\ProfitDistribution;
use App\GoldTrading\Services\LotResultCalculator;
use App\Http\Controllers\Controller;
use App\Investor\Models\LedgerEntry;
use App\Investor\Services\StatementBuilder;

/**
 * The deals an investor's capital has been attributed to, and what each one did.
 *
 * Deal pages describe the company's trading result and the investor's share of
 * it. They never suggest the investor holds gold: the gold is the company's,
 * and the investor's position is a USD balance.
 */
class InvestorDealController extends Controller
{
    public function index(StatementBuilder $builder)
    {
        $page_title = __('My Deals');
        $breadcrumb = __('My Deals');

        // Built by the same code that fills the statement, so the page and the
        // PDF always show the same deals with the same figures.
        $statement = $builder->build(auth()->user());
        $deals = $statement['deals'];

        return view('investor.pages.deals', compact('page_title', 'breadcrumb', 'deals'));
    }

    public function show(string $lot, LotResultCalculator $calculator)
    {
        $user = auth()->user();

        $goldLot = GoldLot::where('lot_code', $lot)->firstOrFail();

        $allocation = CapitalAllocation::where('gold_lot_id', $goldLot->id)
            ->where('user_id', $user->id)
            ->first();

        // An investor may only open a deal their own capital funded.
        abort_unless($allocation !== null, 404);

        $result = $calculator->forLot($goldLot);
        $distribution = ProfitDistribution::where('gold_lot_id', $goldLot->id)
            ->where('user_id', $user->id)
            ->first();

        $movements = LedgerEntry::forUser($user->id)
            ->where('gold_lot_id', $goldLot->id)
            ->chronological()
            ->get()
            ->groupBy('group_uuid');

        $page_title = __('Deal') . ' ' . $goldLot->lot_code;
        $breadcrumb = __('Deal');

        return view('investor.pages.deal', compact(
            'page_title', 'breadcrumb', 'goldLot', 'allocation', 'result', 'distribution', 'movements'
        ));
    }
}
