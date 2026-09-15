<?php

namespace App\Models;

use App\Constants\GlobalConst;
use App\Models\User\Order;
use Laravel\Passport\HasApiTokens;
use App\Models\User\UserHasInvestPlan;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $appends = ['fullname','userImage','stringStatus','lastLogin','kycStringStatus'];
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'firstname',
        'lastname',
        'username',
        'email',
        'mobile_code',
        'mobile',
        'full_mobile',
        'password',
        'referral_id',
        'current_referral_level_id',
        'image',
        'status',
        'address',
        'email_verified',
        'kyc_verified',
        'ver_code',
        'ver_code_send_at',
        'two_factor_verified',
        'two_factor_status',
        'two_factor_secret',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id'     => 'integer',
        'firstname' => 'string',
        'lastname'  => 'string',
        'username'  => 'string',
        'email'     => 'string',
        'mobile_code' => 'string',
        'mobile'      => 'string',
        'full_mobile' => 'string',
        'password'    => 'string',
        'referral_id' => 'string',
        'image'       => 'string',
        'status'      => 'integer',
        'email_verified' => 'integer',
        'kyc_verified'  => 'integer',
        'ver_code'  => 'integer',
        'current_referral_level_id' => 'integer',
        'two_factor_verified' => 'integer',
        'two_factor_status' => 'integer',
        'two_factor_secret' => 'string',
        'email_verified_at' => 'datetime',
        'ver_code_send_at' => 'datetime',
        'address'           => 'object',
    ];

    public function scopeEmailUnverified($query)
    {
        return $query->where('email_verified', false);
    }

    public function scopeEmailVerified($query) {
        return $query->where("email_verified",true);
    }

    public function scopeKycVerified($query) {
        return $query->where("kyc_verified",GlobalConst::VERIFIED);
    }

    public function scopeKycUnverified($query)
    {
        return $query->where('kyc_verified',GlobalConst::UNVERIFIED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
    public function scopeNotAuth($query)
    {
        return $query->whereNot("id", auth()->user()->id);
    }

    public function scopeBanned($query)
    {
        return $query->where('status', false);
    }

    public function kyc()
    {
        return $this->hasOne(UserKycData::class);
    }

    public function getFullnameAttribute()
    {
        return $this->firstname . ' ' . $this->lastname;
    }
    public function referLevel() {
        return $this->belongsTo(ReferralLevelPackage::class,'current_referral_level_id','id');
    }
    public function earnedLevels() {
        return $this->hasMany(UserEarnReferralLevel::class,'user_id');
    }
    public function referUsers() {
        return $this->hasMany(ReferredUser::class,'refer_user_id','id');
    }
    public function nextReferLevel() {
        $current_refer_level = $this->referLevel;
        $default_refer_level = ReferralLevelPackage::default()->first();
        if(!$current_refer_level && $default_refer_level) {
            return ReferralLevelPackage::default()->first();
        }else if(!$current_refer_level && !$default_refer_level) {
            $first_level = ReferralLevelPackage::first();
            if(!$first_level) return false;
            return $first_level;
        }
        $next_refer_level = ReferralLevelPackage::where('id','>',$current_refer_level->id)->orderBy('id','asc')->first();
        if(!$next_refer_level) return false;
        return $next_refer_level;
    }
    public function wallets()
    {
        return $this->hasMany(UserWallet::class);
    }

    public function getUserImageAttribute() {
        $image = $this->image;

        if($image == null) {
            return files_asset_path('profile-default');
        }else if(filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }else {
            return files_asset_path("user-profile") . "/" . $image;
        }
    }

    public function passwordResets() {
        return $this->hasMany(UserPasswordReset::class,"user_id");
    }

    public function scopeGetSocial($query,$credentials) {
        return $query->where("email",$credentials);
    }
    public function investPlans() {
        return $this->hasMany(UserHasInvestPlan::class);
    }
    public function profit(){
        return $this->hasMany(InvestmentProfitLog::class);
    }
    public function orders(){
        return $this->hasMany(Order::class);
    }
    public function getStringStatusAttribute() {
        $status = $this->status;
        $data = [
            'class' => "",
            'value' => "",
        ];
        if($status == GlobalConst::ACTIVE) {
            $data = [
                'class'     => "badge badge--success",
                'value'     => "Active",
            ];
        }else if($status == GlobalConst::BANNED) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => "Banned",
            ];
        }
        return (object) $data;
    }

    public function getKycStringStatusAttribute() {
        $status = $this->kyc_verified;
        $data = [
            'class' => "",
            'value' => "",
        ];
        if($status == GlobalConst::APPROVED) {
            $data = [
                'class'     => "badge badge--success",
                'value'     => "Verified",
            ];
        }else if($status == GlobalConst::PENDING) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => "Pending",
            ];
        }else if($status == GlobalConst::REJECTED) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => "Rejected",
            ];
        }else {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => "Unverified",
            ];
        }
        return (object) $data;
    }

    public function loginLogs(){
        return $this->hasMany(UserLoginLog::class);
    }

    public function getLastLoginAttribute() {
        if($this->loginLogs()->count() > 0) {
            return $this->loginLogs()->get()->last()->created_at->format("H:i A, d M Y");
        }

        return "N/A";
    }

    public function scopeSearch($query,$data) {
        return $query->where(function($q) use ($data) {
            $q->where("username","like","%".$data."%");
        })->orWhere("email","like","%".$data."%")->orWhere("full_mobile","like","%".$data."%");
    }

}
