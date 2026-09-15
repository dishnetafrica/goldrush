<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoneyOutSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'c_balance',
        'p_balance'
    ];
    protected $casts = [
        'id'        => 'integer',
        'c_balance' => "integer",
        'p_balance' => 'integer',

    ];
}
