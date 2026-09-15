<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\MoneyOutSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MoneyOutSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $money_out_settings = array(
            array('id' => '1','c_balance' => '1','p_balance' => '1','created_at' => NULL,'updated_at' => '2024-01-01 12:53:02')
          );
        MoneyOutSetting::insert($money_out_settings);
    }
}
