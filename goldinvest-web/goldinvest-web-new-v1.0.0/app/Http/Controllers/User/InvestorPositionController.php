<?php

namespace App\Http\Controllers\User;

use App\Accounting\Reports\InvestorLiabilityReport;
use App\Accounting\Reports\ReportAudit;
use App\Accounting\Reports\ReportScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * The investor's own position: capital, profit, movements, distributions
 * received, withdrawals, statements. Their row only. Nothing about any other
 * investor, the company's ledger, its cash, its gold or its costs is passed
 * to this page, so nothing of the kind can appear on it.
 */
class InvestorPositionController extends Controller
{
    public function index(Request $request, InvestorLiabilityReport $report, ReportAudit $audit)
    {
        $user = auth()->user();

        $validated = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $scope = ReportScope::fromInput(['from' => $validated['from'] ?? null, 'to' => $validated['to'] ?? null]);

        $position = $report->forInvestor($user, $scope);
        $audit->record('investor-position', ['user' => $user->id] + $position['scope'], [], true, $user);

        return view('investor.pages.position', [
            'page_title' => __('My Position'), 'breadcrumb' => __('My Position'), 'p' => $position, 'input' => $validated,
        ]);
    }
}
