<?php

namespace App\Investor\Services;

use App\Investor\Models\InvestorDocument;
use App\Investor\Support\Branding;
use App\Models\Admin\Currency;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Renders documents and files them away.
 *
 * Issuing is once-only. A receipt for a movement is rendered the first time it
 * is asked for, stored on the private disk with a SHA-256 of its bytes, and
 * every later request hands back those same bytes. An investor and the company
 * therefore always hold the identical piece of paper.
 *
 * Statements for a period that has closed are treated the same way. A statement
 * that includes today cannot be, because today is not over, so it is marked
 * INTERIM and re-rendered each time.
 */
class DocumentIssuer
{
    public function __construct(
        private readonly StatementBuilder $statements,
        private readonly ReceiptBuilder $receipts,
        private readonly SequenceAllocator $sequences,
    ) {
    }

    /**
     * A statement for a period. Throws ReconciliationFailed if the figures do
     * not agree with the ledger, in which case no document is produced at all.
     */
    public function statement(User $user, ?string $from = null, ?string $to = null): InvestorDocument
    {
        $data = $this->statements->build($user, $from, $to);
        $interim = $data['period']['interim'];

        if (! $interim) {
            $existing = InvestorDocument::where('user_id', $user->id)
                ->where('type', InvestorDocument::TYPE_STATEMENT)
                ->where('event_reference', $this->periodKey($data))
                ->first();

            if ($existing && $existing->intact()) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($user, $data, $interim) {
            $number = $this->sequences->next('STM', Carbon::now());

            $html = view('investor.documents.statement', [
                'statement' => $data,
                'branding'  => Branding::get(),
                'number'    => $number,
                'interim'   => $interim,
            ])->render();

            return $this->store($user, [
                'type'              => InvestorDocument::TYPE_STATEMENT,
                'document_number'   => $number,
                'title'             => 'Investor Account Statement',
                'event_reference'   => $interim ? $number : $this->periodKey($data),
                'period_start'      => $data['period']['start'],
                'period_end'        => $data['period']['end'] ?? Carbon::now(),
                'currency_code'     => $data['currency'],
                'closing_available' => $data['closing']['available'],
                'closing_profit'    => $data['closing']['profit'],
                'closing_committed' => $data['closing']['committed'],
                'meta'              => [
                    'interim'      => $interim,
                    'entry_count'  => $data['entry_count'],
                    'external_in'  => $data['totals']['external_in'],
                    'external_out' => $data['totals']['external_out'],
                ],
            ], $html);
        });
    }

    /** A receipt for one ledger movement, identified by its reference. */
    public function receipt(User $user, string $reference): InvestorDocument
    {
        $existing = InvestorDocument::where('user_id', $user->id)
            ->where('type', InvestorDocument::TYPE_RECEIPT)
            ->where('event_reference', $reference)
            ->first();

        if ($existing && $existing->intact()) {
            return $existing;
        }

        $data = $this->receipts->build($user, $reference);

        return DB::transaction(function () use ($user, $data, $reference, $existing) {
            $number = $this->sequences->next('RCP', Carbon::now());

            $html = view('investor.documents.receipt', [
                'receipt'  => $data,
                'branding' => Branding::get(),
                'number'   => $number,
            ])->render();

            return $this->store($user, [
                'type'            => InvestorDocument::TYPE_RECEIPT,
                'document_number' => $number,
                'title'           => $data['title'],
                'event_reference' => $reference,
                'trx_id'          => $data['trx_id'],
                'gold_lot_id'     => $data['lot']?->id,
                'currency_code'   => $data['currency'],
                'closing_available' => $data['after']['available'],
                'closing_profit'    => $data['after']['profit'],
                'closing_committed' => $data['after']['committed'],
                'meta'            => ['event' => $data['event'], 'status' => $data['status']],
                'supersedes_document_id' => $existing?->id,
            ], $html);
        });
    }

    /**
     * Renders to PDF, writes it to the private disk and records it.
     *
     * The hash is taken of exactly the bytes that were written, so a later
     * integrity check compares like with like.
     */
    private function store(User $user, array $attributes, string $html): InvestorDocument
    {
        $pdf = Pdf::loadHTML($html)->setPaper('a4')->output();

        $path = $user->id . '/' . $attributes['type'] . 's/' . $attributes['document_number'] . '.pdf';

        Storage::disk(InvestorDocument::DISK)->put($path, $pdf);

        // A superseded receipt keeps its row and its file; the new one points back
        // at it. Nothing is overwritten, so the trail survives.
        if (($attributes['supersedes_document_id'] ?? null) !== null) {
            InvestorDocument::where('id', $attributes['supersedes_document_id'])
                ->update(['revoked_at' => Carbon::now()]);
        }

        return InvestorDocument::create($attributes + [
            'user_id'      => $user->id,
            'file_path'    => $path,
            'file_hash'    => hash('sha256', $pdf),
            'file_bytes'   => strlen($pdf),
            'generated_at' => Carbon::now(),
            'generated_by' => app()->runningInConsole() ? 'console' : 'web',
        ]);
    }

    /** Identifies a closed period so the same statement is not issued twice. */
    private function periodKey(array $data): string
    {
        $start = $data['period']['start']?->toDateString() ?? 'start';
        $end = $data['period']['end']?->toDateString() ?? 'open';

        return 'PERIOD:' . $start . ':' . $end;
    }
}
