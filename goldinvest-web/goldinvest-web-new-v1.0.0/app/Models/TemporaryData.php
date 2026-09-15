<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryData extends Model
{
    use HasFactory;
    protected $table = "temporary_datas";

    protected $fillable = [
        'type',
        'identifier',
        'gateway_code',
        'currency_code',
        'data'
    ];

    protected $casts = [
        'id'   => 'integer',
        'data' => 'object',
        'type' => 'string',
        'identifier' => 'string',
        'gateway_code'  => 'string',
        'currency_code'  => 'string'
    ];

    public function scopeSearch($query,$token) {
        return $query->where('identifier',$token);
    }
}
