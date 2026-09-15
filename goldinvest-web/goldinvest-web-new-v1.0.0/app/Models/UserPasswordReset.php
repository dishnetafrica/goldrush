<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPasswordReset extends Model
{
    use HasFactory;
    protected $fillable = [
        'email',
        'code',
        'token',
        'user_id'
    ];
    protected $casts = [
        'id'            => 'integer',
        'user_id'       => 'integer',
        'email'         => 'string',
        'code'          => 'integer',
        'token'         => 'string',
    ];
    public function user() {
        return $this->belongsTo(User::class)->select('id','username','email','firstname','lastname');
    }
}
