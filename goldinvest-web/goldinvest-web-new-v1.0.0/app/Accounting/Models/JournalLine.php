<?php

namespace App\Accounting\Models;

use App\Accounting\Exceptions\AccountingException;
use App\GoldTrading\Models\GoldLot;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One side of one journal.
 *
 * Never changes and never disappears. A line that turned out to be wrong is
 * answered by a reversing journal, so the original and its correction both sit
 * in the account's history.
 */
class JournalLine extends Model
{
    protected $table = 'journal_lines';

    protected $fillable = [
        'journal_id', 'line_no', 'account_id', 'debit', 'credit',
        'memo', 'gold_lot_id', 'user_id',
    ];

    protected $casts = [
        'debit'    => 'float',
        'credit'   => 'float',
        'line_no'  => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $line) {
            throw new AccountingException(
                'Journal lines are immutable. Line #' . $line->id . ' belongs to a posted journal; '
                . 'post a reversing journal instead.'
            );
        });

        static::deleting(function (self $line) {
            throw new AccountingException(
                'Journal lines are immutable. Line #' . $line->id . ' cannot be deleted.'
            );
        });
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GoldLot::class, 'gold_lot_id');
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Positive for a debit, negative for a credit. */
    public function signedAmount(): float
    {
        return round($this->debit - $this->credit, 8);
    }
}
