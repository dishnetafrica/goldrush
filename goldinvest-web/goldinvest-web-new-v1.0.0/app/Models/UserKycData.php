<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserKycData extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'data',
        'reject_reason'
    ];

     /**
     * The attributes that should be cast.
     *
     * @var array
     */

    protected $casts = [
        'id'        => 'integer',
        'user_id'   => 'integer',
        'data'      => 'object',
        'reject_reason' => 'string'
    ];
}
