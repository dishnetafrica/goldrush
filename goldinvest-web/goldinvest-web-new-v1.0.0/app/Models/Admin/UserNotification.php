<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'message'
    ];

    protected $casts = [
        'id'   => 'integer',
        'user_id'   => 'integer',
        'type'   => 'string',
        'message'   => 'object'
    ];

    protected $with = [
        'user',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function scopeGetByType($query,$types) {
        if(is_array($types)) return $query->whereIn('type',$types);
    }

    public function scopeNotAuth($query) {
        $query->where("user_id","!=",auth()->user()->id);
    }

    public function scopeAuth($query) {
        $query->where("user_id",auth()->user()->id);
    }
}
