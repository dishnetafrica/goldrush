<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetupPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'url',
        'status',
        'last_edit_by'
    ];

    protected $casts = [
        'id'        => 'integer',
        'slug'      => 'string',
        'title'     => 'string',
        'url'       => 'string',
        'status'    => 'integer',
        'last_edit_by' => 'integer',
    ];
}
