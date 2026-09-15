<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use App\Models\Admin\AppOnboardScreens;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AppOnBoardScreenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $app_onboard_screens = array(
            array('id' => '1','title' => 'Inspiring financial success, multiple investment at a time','sub_title' => NULL,'image' => 'seeder/first-onboard.png','status' => '1','last_edit_by' => '1','created_at' => '2024-10-18 12:02:34','updated_at' => '2024-10-18 12:02:34'),
            array('id' => '2','title' => 'Unlock Your Style Potential with Gold, A Masterpiece in Every Piece','sub_title' => NULL,'image' => 'seeder/second-onboard.png','status' => '1','last_edit_by' => '1','created_at' => '2024-10-18 12:02:58','updated_at' => '2024-10-18 12:02:58'),
            array('id' => '3','title' => 'We keep our promise of best returns of your investment','sub_title' => NULL,'image' => 'seeder/third-onboard.png','status' => '1','last_edit_by' => '1','created_at' => '2024-10-18 12:03:43','updated_at' => '2024-10-18 12:03:43')
          );

        AppOnboardScreens::insert($app_onboard_screens);
    }
}
