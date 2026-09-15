<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLoginLog extends Model
{
    use HasFactory;
    protected $casts = [
        'id'        => 'integer',
        'user_id'      => 'integer',
        'reject_reason' => 'string',
        'ip'            => 'string',
        'mac'           => 'string',
        'city'          => 'string',
        'country'       => 'string',
        'longitude'     => 'string',
        'latitude'      => 'string',
        'browser'       => 'string',
        'os'            => 'string',
        'timezone'      => 'string',
    ];
    protected $fillable = [
        'user_id',
        'ip',
        'mac',
        'city',
        'country',
        'longitude',
        'latitude',
        'browser',
        'os',
        'timezone'
    ];
}
