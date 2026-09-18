<?php

namespace App\Accounting\Models;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line as the bank reported it.
 *
 * This is evidence, not accounting: it records what the bank says happened, so
 * it can be held against what the company recorded. Matching a line never
 * changes a journal — if the two disagree, the disagreement is the finding.
 */
class BankStatementLine extends Model
{
    protected $table = 'bank_statement_lines';

    public const UNMATCHED = 'unmatched';
    public const MATCHED   = 'matched';
    public const IGNORED   = 'ignored';

    protected $fillable = [
        'cash_account_id', 'statement_ref', 'value_date', 'description', 'amount',
        'external_ref', 'fingerprint', 'status', 'matched_journal_line_id',
        'matched_by', 'matched_at', 'ignore_reason', 'imported_by',
    ];

    protected $casts = [
        'value_date' => 'date',
        'amount'     => 'float',
        'matched_at' => 'datetime',
    ];

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'cash_account_id');
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(JournalLine::class, 'matched_journal_line_id');
    }

    public function matchedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'matched_by');
    }

    public function isMoneyIn(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Identifies a line well enough that importing the same statement twice
     * does not duplicate it.
     */
    public static function fingerprintFor(int $cashAccountId, string $date, float $amount, string $description, ?string $ref): string
    {
        return hash('sha256', implode('|', [
            $cashAccountId, $date, number_format($amount, 8, '.', ''), trim($description), trim((string) $ref),
        ]));
    }
}
