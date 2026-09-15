<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoldStock extends Model
{
    use HasFactory;
    protected $fillable = [
        'slug',
        'title',
        'type',
        'price',
        'charge',
        'weight',
        'purity',
        'manufacturer',
        'country_of_origin',
        'image',
        'status'
    ];
    protected $casts = [
        'id'        => 'integer',
        'slug'      => 'string',
        'type'      => 'string',
        'weight'    => 'string',
        'purity'    => 'string',
        'manufacturer' => 'string',
        'country_of_origin' => 'string',
        'status'    => 'integer',
        'title'     => 'object',
        'image'     => 'string',
        'price'     => 'double',
        'charge'    => 'double',
    ];
}
