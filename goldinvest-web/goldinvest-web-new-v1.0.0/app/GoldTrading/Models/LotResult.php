<?php

namespace App\GoldTrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotResult extends Model
{
    protected $table = 'gold_lot_results';

    protected $fillable = [
        'gold_lot_id', 'closed_at', 'refined_grams', 'sold_grams', 'cost_of_goods_sold_usd',
        'expenses_usd', 'proceeds_usd', 'gross_profit_usd', 'net_profit_usd',
        'investor_share_percent', 'investor_profit_usd', 'company_profit_usd',
        'status', 'notes', 'approved_by',
    ];

    protected $casts = [
        'closed_at'              => 'date',
        'refined_grams'          => 'float',
        'sold_grams'             => 'float',
        'cost_of_goods_sold_usd' => 'float',
        'expenses_usd'           => 'float',
        'proceeds_usd'           => 'float',
        'gross_profit_usd'       => 'float',
        'net_profit_usd'         => 'float',
        'investor_share_percent' => 'float',
        'investor_profit_usd'    => 'float',
        'company_profit_usd'     => 'float',
    ];

    public const STATUS_DRAFT       = 'draft';
    public const STATUS_APPROVED    = 'approved';
    public const STATUS_DISTRIBUTED = 'distributed';

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GoldLot::class, 'gold_lot_id');
    }
}
