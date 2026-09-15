<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'bonus',
        'wallet_type',
        'mail',
        'status'
    ];

    protected $casts = [
        'id'        => 'integer',
        'bonus'     => 'double',
        'status'    => 'integer',
        'mail'      => 'integer',
        'wallet_type' => 'string',

    ];
}
