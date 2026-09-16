<?php

namespace App\GoldTrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradingExpense extends Model
{
    protected $table = 'gold_trading_expenses';

    protected $fillable = [
        'expense_date', 'category', 'description', 'currency_code', 'amount_local',
        'fx_rate_to_usd', 'amount_usd', 'gold_lot_id', 'gold_sale_id', 'recorded_by',
    ];

    protected $casts = [
        'expense_date'   => 'date',
        'amount_local'   => 'float',
        'fx_rate_to_usd' => 'float',
        'amount_usd'     => 'float',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GoldLot::class, 'gold_lot_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(GoldSale::class, 'gold_sale_id');
    }
}
