<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\SetupSeo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SetupSeoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $setup_seos = array(
            array('id' => '1','slug' => 'lorem_ipsum','title' => 'GoldInvest is simply dummy text of the printing and typesetting industry.','desc' => 'GoldInvest is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.','tags' => '["Lorem","Ipsum"]','image' => 'fe9b26c4-9c71-456b-ae80-ed87635bd15a.webp','last_edit_by' => '1','created_at' => '2024-10-22 17:53:54','updated_at' => '2024-10-22 18:21:39')
          );

        SetupSeo::insert($setup_seos);
    }
}
