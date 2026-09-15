<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\GoldStock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GoldStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $gold_stocks = array(
            array('id' => '1','slug' => 'perth-mint-gold-bar','title' => '{"language":{"en":{"title":"Perth Mint Gold Bar"},"es":{"title":"Barra de oro de la Casa de la Moneda de Perth"},"ar":{"title":"\\u0628\\u064a\\u0631\\u062b \\u0645\\u0646\\u062a \\u0627\\u0644\\u0630\\u0647\\u0628 \\u0628\\u0627\\u0631"}}}','type' => 'Bar','price' => '1300.00','charge' => '5.00','weight' => '20gm','purity' => '999.90','manufacturer' => 'Philoro','country_of_origin' => 'Austria / Switzerland','image' => '6a85cb88-8e7f-4fdb-92a9-b2b406ba2750.webp','status' => '1','created_at' => '2023-12-30 08:07:29','updated_at' => '2023-12-30 08:07:29'),
            array('id' => '2','slug' => 'emirates-gold-bar','title' => '{"language":{"en":{"title":"Emirates Gold Bar"},"es":{"title":"Barra de oro de los Emiratos"},"ar":{"title":"\\u0627\\u0644\\u0625\\u0645\\u0627\\u0631\\u0627\\u062a \\u0644\\u0644\\u0630\\u0647\\u0628"}}}','type' => 'Bar','price' => '2600.00','charge' => '10.00','weight' => '45gm','purity' => '999.9','manufacturer' => 'Lisbone','country_of_origin' => 'India/ Africa','image' => '638d5ce4-6376-4dba-b53e-14cef1a59503.webp','status' => '1','created_at' => '2023-12-30 08:08:13','updated_at' => '2023-12-30 08:08:13'),
            array('id' => '3','slug' => 'swiss-gold-bar','title' => '{"language":{"en":{"title":"Swiss Gold Bar"},"es":{"title":"Barra de oro suiza"},"ar":{"title":"\\u0633\\u0628\\u064a\\u0643\\u0629 \\u0627\\u0644\\u0630\\u0647\\u0628 \\u0627\\u0644\\u0633\\u0648\\u064a\\u0633\\u0631\\u064a"}}}','type' => 'Bar','price' => '3000.00','charge' => '15.00','weight' => '50gm','purity' => '999.99','manufacturer' => 'PAMP Suisse','country_of_origin' => 'Switzerland','image' => '6a85cb88-8e7f-4fdb-92a9-b2b406ba2750.webp','status' => '1','created_at' => '2024-01-15 10:30:00','updated_at' => '2024-01-15 10:30:00'),
            array('id' => '4','slug' => 'canadian-maple-leaf-gold-bar','title' => '{"language":{"en":{"title":"Canadian Maple Leaf Gold Bar"},"es":{"title":"Barra de oro Hoja de Arce Canadiense"},"ar":{"title":"\\u0633\\u0628\\u064a\\u0643\\u0629 \\u0648\\u0631\\u0642\\u0629 \\u0627\\u0644\\u0642\\u064a\\u0642\\u0628 \\u0627\\u0644\\u0643\\u0646\\u062f\\u064a\\u0629 \\u0627\\u0644\\u0630\\u0647\\u0628\\u064a\\u0629"}}}','type' => 'Bar','price' => '1800.00','charge' => '8.00','weight' => '31.1gm','purity' => '999.99','manufacturer' => 'Royal Canadian Mint','country_of_origin' => 'Canada','image' => '638d5ce4-6376-4dba-b53e-14cef1a59503.webp','status' => '1','created_at' => '2024-01-16 14:45:00','updated_at' => '2024-01-16 14:45:00'),
            array('id' => '5','slug' => 'american-eagle-gold-bar','title' => '{"language":{"en":{"title":"American Eagle Gold Bar"},"es":{"title":"Barra de oro Águila Americana"},"ar":{"title":"\\u0633\\u0628\\u064a\\u0643\\u0629 \\u0627\\u0644\\u0646\\u0633\\u0631 \\u0627\\u0644\\u0623\\u0645\\u0631\\u064a\\u0643\\u064a \\u0627\\u0644\\u0630\\u0647\\u0628\\u064a\\u0629"}}}','type' => 'Bar','price' => '1900.00','charge' => '9.00','weight' => '31.1gm','purity' => '916.7','manufacturer' => 'United States Mint','country_of_origin' => 'United States','image' => '6a85cb88-8e7f-4fdb-92a9-b2b406ba2750.webp','status' => '1','created_at' => '2024-01-17 09:15:00','updated_at' => '2024-01-17 09:15:00'),
            array('id' => '6','slug' => 'south-african-krugerrand','title' => '{"language":{"en":{"title":"South African Krugerrand"},"es":{"title":"Krugerrand Sudafricano"},"ar":{"title":"\\u0643\\u0631\\u0648\\u062c\\u0631\\u0627\\u0646\\u062f \\u062c\\u0646\\u0648\\u0628 \\u0623\\u0641\\u0631\\u064a\\u0642\\u064a\\u0627"}}}','type' => 'Bar','price' => '1850.00','charge' => '8.50','weight' => '31.1gm','purity' => '916.7','manufacturer' => 'Rand Refinery','country_of_origin' => 'South Africa','image' => '638d5ce4-6376-4dba-b53e-14cef1a59503.webp','status' => '1','created_at' => '2024-01-18 11:00:00','updated_at' => '2024-01-18 11:00:00')
          );
        GoldStock::insert($gold_stocks);
    }
}
