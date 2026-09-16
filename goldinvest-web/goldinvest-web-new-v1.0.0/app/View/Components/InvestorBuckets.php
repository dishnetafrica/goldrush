<?php

namespace App\View\Components;

use App\Investor\Ledger\Bucket;
use App\Investor\Models\LedgerEntry;
use App\Investor\Services\LedgerReconciler;
use Illuminate\View\Component;

/**
 * The three-bucket summary shown on the investor dashboard.
 *
 * It reads the ledger, not the wallet, and it reads it through the reconciler:
 * if the account does not reconcile the component shows that it is being
 * checked rather than showing figures nobody has verified. The same rule that
 * governs statements governs what appears on screen.
 */
class InvestorBuckets extends Component
{
    public array $position;
    public float $total;
    public bool $verified;
    public int $openDeals;

    public function __construct(LedgerReconciler $reconciler)
    {
        $user = auth()->user();
        $report = $reconciler->forUser($user);

        $this->verified = $report['passed'];
        $this->position = $report['position'];
        $this->total = $report['total'];

        $this->openDeals = LedgerEntry::forUser($user->id)
            ->where('bucket', Bucket::COMMITTED)
            ->where('amount_usd', '>', 0)
            ->whereNotNull('gold_lot_id')
            ->count();
    }

    public function render()
    {
        return view('components.investor-buckets');
    }
}
