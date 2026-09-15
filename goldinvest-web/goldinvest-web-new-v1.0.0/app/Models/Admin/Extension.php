<?php
namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Extension extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'script',
        'shortcode',
        'support_image',
        'status'
    ];

    protected $casts = [
        'shortcode' => 'object',
    ];
}
