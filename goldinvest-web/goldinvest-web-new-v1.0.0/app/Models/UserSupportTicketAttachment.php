<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSupportTicketAttachment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_support_ticket_id',
        'attachment',
        'attachment_info',
    ];

    protected $casts = [
        'id'        => 'integer',
        'user_support_ticket_id' => 'integer',
        'attachment_info'        => 'object',
        'attachment'        => 'string',
    ];
}
