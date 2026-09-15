<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\Admin;

class UserSupportChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_support_ticket_id',
        'sender',
        'sender_type',
        'receiver',
        'receiver_type',
        'message',
        'seen'
    ];
    protected $casts = [
        'id'            => 'integer',
        'user_support_ticket_id' => 'integer',
        'sender'        => 'integer',
        'receiver'      => 'integer',
        'sender_type'   => 'string',
        'receiver_type' => 'string',
        'message'       => 'string',
        'information'   => 'string',
        'seen'          => 'integer'
    ];
    protected $with = [
        'supportTicket',
    ];

    protected $appends = ['senderImage'];

    public function scopeConversations($query,$id) {
        return $query->where("user_support_ticket_id",$id);
    }

    public function supportTicket() {
        return $this->belongsTo(UserSupportTicket::class,"user_support_ticket_id");
    }

    public function getSenderImageAttribute() {
        if($this->sender_type == "ADMIN") {
            $admin = Admin::find($this->sender);
            if($admin) {
                return get_image($admin->image,"admin-profile");
            }else {
                return files_asset_path("default");
            }
        }else if($this->sender_type == "USER"){
            return $this->supportTicket->user->userImage;
        }
        return files_asset_path("default");
    }
}
