<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminLoginLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'ip',
        'mac',
        'city',
        'country',
        'longitude',
        'latitude',
        'browser',
        'os',
        'timezone',
    ];
}
