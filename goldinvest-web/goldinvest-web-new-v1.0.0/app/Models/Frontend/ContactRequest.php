<?php

namespace App\Models\Frontend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'message',
        'reply'
    ];
    protected $casts = [
        'id'      => 'integer',
        'name'    => "string",
        'email'   => "string",
        'message' => "string",
        'reply'   => "integer",
    ];
}
