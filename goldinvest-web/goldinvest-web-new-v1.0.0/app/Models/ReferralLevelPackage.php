<?php

namespace App\Models;

use App\Constants\GlobalConst;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralLevelPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'refer_user',
        'invested_amount',
        'commission',
        'default'
    ];

    protected $casts = [
        'id'                => 'integer',
        'title'             => 'string',
        'refer_user'        => 'integer',
        'invested_amount'   => 'double',
        'commission'        => 'double',
        'default'           => 'boolean',
    ];

    function scopeDefault($query) {
        return $query->where('default',GlobalConst::ACTIVE);
    }
}
