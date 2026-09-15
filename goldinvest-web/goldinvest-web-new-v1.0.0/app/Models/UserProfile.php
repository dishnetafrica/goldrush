<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;
    protected $casts = [
        'id'            => 'integer',
        'user_id'       => 'integer',
        'country'       => 'string',
        'city'          => 'string',
        'state'         => 'string',
        'zip_code'      => 'string',
        'information'   => 'string',
        'reject_reason' => 'string',
    ];
    protected $fillable = [
        'user_id',
        'country',
        'city',
        'state',
        'zip_code',
        'information',
        'reject_reason',
        'status',
    ];
}
