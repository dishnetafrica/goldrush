<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetupKyc extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'user_type',
        'fields',
        'status',
        'last_edit_by'
    ];

    protected $casts = [
        'id'        => 'integer',
        'slug'      => 'string',
        'user_type' => 'string',
        'status'    => 'integer',
        'last_edit_by' => 'integer',
        'fields'    => "object",
    ];

    public function scopeUserKyc($query) {
        return $query->where("user_type","USER")->active();
    }

    public function scopeActive($query) {
        $query->where("status",true);
    }
}
