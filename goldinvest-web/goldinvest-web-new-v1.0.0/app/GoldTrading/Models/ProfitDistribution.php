<?php

namespace App\GoldTrading\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitDistribution extends Model
{
    protected $table = 'gold_profit_distributions';

    /**
     * Transaction type written to the transactions table when profit is paid.
     * The user transaction log renders entries by type, so this value must match
     * the one used in resources/views/user/components/transaction/log.blade.php.
     */
    public const TRX_TYPE = 'GOLD-DEAL-PROFIT';

    public const STATUS_PAID     = 'paid';
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'gold_lot_id', 'user_id', 'capital_usd', 'capital_share_percent', 'profit_share_percent',
        'amount_usd', 'wallet_id', 'trx_id', 'credited_to', 'distributed_at', 'status',
        'notes', 'recorded_by',
    ];

    protected $casts = [
        'distributed_at'        => 'date',
        'capital_usd'           => 'float',
        'capital_share_percent' => 'float',
        'profit_share_percent'  => 'float',
        'amount_usd'            => 'float',
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
