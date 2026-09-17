<?php

namespace App\GoldTrading\Models;

use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\Journal;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A cost the company has incurred: a deal cost or an operating one.
 *
 * An expense here is a claim that passes through a lifecycle before it becomes
 * money. Draft is editable because nobody has looked at it yet. Once submitted
 * it is evidence of what somebody asserted, so it stops being editable and the
 * only things that may change it are the workflow's own steps, each of which
 * records who took it. Once posted it is in the general ledger and nothing can
 * change it at all; a mistake is corrected by reversing it and raising the
 * replacement, so both remain visible.
 *
 * Two facts are kept apart deliberately. Posting says the cost has been
 * recognised in the books; payment says the money has actually left. An expense
 * posted against accrued expenses payable is a real cost that has not been
 * paid, and collapsing the two would hide what the company owes.
 *
 * Decision D4 lives on the `capitalised` flag. A cost incurred to bring gold to
 * a saleable condition is part of what the gold cost: it is debited to
 * inventory, it enters LotCostBasis, and it is never recognised as an expense
 * as well. Everything else is an expense of the period it falls in.
 */
class TradingExpense extends Model
{
    protected $table = 'gold_trading_expenses';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_POSTED    = 'posted';
    public const STATUS_REVERSED  = 'reversed';

    /** Entered before the workflow existed. A fact, but never approved by anybody. */
    public const STATUS_RECORDED = 'recorded';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PAID   = 'paid';

    /** Where evidence lives: a private disk, never under the web root. */
    public const EVIDENCE_DISK = 'expense-private';

    public const CATEGORIES = [
        'transport', 'security', 'travel', 'refining', 'assay',
        'packaging', 'storage', 'commission', 'other', 'operating',
    ];

    /**
     * What may still change once an expense has left draft, by the state it is
     * in. Anything not listed is refused — including by an honest mistake, which
     * is the point of listing them.
     */
    private const MUTABLE_AFTER = [
        self::STATUS_SUBMITTED => [
            'status', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at',
            'rejection_reason', 'sod_exception', 'sod_exception_reason',
        ],
        self::STATUS_APPROVED => [
            'status', 'journal_id', 'posted_by', 'posted_at', 'capitalised_into',
            'paid_from_cash_account_id', 'payment_status', 'payment_journal_id', 'paid_by', 'paid_at',
        ],
        self::STATUS_POSTED => [
            'status', 'payment_status', 'payment_journal_id', 'paid_by', 'paid_at',
            'paid_from_cash_account_id', 'reversed_by_expense_id', 'reversal_reason',

            // A reclassification moves the cost between accounts and records the
            // journal that moved it. The amount never changes; only where it sits.
            'category', 'capitalised', 'capitalised_into', 'notes',
        ],
        self::STATUS_RECORDED => [
            'status', 'journal_id', 'posted_by', 'posted_at', 'capitalised_into',
            'paid_from_cash_account_id', 'payment_status', 'payment_journal_id', 'paid_by', 'paid_at',
            'reversed_by_expense_id', 'reversal_reason',
        ],
        self::STATUS_REJECTED => [],
        self::STATUS_REVERSED => [],
    ];

    protected $fillable = [
        'reference', 'status', 'expense_date', 'category', 'description', 'payee_name',
        'external_ref', 'currency_code', 'amount_local', 'fx_rate_to_usd', 'amount_usd',
        'capitalised', 'capitalised_into', 'gold_lot_id', 'gold_sale_id', 'recorded_by',
        'journal_id', 'paid_from_cash_account_id', 'notes',
        'evidence_path', 'evidence_filename', 'evidence_mime', 'evidence_bytes',
        'evidence_hash', 'evidence_uploaded_by', 'evidence_uploaded_at',
        'submitted_by', 'submitted_at', 'approved_by', 'approved_at',
        'rejected_by', 'rejected_at', 'rejection_reason',
        'sod_exception', 'sod_exception_reason', 'posted_by', 'posted_at',
        'payment_status', 'payment_journal_id', 'paid_by', 'paid_at',
        'reverses_expense_id', 'reversed_by_expense_id', 'reversal_reason',
    ];

    protected $casts = [
        'expense_date'         => 'date',
        'amount_local'         => 'float',
        'fx_rate_to_usd'       => 'float',
        'amount_usd'           => 'float',
        'capitalised'          => 'boolean',
        'sod_exception'        => 'boolean',
        'evidence_bytes'       => 'integer',
        'evidence_uploaded_at' => 'datetime',
        'submitted_at'         => 'datetime',
        'approved_at'          => 'datetime',
        'rejected_at'          => 'datetime',
        'posted_at'            => 'datetime',
        'paid_at'              => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $expense) {
            $was = $expense->getOriginal('status') ?? self::STATUS_DRAFT;

            // A draft is a working document. Nobody has relied on it yet.
            if ($was === self::STATUS_DRAFT) {
                return;
            }

            $changed = array_diff(array_keys($expense->getDirty()), ['updated_at']);
            $allowed = self::MUTABLE_AFTER[$was] ?? [];
            $refused = array_diff($changed, $allowed);

            if ($refused !== []) {
                throw new PostingRefused(
                    'Expense ' . ($expense->reference ?? '#' . $expense->id) . ' is ' . $was
                    . ' and cannot be altered (' . implode(', ', $refused) . '). '
                    . ($was === self::STATUS_POSTED
                        ? 'Reverse it and raise a replacement instead.'
                        : 'Raise a correction rather than editing it.')
                );
            }
        });

        static::deleting(function (self $expense) {
            $status = $expense->status ?? self::STATUS_DRAFT;

            if ($status !== self::STATUS_DRAFT) {
                throw new PostingRefused(
                    'Expense ' . ($expense->reference ?? '#' . $expense->id) . ' is ' . $status
                    . ' and cannot be deleted. Only a draft nobody has seen can be thrown away.'
                );
            }
        });
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GoldLot::class, 'gold_lot_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(GoldSale::class, 'gold_sale_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function paymentJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'payment_journal_id');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'paid_from_cash_account_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /** Whether this cost is in the general ledger. */
    public function isPosted(): bool
    {
        return $this->journal_id !== null && $this->status !== self::STATUS_REVERSED;
    }

    /**
     * Whether this cost counts as an expense of the deal it belongs to.
     *
     * A capitalised cost does not: it is part of what the gold cost, and
     * counting it here as well would charge the same money twice (D4). Rejected
     * and reversed claims do not either, because they are not costs at all.
     */
    public function countsAsDealExpense(): bool
    {
        return ! $this->capitalised
            && ! in_array($this->status, [self::STATUS_REJECTED, self::STATUS_REVERSED], true);
    }

    /**
     * Whether this cost is part of a lot's cost basis.
     *
     * Only once it is posted. Until then the general ledger does not hold it in
     * inventory either, and the two agreeing is what decision D4 is for.
     */
    public function countsInCostBasis(): bool
    {
        return $this->capitalised && $this->status === self::STATUS_POSTED;
    }

    public function hasEvidence(): bool
    {
        return $this->evidence_path !== null;
    }

    /** Whether the stored evidence is still the bytes that were filed. */
    public function evidenceIntact(): bool
    {
        if (! $this->hasEvidence() || ! Storage::disk(self::EVIDENCE_DISK)->exists($this->evidence_path)) {
            return false;
        }

        return hash('sha256', Storage::disk(self::EVIDENCE_DISK)->get($this->evidence_path)) === $this->evidence_hash;
    }

    public function label(): string
    {
        return ($this->reference ?? '#' . $this->id) . ' ' . $this->description;
    }
}
