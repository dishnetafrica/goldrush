<?php

namespace App\Models\Admin;

use App\Constants\GlobalConst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestmentPlan extends Model
{
    use HasFactory;
    protected $fillable = [
        'slug',
        'data',
        'plan_duration',
        'profit_return_type',
        'minimum_investment',
        'minimum_investment_offer',
        'maximum_investment',
        'profit',
        'profit_percentage',
        'image',
        'status'
    ];
    protected $casts = [
        'id'        => 'integer',
        'slug'      => 'string',
        'data'      => 'object',
        'plan_duration'         => "integer",
        'minimum_investment'    => 'double',
        'minimum_investment_offer' => 'double',
        'maximum_investment'    => 'double',
        'profit'                => 'double',
        'profit_percentage'     => 'double',
        'status'                => 'boolean',
        'profit_return_type'    => 'string',
        'image'                 => 'string',
        'status'                => 'integer',

    ];
    public function getRouteKeyName()
    {
        return 'slug';
    }
    public function scopeActive($query) {
        return $query->where('status',GlobalConst::ACTIVE);
    }

    public function getMinInvestRequirementAttribute() {
        if($this->minimum_investment_offer > 0) {
            return $this->minimum_investment_offer;
        }
        return $this->minimum_investment;
    }
}
