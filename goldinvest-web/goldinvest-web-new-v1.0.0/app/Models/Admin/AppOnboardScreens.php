<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppOnboardScreens extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'sub_title',
        'image',
        'status',
        'last_edit_by'
    ];
    
    protected $casts = [
        'id'    => 'integer',
        'title' => 'string',
        'sub_title' => 'string',
        'image' => 'string',
        'status' => 'integer',
        'last_edit_by' => 'integer',
    ];
    protected $appends = [
        'editData',
    ];


    public function getEditDataAttribute()
    {
        $data = [
            'title'      => $this->title,
            'sub_title'  => $this->sub_title,
            'image'      => $this->image,
            'status'     => $this->status,
            'target'     => $this->id,
        ];

        return json_encode($data);
    }
}
