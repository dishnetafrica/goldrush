<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'token'
    ];
    protected $casts = [
        'id'                => 'integer',
        'user_id'           => 'integer',
        'code'              => 'integer',
        'token'             => 'string',
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
}
