<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_ids',
        'device_ids',
        'method',
        'response',
        'message',
        'send_by'
    ];

    protected $casts = [
        'response'      => 'object',
        'message'       => 'object',
    ];
}
