<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Investor\Models\InvestorDocument;
use App\Investor\Models\LedgerEntry;
use App\Investor\Services\DocumentIssuer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves investor documents.
 *
 * Files live on a private disk and are streamed through this controller after
 * an authorisation check. No document is ever reachable by URL alone, which is
 * the difference between this and the vendor's uploads under public/.
 */
class InvestorDocumentController extends Controller
{
    public function index()
    {
        $page_title = __('Documents');
        $breadcrumb = __('Documents');

        $documents = InvestorDocument::where('user_id', auth()->id())
            ->whereNull('revoked_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('investor.pages.documents', compact('page_title', 'breadcrumb', 'documents'));
    }

    public function download(InvestorDocument $document): StreamedResponse
    {
        // Ownership is checked here and nowhere else matters: changing the id in
        // the URL to someone else's document gets a 403, not their statement.
        $this->authorize('download', $document);

        abort_unless(Storage::disk(InvestorDocument::DISK)->exists($document->file_path), 404);

        return Storage::disk(InvestorDocument::DISK)->download(
            $document->file_path,
            $document->document_number . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Issues (or returns) the receipt for one of the investor's own movements.
     *
     * The reference is looked up scoped to the signed-in investor, so quoting
     * someone else's reference finds nothing rather than issuing their receipt.
     */
    public function receipt(string $reference, DocumentIssuer $issuer)
    {
        $user = auth()->user();

        $owns = LedgerEntry::forUser($user->id)->where('reference', $reference)->exists();

        abort_unless($owns, 404);

        $document = $issuer->receipt($user, $reference);

        return redirect()->route('user.documents.download', $document->id);
    }
}
