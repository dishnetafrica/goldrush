<?php

namespace App\GoldTrading\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records whose investor capital funded a lot.
 *
 * An allocation can be committed (locked_balance = 1), which debits the investor's
 * spendable wallet so the money cannot also be withdrawn while it is sitting in
 * gold, or attribution only (locked_balance = 0), which records the funding without
 * touching any balance. Committed capital is credited back when the deal is paid.
 */
class CapitalAllocation extends Model
{
    protected $table = 'gold_capital_allocations';

    /** Transaction types written when capital goes into, and comes back from, a deal. */
    public const TRX_LOCK    = 'GOLD-CAPITAL-LOCK';
    public const TRX_RELEASE = 'GOLD-CAPITAL-RETURN';

    public const STATUS_ALLOCATED  = 'allocated';
    public const STATUS_RETURNED   = 'returned';
    public const STATUS_WRITTENOFF = 'written_off';

    public const FROM_BALANCE = 'balance';
    public const FROM_PROFIT  = 'profit_balance';
    public const FROM_MIXED   = 'mixed';

    protected $fillable = [
        'gold_lot_id', 'user_id', 'source_trx_id', 'amount_usd', 'share_percent',
        'allocated_at', 'status', 'notes', 'recorded_by',
        'locked_balance', 'locked_from', 'locked_from_balance_usd', 'locked_from_profit_usd',
        'lock_trx_id', 'release_trx_id', 'released_at',
    ];

    protected $casts = [
        'allocated_at'            => 'date',
        'released_at'             => 'date',
        'amount_usd'              => 'float',
        'share_percent'           => 'float',
        'locked_balance'          => 'boolean',
        'locked_from_balance_usd' => 'float',
        'locked_from_profit_usd'  => 'float',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GoldLot::class, 'gold_lot_id');
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Capital currently sitting in an open deal for this investor. */
    public static function committedFor(int $userId): float
    {
        return (float) static::where('user_id', $userId)
            ->where('status', self::STATUS_ALLOCATED)
            ->where('locked_balance', true)
            ->sum('amount_usd');
    }
}
