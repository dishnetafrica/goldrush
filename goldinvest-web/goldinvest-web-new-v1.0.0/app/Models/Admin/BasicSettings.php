<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_name',
        'site_title',
        'base_color',
        'secondary_color',
        'otp_exp_seconds',
        'timezone',
        'user_registration',
        'secure_password',
        'agree_policy',
        'force_ssl',
        'email_verification',
        'email_notification',
        'push_notification',
        'kyc_verification',
        'site_logo_dark',
        'site_logo',
        'site_fav_dark',
        'site_fav',
        'preloader_image',
        'mail_config',
        'mail_activity',
        'push_notification_config',
        'push_notification_activity',
        'broadcast_config',
        'broadcast_activity',
        'sms_config',
        'sms_activity',
        'web_version',
        'admin_version'
    ];

    protected $casts = [
        'id'                        => 'integer',
        'site_name'                 => 'string',
        'site_title'                => 'string',
        'base_color'                => 'string',
        'secondary_color'           => 'string',
        'timezone'                  => 'string',
        'site_logo_dark'            => 'string',
        'site_logo'                 => 'string',
        'site_fav_dark'             => 'string',
        'site_fav'                  => 'string',
        'preloader_image'           => 'string',
        'mail_activity'             => 'string',
        'push_notification_activity'=> 'string',
        'broadcast_activity'        => 'string',
        'sms_activity'              => 'string',
        'web_version'               => 'string',
        'admin_version'             => 'string',
        'otp_exp_seconds'           => 'integer',
        'user_registration'         => 'integer',
        'secure_password'           => 'integer',
        'agree_policy'              => 'integer',
        'force_ssl'                 => 'integer',
        'email_verification'        => 'integer',
        'email_notification'        => 'integer',
        'push_notification'         => 'integer',
        'kyc_verification'          => 'integer',
        'mail_config'               => 'object',
        'push_notification_config'  => 'object',
        'broadcast_config'          => 'object',
    ];


    public function mailConfig() {

    }
}
