<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetupSeo extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'desc',
        'tags',
        'image',
        'last_edit_by'
    ];

    protected $casts = [
        'tags'      => 'object',
    ];
}
