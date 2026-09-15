<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\BasicSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BasicSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $basic_settings = array(
            array('id' => '1','site_name' => 'GoldInvest','site_title' => 'Invest And Safe Your Money','base_color' => '#FC5B3F','secondary_color' => '#161720','otp_exp_seconds' => '3600','timezone' => 'Asia/Dhaka','user_registration' => '1','secure_password' => '1','agree_policy' => '1','force_ssl' => '1','email_verification' => '1','email_notification' => '1','push_notification' => '1','kyc_verification' => '1','site_logo_dark' => 'seeder/logo-white.png','site_logo' => 'seeder/logo-dark.png','site_fav_dark' => 'seeder/fav-dark.png','site_fav' => 'seeder/fav-white.png','preloader_image' => NULL,'mail_config' => '{"method":"smtp","host":"","port":"465","encryption":"","username":"","password":"","from":"","app_name":"GoldInvest"}','mail_activity' => NULL,'push_notification_config' => '{"method":"pusher","instance_id":"","primary_key":""}','push_notification_activity' => NULL,'broadcast_config' => '{"method":"pusher","app_id":"","primary_key":"","secret_key":"","cluster":"ap2"}','broadcast_activity' => NULL,'sms_config' => NULL,'sms_activity' => NULL,'web_version' => '1.0.0','admin_version' => '2.5.0','created_at' => '2023-12-04 09:58:52','updated_at' => '2024-02-01 12:35:00')
          );

        BasicSettings::insert($basic_settings);
    }
}
