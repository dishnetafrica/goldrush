<?php

namespace App\GoldTrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoldLot extends Model
{
    protected $fillable = [
        'lot_code', 'purchase_date', 'project_name', 'location', 'supplier_name', 'purity_in',
        'gross_grams', 'purchase_currency', 'price_per_gram_local', 'fx_rate_to_usd',
        'price_per_gram_usd', 'total_cost_local', 'total_cost_usd', 'reference_rate_note',
        'status', 'investor_share_percent', 'expense_policy', 'terms_note', 'notes', 'recorded_by',
        'expenses_finalised_at', 'expenses_finalised_by',
    ];

    protected $casts = [
        'purchase_date'        => 'date',
        'gross_grams'          => 'float',
        'price_per_gram_local' => 'float',
        'fx_rate_to_usd'       => 'float',
        'price_per_gram_usd'   => 'float',
        'total_cost_local'     => 'float',
        'total_cost_usd'       => 'float',
        'investor_share_percent' => 'float',
        'expenses_finalised_at'  => 'datetime',
    ];

    public const EXPENSES_DEAL_BEFORE_SPLIT = 'deal_before_split';
    public const EXPENSES_COMPANY_SHARE     = 'company_share';

    public const STATUS_PURCHASED      = 'purchased';
    public const STATUS_PROCESSING     = 'processing';
    public const STATUS_IN_STOCK       = 'in_stock';
    public const STATUS_PARTIALLY_SOLD = 'partially_sold';
    public const STATUS_SOLD           = 'sold';
    public const STATUS_WRITTEN_OFF    = 'written_off';

    public function processings(): HasMany
    {
        return $this->hasMany(GoldProcessing::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(GoldSale::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(TradingExpense::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CapitalAllocation::class);
    }

    public function result()
    {
        return $this->hasOne(LotResult::class);
    }

    public function getRouteKeyName(): string
    {
        return 'lot_code';
    }

    /**
     * Whether this deal's costs are known to be complete.
     *
     * While false the recorded net trading result is provisional: further
     * transport, refining, assay, security or travel costs would reduce it.
     */
    public function expensesFinalised(): bool
    {
        return $this->expenses_finalised_at !== null;
    }

    /** How the expense position is described to an investor. */
    public function expenseStatusLabel(): string
    {
        return $this->expensesFinalised()
            ? __('Closed - expenses complete')
            : __('Open - expenses pending');
    }
}
