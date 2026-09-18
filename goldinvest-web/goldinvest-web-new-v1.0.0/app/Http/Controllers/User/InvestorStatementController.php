<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Investor\Exceptions\ReconciliationFailed;
use App\Investor\Models\InvestorDocument;
use App\Investor\Services\DocumentIssuer;
use App\Investor\Services\StatementBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * The investor's statements page: see the current position, pick a period,
 * generate a statement.
 *
 * Every figure shown here comes from the same StatementBuilder that produces
 * the PDF, so the screen and the document cannot disagree.
 */
class InvestorStatementController extends Controller
{
    public function index(StatementBuilder $builder)
    {
        $user = auth()->user();
        $page_title = __('Statements');
        $breadcrumb = __('Statements');

        $statement = null;
        $error = null;

        try {
            $statement = $builder->build($user);
        } catch (ReconciliationFailed $e) {
            // The investor is told the truth — that we will not show figures we
            // cannot stand behind — without being handed the internal detail.
            Log::error('Statement refused for ' . $user->username . ': ' . $e->getMessage());
            $error = __('Your statement is temporarily unavailable while your account is being checked. Please contact support.');
        }

        $documents = InvestorDocument::where('user_id', $user->id)
            ->where('type', InvestorDocument::TYPE_STATEMENT)
            ->whereNull('revoked_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('investor.pages.statements', compact('page_title', 'breadcrumb', 'statement', 'error', 'documents'));
    }

    public function store(Request $request, DocumentIssuer $issuer)
    {
        $validated = $request->validate([
            'period' => 'required|string|in:current,month,year,custom',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
        ]);

        [$from, $to] = $this->resolvePeriod($validated);

        try {
            $document = $issuer->statement(auth()->user(), $from, $to);
        } catch (ReconciliationFailed $e) {
            Log::error('Statement refused for ' . auth()->user()->username . ': ' . $e->getMessage());

            return back()->with(['error' => [__('Your statement could not be produced because your account is being checked. Please contact support.')]]);
        }

        return redirect()->route('user.documents.download', $document->id);
    }

    private function resolvePeriod(array $validated): array
    {
        return match ($validated['period']) {
            'month'  => [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->toDateString()],
            'year'   => [Carbon::now()->startOfYear()->toDateString(), Carbon::now()->endOfYear()->toDateString()],
            'custom' => [$validated['from'] ?? null, $validated['to'] ?? null],
            default  => [null, null],
        };
    }
}
