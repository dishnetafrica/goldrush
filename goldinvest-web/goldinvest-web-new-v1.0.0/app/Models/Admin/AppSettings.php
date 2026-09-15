<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSettings extends Model
{
    use HasFactory;
    protected $fillable = [
        'version',
        'splash_screen_image',
        'url_title',
        'android_url',
        'iso_url'
    ];
    protected $casts = [
        'id'    => 'integer',
        'version' => 'string',
        'url_title' => 'string',
        'splash_screen_image' => 'string',
        'android_url' => 'string',
        'iso_url' => 'string',
    ];
}
