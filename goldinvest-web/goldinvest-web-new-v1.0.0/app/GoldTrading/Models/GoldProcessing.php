<?php

namespace App\GoldTrading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoldProcessing extends Model
{
    protected $fillable = [
        'gold_lot_id', 'processed_at', 'method', 'input_grams', 'waste_grams',
        'waste_percent', 'output_grams', 'output_purity', 'cost_usd', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'processed_at'  => 'date',
        'input_grams'   => 'float',
        'waste_grams'   => 'float',
        'waste_percent' => 'float',
        'output_grams'  => 'float',
        'cost_usd'      => 'float',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(GoldLot::class, 'gold_lot_id');
    }
}
