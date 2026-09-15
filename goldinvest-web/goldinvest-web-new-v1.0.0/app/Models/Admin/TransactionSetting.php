<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionSetting extends Model
{
    use HasFactory;


    protected $fillable = [
        'admin_id',
        'slug',
        'title',
        'fixed_charge',
        'percent_charge',
        'min_limit',
        'max_limit',
        'monthly_limit',
        'daily_limit',
        'status'
    ];

    protected $casts = [
        'id'        => 'integer',
        'admin_id'  => 'integer',
        'slug'      => 'string',
        'title'     => 'string',
        'status'    => 'integer',
        'fixed_charge' => 'double',
        'percent_charge' => 'double',
        'min_limit' => 'double',
        'max_limit' => 'double',
        'monthly_limit'=> 'double',
        'daily_limit' => 'double'
    ];
    protected $with = ['admin'];


    public function admin() {
        return $this->belongsTo(Admin::class);
    }
}
