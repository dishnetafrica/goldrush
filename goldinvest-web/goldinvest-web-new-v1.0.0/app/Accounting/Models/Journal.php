<?php

namespace App\Accounting\Models;

use App\Accounting\Exceptions\AccountingException;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One posted journal entry.
 *
 * Immutable once written. The only field that may change afterwards is the pair
 * that records it has been reversed, and even that is set by posting a second
 * journal rather than by editing this one. Everything else — the date, the memo,
 * the lines, the amounts — is what it was at the moment it was posted.
 */
class Journal extends Model
{
    protected $table = 'journals';

    public const POSTED   = 'posted';
    public const REVERSED = 'reversed';

    /** The only columns that may be touched after posting, and only to record a reversal. */
    private const MUTABLE_AFTER_POST = ['status', 'reversed_by_journal_id', 'updated_at'];

    protected $fillable = [
        'reference', 'journal_date', 'accounting_period_id', 'memo',
        'source_type', 'source_id', 'status', 'posted_by', 'posted_at',
        'reverses_journal_id', 'reversed_by_journal_id', 'reversal_reason', 'meta',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posted_at'    => 'datetime',
        'meta'         => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $journal) {
            $changed = array_keys($journal->getDirty());
            $illegal = array_diff($changed, self::MUTABLE_AFTER_POST);

            if ($illegal !== []) {
                throw new AccountingException(
                    'Journal ' . $journal->reference . ' has been posted and cannot be changed ('
                    . implode(', ', $illegal) . '). Post a reversing journal instead.'
                );
            }
        });

        static::deleting(function (self $journal) {
            throw new AccountingException(
                'Journal ' . $journal->reference . ' has been posted and cannot be deleted. '
                . 'Post a reversing journal instead.'
            );
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_id')->orderBy('line_no');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'posted_by');
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_journal_id');
    }

    public function totalDebit(): float
    {
        return round((float) $this->lines->sum('debit'), 8);
    }

    public function totalCredit(): float
    {
        return round((float) $this->lines->sum('credit'), 8);
    }

    public function balances(): bool
    {
        return abs($this->totalDebit() - $this->totalCredit()) <= 0.00000001;
    }

    public function isReversed(): bool
    {
        return $this->status === self::REVERSED || $this->reversed_by_journal_id !== null;
    }

    public function isReversal(): bool
    {
        return $this->reverses_journal_id !== null;
    }
}
