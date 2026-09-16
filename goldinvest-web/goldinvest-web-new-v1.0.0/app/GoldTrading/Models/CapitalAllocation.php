<?php

namespace App\GoldTrading\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records whose investor capital funded a lot. Attribution only:
 * creating one never changes user_wallets. The investor keeps their USD
 * claim on the company until a result is approved and distributed.
 */
class CapitalAllocation extends Model
{
    protected $table = 'gold_capital_allocations';

    protected $fillable = [
        'gold_lot_id', 'user_id', 'source_trx_id', 'amount_usd',
        'allocated_at', 'status', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'allocated_at' => 'date',
        'amount_usd'   => 'float',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GoldLot::class, 'gold_lot_id');
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
