<?php

namespace App\GoldTrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoldSale extends Model
{
    protected $fillable = [
        'sale_code', 'gold_lot_id', 'sale_date', 'buyer_name', 'location', 'grams_sold',
        'reference_rate_usd', 'discount_percent', 'price_basis', 'price_per_gram_usd',
        'gross_proceeds_usd', 'settlement_currency', 'fx_rate_to_usd', 'gross_proceeds_local',
        'status', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'sale_date'            => 'date',
        'grams_sold'           => 'float',
        'reference_rate_usd'   => 'float',
        'discount_percent'     => 'float',
        'price_per_gram_usd'   => 'float',
        'gross_proceeds_usd'   => 'float',
        'fx_rate_to_usd'       => 'float',
        'gross_proceeds_local' => 'float',
    ];

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SETTLED   = 'settled';
    public const STATUS_CANCELLED = 'cancelled';

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GoldLot::class, 'gold_lot_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(TradingExpense::class, 'gold_sale_id');
    }

    public function scopeCounted($query)
    {
        return $query->where('status', self::STATUS_SETTLED);
    }
}
