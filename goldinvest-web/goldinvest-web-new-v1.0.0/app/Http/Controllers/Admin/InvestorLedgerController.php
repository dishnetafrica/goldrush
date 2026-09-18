<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Investor\Exceptions\ReconciliationFailed;
use App\Investor\Models\InvestorDocument;
use App\Investor\Models\LedgerEntry;
use App\Investor\Services\DocumentIssuer;
use App\Investor\Services\LedgerReconciler;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The admin view of an investor's account.
 *
 * Read and issue only. There is deliberately no way from here to edit a ledger
 * entry or a document: corrections are posted as new movements by the ledger
 * commands, which record why they were made. An admin who could quietly rewrite
 * history would undo the point of keeping it.
 */
class InvestorLedgerController extends Controller
{
    public function index(Request $request, LedgerReconciler $reconciler)
    {
        $page_title = __('Investor Ledgers');

        $search = $request->string('search')->toString();

        $users = User::query()
            ->when($search, fn ($q) => $q->where('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->whereIn('id', LedgerEntry::select('user_id')->distinct())
            ->orderBy('id')
            ->paginate(15);

        // Reconciliation status per investor, so a problem is visible from the
        // list rather than only when someone opens the account.
        $status = [];

        foreach ($users as $user) {
            $report = $reconciler->forUser($user);
            $status[$user->id] = [
                'passed'   => $report['passed'],
                'position' => $report['position'],
                'total'    => $report['total'],
            ];
        }

        return view('admin.sections.investor-ledger.index', compact('page_title', 'users', 'status', 'search'));
    }

    public function show(int $userId, LedgerReconciler $reconciler)
    {
        $user = User::findOrFail($userId);
        $page_title = __('Investor Ledger') . ' - ' . $user->username;

        $report = $reconciler->forUser($user);

        $entries = LedgerEntry::forUser($user->id)->chronological()->get();

        $documents = InvestorDocument::where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $transactions = DB::table('transactions')
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('receiver_id', $user->id))
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('admin.sections.investor-ledger.show', compact(
            'page_title', 'user', 'report', 'entries', 'documents', 'transactions'
        ));
    }

    public function statement(int $userId, Request $request, DocumentIssuer $issuer)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        try {
            $document = $issuer->statement($user, $validated['from'] ?? null, $validated['to'] ?? null);
        } catch (ReconciliationFailed $e) {
            // The admin, unlike the investor, is shown exactly what is wrong.
            return back()->with(['error' => array_merge(
                [__('No statement was produced: this account does not reconcile.')],
                $e->failures
            )]);
        }

        return back()->with(['success' => [__('Issued') . ' ' . $document->document_number]]);
    }

    public function receipts(int $userId, DocumentIssuer $issuer)
    {
        $user = User::findOrFail($userId);

        $references = LedgerEntry::forUser($user->id)->pluck('reference')->unique();
        $issued = 0;

        foreach ($references as $reference) {
            try {
                $issuer->receipt($user, $reference);
                $issued++;
            } catch (\Throwable) {
                // Skip the one that failed and carry on; the list page shows what exists.
            }
        }

        return back()->with(['success' => [__('Receipts available for') . ' ' . $issued . ' ' . __('movements')]]);
    }

    public function download(int $documentId)
    {
        $document = InvestorDocument::findOrFail($documentId);

        abort_unless(Storage::disk(InvestorDocument::DISK)->exists($document->file_path), 404);

        return Storage::disk(InvestorDocument::DISK)->download(
            $document->file_path,
            $document->document_number . '.pdf'
        );
    }
}
