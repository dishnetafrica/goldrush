<?php

namespace App\Accounting\Models;

use App\Accounting\Exceptions\AccountingException;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record that somebody checked the company's books against the bank's, on a
 * date, and what they found.
 *
 * Completed reconciliations are kept forever and never edited. A later check is
 * a new reconciliation, so the history shows what was believed at each point
 * rather than only the latest opinion.
 */
class BankReconciliation extends Model
{
    protected $table = 'bank_reconciliations';

    public const DRAFT     = 'draft';
    public const COMPLETED = 'completed';

    protected $fillable = [
        'reference', 'cash_account_id', 'as_at', 'statement_closing_balance',
        'ledger_balance', 'difference', 'lines_total', 'lines_matched',
        'lines_unmatched', 'lines_ignored', 'status', 'performed_by',
        'completed_by', 'completed_at', 'notes', 'snapshot',
        'sod_exception', 'sod_exception_reason',
    ];

    protected $casts = [
        'as_at'                    => 'date',
        'statement_closing_balance' => 'float',
        'ledger_balance'           => 'float',
        'difference'               => 'float',
        'completed_at'             => 'datetime',
        'sod_exception'            => 'boolean',
        'snapshot'                 => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $reconciliation) {
            if ($reconciliation->getOriginal('status') === self::COMPLETED) {
                throw new AccountingException(
                    'Reconciliation ' . $reconciliation->reference . ' is completed and cannot be changed. '
                    . 'Perform a new reconciliation instead.'
                );
            }
        });

        static::deleting(function (self $reconciliation) {
            throw new AccountingException(
                'Reconciliation ' . $reconciliation->reference . ' cannot be deleted.'
            );
        });
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'cash_account_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'completed_by');
    }

    public function reconciles(): bool
    {
        return abs($this->difference) <= 0.00000001;
    }
}
