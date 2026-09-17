<?php

namespace App\Investor\Models;

use App\Investor\Exceptions\LedgerException;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * An issued statement or receipt.
 *
 * Immutable once written. Correcting a document means issuing a new one that
 * supersedes it, never editing the old one — the same discipline the ledger
 * itself follows, for the same reason.
 */
class InvestorDocument extends Model
{
    protected $table = 'investor_documents';

    public const TYPE_STATEMENT = 'statement';
    public const TYPE_RECEIPT   = 'receipt';

    public const DISK = 'investor-private';

    protected $fillable = [
        'user_id', 'type', 'document_number', 'title', 'event_reference', 'trx_id',
        'gold_lot_id', 'period_start', 'period_end', 'file_path', 'file_hash',
        'file_bytes', 'currency_code', 'closing_available', 'closing_profit',
        'closing_committed', 'meta', 'generated_at', 'generated_by',
        'revoked_at', 'supersedes_document_id', 'supersede_seq',
    ];

    protected $casts = [
        'period_start'      => 'date',
        'period_end'        => 'date',
        'generated_at'      => 'datetime',
        'revoked_at'        => 'datetime',
        'meta'              => 'array',
        'file_bytes'        => 'integer',
        'closing_available' => 'float',
        'closing_profit'    => 'float',
        'closing_committed' => 'float',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $document) {
            // Only the revocation stamp may be set after issue; the document's own
            // content and identity never change.
            $changed = array_keys($document->getDirty());

            if (array_diff($changed, ['revoked_at', 'supersede_seq', 'updated_at']) !== []) {
                throw new LedgerException(
                    'Document ' . $document->document_number . ' has been issued and cannot be altered. '
                    . 'Issue a superseding document instead.'
                );
            }
        });

        static::deleting(function (self $document) {
            throw new LedgerException(
                'Document ' . $document->document_number . ' has been issued and cannot be deleted.'
            );
        });
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contents(): string
    {
        return Storage::disk(self::DISK)->get($this->file_path);
    }

    /** Whether the stored file is still the one that was issued. */
    public function intact(): bool
    {
        if (! Storage::disk(self::DISK)->exists($this->file_path)) {
            return false;
        }

        return hash('sha256', $this->contents()) === $this->file_hash;
    }
}
