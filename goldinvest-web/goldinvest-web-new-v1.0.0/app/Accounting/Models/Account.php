<?php

namespace App\Accounting\Models;

use App\Accounting\Ledger\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One line of the chart of accounts. */
class Account extends Model
{
    protected $table = 'accounts';

    /** What a control account is reconciled against. */
    public const CONTROL_INVESTOR_CAPITAL = 'investor_capital';
    public const CONTROL_INVESTOR_PROFIT  = 'investor_profit';
    public const CONTROL_INVENTORY        = 'inventory';
    public const CONTROL_CASH             = 'cash';

    protected $fillable = [
        'code', 'name', 'type', 'normal_balance', 'parent_id',
        'control_of', 'currency_code', 'active', 'description',
    ];

    protected $casts = ['active' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    public function isDebitNormal(): bool
    {
        return $this->normal_balance === AccountType::DEBIT;
    }

    /**
     * The account's balance expressed the way its own type reads it, so an asset
     * and a liability can both be reported as positive when they are healthy.
     */
    public function signedBalance(float $debits, float $credits): float
    {
        return round($this->isDebitNormal() ? $debits - $credits : $credits - $debits, 8);
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
