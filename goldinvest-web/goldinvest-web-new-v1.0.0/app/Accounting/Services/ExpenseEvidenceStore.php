<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Security\AccountingPermission;
use App\GoldTrading\Models\TradingExpense;
use App\Models\Admin\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Where the proof of a cost is kept.
 *
 * On a private disk, under storage/, never anywhere a web server will serve.
 * An expense receipt can carry a bank account number, a supplier's terms, a
 * signature or a passport page, and none of that becomes public merely because
 * it was attached to an expense claim. Reading one goes through here so that
 * the permission check cannot be forgotten at a call site.
 *
 * The same discipline the investor documents follow, for the same reason: the
 * bytes are hashed when they are filed, so an altered or missing file is
 * detectable rather than merely unlucky.
 */
class ExpenseEvidenceStore
{
    /**
     * File evidence against a draft expense.
     *
     * Only against a draft: a claim is submitted together with its proof, and
     * after that the paperwork stops changing. Swapping the receipt under a
     * claim somebody has already approved would defeat the point of approving
     * it.
     */
    public function attach(
        TradingExpense $expense,
        string $contents,
        string $filename,
        ?string $mime = null,
        ?Admin $actor = null,
    ): TradingExpense {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_CREATE);

        $status = $expense->status ?? TradingExpense::STATUS_DRAFT;

        if ($status !== TradingExpense::STATUS_DRAFT) {
            throw new PostingRefused(
                'Expense ' . $expense->label() . ' is ' . $status
                . '; evidence is attached while the claim is still a draft.'
            );
        }

        $bytes = strlen($contents);

        if ($bytes === 0) {
            throw new PostingRefused('That evidence file is empty.');
        }

        $limit = (int) config('accounting.expenses.max_evidence_bytes', 10485760);

        if ($bytes > $limit) {
            throw new PostingRefused(
                'That evidence file is ' . number_format($bytes / 1048576, 1) . ' MB, over the '
                . number_format($limit / 1048576, 1) . ' MB limit.'
            );
        }

        $mime = $mime ?: $this->sniff($contents);
        $allowed = (array) config('accounting.expenses.evidence_mime_types', []);

        if ($allowed !== [] && ! in_array($mime, $allowed, true)) {
            throw new PostingRefused(
                'Evidence of type "' . $mime . '" is not accepted. A receipt is a PDF or a photograph of one.'
            );
        }

        $path = $this->pathFor($expense, $filename);

        Storage::disk(TradingExpense::EVIDENCE_DISK)->put($path, $contents);

        $expense->forceFill([
            'evidence_path'        => $path,
            'evidence_filename'    => $this->safeName($filename),
            'evidence_mime'        => $mime,
            'evidence_bytes'       => $bytes,
            'evidence_hash'        => hash('sha256', $contents),
            'evidence_uploaded_by' => $actor?->id,
            'evidence_uploaded_at' => Carbon::now(),
        ])->save();

        return $expense;
    }

    /**
     * Hand back the filed bytes.
     *
     * The permission check lives here rather than at the call sites, because a
     * call site that forgets it is how a private document becomes a public one.
     */
    public function read(TradingExpense $expense, ?Admin $actor = null): string
    {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_VIEW);

        if (! $expense->hasEvidence()) {
            throw new PostingRefused('Expense ' . $expense->label() . ' has no evidence attached.');
        }

        if (! Storage::disk(TradingExpense::EVIDENCE_DISK)->exists($expense->evidence_path)) {
            throw new PostingRefused(
                'The evidence filed against ' . $expense->label() . ' is no longer on disk.'
            );
        }

        return Storage::disk(TradingExpense::EVIDENCE_DISK)->get($expense->evidence_path);
    }

    /** Whether this admin may read this evidence at all. */
    public function canRead(TradingExpense $expense, ?Admin $actor): bool
    {
        return AccountingPermission::allows($actor, AccountingPermission::EXPENSE_VIEW);
    }

    /** The absolute directory evidence lives in, for checking it is not public. */
    public function root(): string
    {
        return (string) config('filesystems.disks.' . TradingExpense::EVIDENCE_DISK . '.root');
    }

    private function pathFor(TradingExpense $expense, string $filename): string
    {
        $reference = $expense->reference ?: 'EXP-' . $expense->id;
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'bin';

        return $expense->expense_date->format('Y') . '/' . $reference . '/'
            . Str::random(16) . '.' . preg_replace('/[^a-z0-9]/', '', $extension);
    }

    /** A filename fit to be stored and shown, rather than whatever was uploaded. */
    private function safeName(string $filename): string
    {
        $name = basename(str_replace('\\', '/', $filename));

        return Str::limit(preg_replace('/[^A-Za-z0-9._ -]/', '_', $name) ?: 'evidence', 180, '');
    }

    private function sniff(string $contents): string
    {
        $info = new \finfo(FILEINFO_MIME_TYPE);

        return $info->buffer($contents) ?: 'application/octet-stream';
    }
}
