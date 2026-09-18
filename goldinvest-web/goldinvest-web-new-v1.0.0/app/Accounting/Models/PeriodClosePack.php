<?php

namespace App\Accounting\Models;

use App\Accounting\Exceptions\AccountingException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * The PDF pack for one period close.
 *
 * A reporting artifact, not an accounting record: it says what the books
 * showed when it was generated, and the books remain the authority. It is
 * immutable because a snapshot that can be edited is not a snapshot, and it
 * is hashed so that the file on disk can be proved to be the one recorded.
 */
class PeriodClosePack extends Model
{
    protected $table = 'period_close_packs';

    public const DISK = 'accounting-private';

    protected $fillable = [
        'reference', 'accounting_period_id', 'close_reference', 'file_path', 'file_hash', 'file_bytes',
        'controls', 'controls_passed', 'sections', 'generated_by', 'generated_at',
    ];

    protected $casts = [
        'controls'        => 'array',
        'sections'        => 'array',
        'controls_passed' => 'boolean',
        'file_bytes'      => 'integer',
        'generated_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $pack) {
            throw new AccountingException('Close pack ' . $pack->reference . ' has been generated and cannot be altered.');
        });

        static::deleting(function (self $pack) {
            throw new AccountingException('Close pack ' . $pack->reference . ' cannot be deleted.');
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function contents(): string
    {
        return Storage::disk(self::DISK)->get($this->file_path);
    }

    /** Whether the file on disk is still byte-for-byte the one that was recorded. */
    public function intact(): bool
    {
        if (! Storage::disk(self::DISK)->exists($this->file_path)) {
            return false;
        }

        return hash('sha256', $this->contents()) === $this->file_hash;
    }
}
