<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\AppSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $app_settings = array(
            array('id' => '1','version' => '1.0.0','splash_screen_image' => 'seeder/splash-screen.png','url_title' => 'Download App','android_url' => 'https://play.google.com/','iso_url' => 'https://www.apple.com/app-store/','created_at' => '2024-10-17 13:21:06','updated_at' => '2024-10-18 12:06:27')
          );

        AppSettings::insert($app_settings);
    }
}
