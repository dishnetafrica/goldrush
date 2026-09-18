<?php

namespace App\Accounting\Models;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A cash box or a bank account.
 *
 * Its balance is the balance of the GL account it owns. There is no second
 * figure kept anywhere, so there is nothing that can disagree.
 */
class CashAccount extends Model
{
    protected $table = 'cash_accounts';

    public const TYPE_CASH = 'cash';
    public const TYPE_BANK = 'bank';

    protected $fillable = [
        'code', 'name', 'type', 'gl_account_id', 'currency_code', 'bank_name',
        'account_ref', 'allow_negative', 'overdraft_limit', 'active', 'notes', 'created_by',
    ];

    protected $casts = [
        'allow_negative'  => 'boolean',
        'active'          => 'boolean',
        'overdraft_limit' => 'float',
    ];

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function statementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class, 'cash_account_id');
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class, 'cash_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * What this account holds, read straight off the general ledger.
     *
     * Cash accounts are debit-normal, so debits add and credits subtract.
     */
    public function balance(?string $asAt = null): float
    {
        $query = DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->where('journal_lines.account_id', $this->gl_account_id);

        if ($asAt) {
            $query->whereDate('journals.journal_date', '<=', $asAt);
        }

        $sums = $query->selectRaw('SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c')->first();

        return round((float) ($sums->d ?? 0) - (float) ($sums->c ?? 0), 8);
    }

    /** The floor this account may not go below. */
    public function floor(): float
    {
        return $this->allow_negative ? -abs($this->overdraft_limit) : 0.0;
    }

    public function label(): string
    {
        return $this->code . ' ' . $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
