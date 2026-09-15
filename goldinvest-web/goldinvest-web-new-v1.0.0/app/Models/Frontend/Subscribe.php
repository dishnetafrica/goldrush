<?php

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscribe extends Model
{
    use HasFactory;

    protected $fillable = [
        'email'
    ];

    protected $casts = [
        'id'    => 'integer',
        'email' => 'string'
    ];
}
