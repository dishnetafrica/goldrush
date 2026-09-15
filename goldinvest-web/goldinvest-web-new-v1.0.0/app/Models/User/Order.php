<?php

namespace App\Models\User;

use App\Models\User;
use App\Constants\GlobalConst;
use App\Models\Admin\GoldStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'gold_stock_id',
        'quantity',
        'mobile_code',
        'mobile',
        'full_mobile',
        'address',
        'total_amount',
        'payment_type',
        'status',
        'order_status',
        'cancel_reason',
        'payment_status'
    ];
    protected $casts = [
        'id'                => 'integer',
        'user_id'           => 'integer',
        'gold_stock_id'     => 'integer',
        'quantity'          => 'integer',
        'address'           => 'object',
        'mobile_code'       => 'string',
        'quantity'          => 'integer',
        'mobile'            => 'string',
        'full_mobile'       => 'string',
        'total_amount'      => 'double',
        'payment_status'    => 'integer',
        'status'            => 'integer',
        'order_status'      => 'integer',
        'cancel_reason'     => 'string',
        'payment_type'      => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function gold()
    {
        return $this->belongsTo(GoldStock::class,'gold_stock_id');
    }
    public function scopeAuth($query) {
        return $query->where('user_id',auth()->user()->id);
    }

}
