<?php

namespace App\Models\Frontend;

use App\Constants\GlobalConst;
use Illuminate\Database\Eloquent\Model;
use App\Models\Frontend\AnnouncementCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_category_id',
        'slug',
        'data',
        'status'
    ];

    protected $casts = [
        'id'        => "integer",
        'announcement_category_id'  => "integer",
        'slug'      => 'string',
        'status'    => 'integer',
        'data'      => 'object',
    ];

    public function getRouteKeyName()
    {
        return "slug";
    }

    public function category() {
        return $this->belongsTo(AnnouncementCategory::class,"announcement_category_id");
    }

    public function scopeActive($query) {
        return $query->where("status",GlobalConst::ACTIVE);
    }
}
