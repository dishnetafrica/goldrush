<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\InvestmentPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvestmentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $investment_plans = array(
            array('id' => '1','slug' => 'starter','data' => '{"language":{"en":{"name":"Starter","title":"Starter"},"es":{"name":"Arrancador","title":"Arrancador"},"ar":{"name":"\\u0628\\u062f\\u0627\\u064a\\u0629","title":"\\u0628\\u062f\\u0627\\u064a\\u0629"}}}','plan_duration' => '1','profit_return_type' => 'DAILY-BASIS','minimum_investment' => '30.00000000','minimum_investment_offer' => '25.00000000','maximum_investment' => '50.00000000','profit' => '5.00000000','profit_percentage' => '2.00000000','image' => '51e51114-8269-46a6-9e0a-e763539b5ac3.webp','status' => '1','created_at' => '2023-12-30 08:10:32','updated_at' => '2023-12-30 08:10:32'),
            array('id' => '2','slug' => 'basic-investment','data' => '{"language":{"en":{"name":"Basic Investment","title":"Basic Investment"},"es":{"name":"Inversi\\u00f3n B\\u00e1sica","title":"Inversi\\u00f3n B\\u00e1sica"},"ar":{"name":"\\u0627\\u0644\\u0627\\u0633\\u062a\\u062b\\u0645\\u0627\\u0631 \\u0627\\u0644\\u0623\\u0633\\u0627\\u0633\\u064a","title":"\\u0627\\u0644\\u0627\\u0633\\u062a\\u062b\\u0645\\u0627\\u0631 \\u0627\\u0644\\u0623\\u0633\\u0627\\u0633\\u064a"}}}','plan_duration' => '3','profit_return_type' => 'DAILY-BASIS','minimum_investment' => '60.00000000','minimum_investment_offer' => NULL,'maximum_investment' => '120.00000000','profit' => '10.00000000','profit_percentage' => '3.00000000','image' => 'b39206e2-eb3c-4182-b665-d6bf46063835.webp','status' => '1','created_at' => '2023-12-30 08:11:55','updated_at' => '2023-12-30 08:11:55'),
            array('id' => '3','slug' => 'growth-plus','data' => '{"language":{"en":{"name":"Growth Plus","title":"Growth Plus"},"es":{"name":"Crecimiento Plus","title":"Crecimiento Plus"},"ar":{"name":"\\u0627\\u0644\\u0646\\u0645\\u0648 \\u0632\\u0627\\u0626\\u062f","title":"\\u0627\\u0644\\u0646\\u0645\\u0648 \\u0632\\u0627\\u0626\\u062f"}}}','plan_duration' => '7','profit_return_type' => 'ONE-TIME','minimum_investment' => '120.00000000','minimum_investment_offer' => '110.00000000','maximum_investment' => '400.00000000','profit' => '15.00000000','profit_percentage' => '1.00000000','image' => '28cfcf71-0c10-4ddf-8efe-8b773fc94ff0.webp','status' => '1','created_at' => '2023-12-30 08:14:02','updated_at' => '2023-12-30 08:14:02'),
            array('id' => '4','slug' => 'advanced-portfolio','data' => '{"language":{"en":{"name":"Advanced Portfolio","title":"Advanced Portfolio"},"es":{"name":"Portafolio avanzado","title":"Portafolio avanzado"},"ar":{"name":"\\u0627\\u0644\\u0645\\u062d\\u0641\\u0638\\u0629 \\u0627\\u0644\\u0645\\u062a\\u0642\\u062f\\u0645\\u0629","title":"\\u0627\\u0644\\u0645\\u062d\\u0641\\u0638\\u0629 \\u0627\\u0644\\u0645\\u062a\\u0642\\u062f\\u0645\\u0629"}}}','plan_duration' => '14','profit_return_type' => 'DAILY-BASIS','minimum_investment' => '320.00000000','minimum_investment_offer' => NULL,'maximum_investment' => '650.00000000','profit' => '15.00000000','profit_percentage' => '8.00000000','image' => '2e650512-2288-4efe-9334-cf3fef41a39e.webp','status' => '1','created_at' => '2023-12-30 08:15:21','updated_at' => '2023-12-30 08:15:21'),
            array('id' => '5','slug' => 'premium-strategy','data' => '{"language":{"en":{"name":"Premium Strategy","title":"Premium Strategy"},"es":{"name":"Estrategia premium","title":"Estrategia premium"},"ar":{"name":"\\u0627\\u0633\\u062a\\u0631\\u0627\\u062a\\u064a\\u062c\\u064a\\u0629 \\u0645\\u062a\\u0645\\u064a\\u0632\\u0629","title":"\\u0627\\u0633\\u062a\\u0631\\u0627\\u062a\\u064a\\u062c\\u064a\\u0629 \\u0645\\u062a\\u0645\\u064a\\u0632\\u0629"}}}','plan_duration' => '21','profit_return_type' => 'DAILY-BASIS','minimum_investment' => '450.00000000','minimum_investment_offer' => '400.00000000','maximum_investment' => '850.00000000','profit' => '18.00000000','profit_percentage' => '10.00000000','image' => 'bc1a5a3a-df42-456c-b332-50a04595dcca.webp','status' => '1','created_at' => '2023-12-30 08:16:32','updated_at' => '2023-12-30 08:16:32'),
            array('id' => '6','slug' => 'ultimate-wealth','data' => '{"language":{"en":{"name":"Ultimate Wealth","title":"Ultimate Wealth"},"es":{"name":"Riqueza definitiva","title":"Riqueza definitiva"},"ar":{"name":"\\u0627\\u0644\\u062b\\u0631\\u0648\\u0629 \\u0627\\u0644\\u0645\\u0637\\u0644\\u0642\\u0629","title":"\\u0627\\u0644\\u062b\\u0631\\u0648\\u0629 \\u0627\\u0644\\u0645\\u0637\\u0644\\u0642\\u0629"}}}','plan_duration' => '29','profit_return_type' => 'ONE-TIME','minimum_investment' => '750.00000000','minimum_investment_offer' => '700.00000000','maximum_investment' => '1000.00000000','profit' => '20.00000000','profit_percentage' => '10.00000000','image' => '5222085c-cbec-4457-aeda-9763b239baad.webp','status' => '1','created_at' => '2023-12-30 08:20:58','updated_at' => '2023-12-30 08:20:58')
          );
        InvestmentPlan::insert($investment_plans);
    }
}
