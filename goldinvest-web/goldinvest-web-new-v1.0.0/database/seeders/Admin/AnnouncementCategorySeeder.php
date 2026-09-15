<?php

namespace Database\Seeders\Admin;

use App\Models\Frontend\AnnouncementCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnnouncementCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $announcement_categories = array(
            array('id' => '1','name' => '{"language":{"en":{"name":"Gold"},"es":{"name":"Oro"},"ar":{"name":"\\u0630\\u0647\\u0628"}}}','status' => '1','created_at' => '2023-12-06 10:31:28','updated_at' => '2023-12-06 10:31:28')
          );
        AnnouncementCategory::insert($announcement_categories);
    }
}
