<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMailLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'method',
        'subject',
        'message',
    ];
    protected $casts = [
        'id'            => 'integer',
        'user_id'       => 'integer',
        'method'        => 'string',
        'subject'       => 'string',
        'message'       => 'string',
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
}
