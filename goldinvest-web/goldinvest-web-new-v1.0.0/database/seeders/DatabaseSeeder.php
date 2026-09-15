<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Database\Seeders\User\UserSeeder;
use Database\Seeders\Admin\RoleSeeder;
use Database\Seeders\Admin\AdminSeeder;
use Database\Seeders\Admin\CurrencySeeder;
use Database\Seeders\Admin\LanguageSeeder;
use Database\Seeders\Admin\SetupKycSeeder;
use Database\Seeders\Admin\SetupSeoSeeder;
use Database\Seeders\Admin\ExtensionSeeder;
use Database\Seeders\Admin\SetupPageSeeder;
use Database\Seeders\Admin\UsefulLinkSeeder;
use Database\Seeders\Admin\AppSettingsSeeder;
use Database\Seeders\Admin\AdminHasRoleSeeder;
use Database\Seeders\Admin\AnnouncementCategorySeeder;
use Database\Seeders\Admin\AnnouncementSeeder;
use Database\Seeders\Admin\AppOnBoardScreenSeeder;
use Database\Seeders\Admin\SiteSectionsSeeder;
use Database\Seeders\Admin\BasicSettingsSeeder;
use Database\Seeders\Admin\GoldStockSeeder;
use Database\Seeders\Admin\InvestmentPlanSeeder;
use Database\Seeders\Admin\MoneyOutSettingsSeeder;
use Database\Seeders\Admin\PaymentGatewaySeeder;
use Database\Seeders\Admin\TransactionSettingSeeder;
use Database\Seeders\Fresh\BasicSettingsSeeder as FreshBasicSettingsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //Demo Seeder
        // $this->call([
        //     AdminSeeder::class,
        //     RoleSeeder::class,
        //     TransactionSettingSeeder::class,
        //     CurrencySeeder::class,
        //     BasicSettingsSeeder::class,
        //     SetupSeoSeeder::class,
        //     AppSettingsSeeder::class,
        //     AppOnBoardScreenSeeder::class,
        //     SiteSectionsSeeder::class,
        //     SetupKycSeeder::class,
        //     ExtensionSeeder::class,
        //     AdminHasRoleSeeder::class,
        //     UserSeeder::class,
        //     SetupPageSeeder::class,
        //     LanguageSeeder::class,
        //     UsefulLinkSeeder::class,
        //     PaymentGatewaySeeder::class,
        //     AnnouncementCategorySeeder::class,
        //     AnnouncementSeeder::class,
        //     GoldStockSeeder::class,
        //     InvestmentPlanSeeder::class,
        //     MoneyOutSettingsSeeder::class,
        //     ReferralSettingSeeder::class,
        //     ReferralLevelPackageSeeder::class
        // ]);

        //Fresh Seeder
        $this->call([
            AdminSeeder::class,
            RoleSeeder::class,
            TransactionSettingSeeder::class,
            CurrencySeeder::class,
            FreshBasicSettingsSeeder::class,
            SetupSeoSeeder::class,
            AppSettingsSeeder::class,
            AppOnBoardScreenSeeder::class,
            SiteSectionsSeeder::class,
            SetupKycSeeder::class,
            ExtensionSeeder::class,
            AdminHasRoleSeeder::class,
            SetupPageSeeder::class,
            LanguageSeeder::class,
            UsefulLinkSeeder::class,
            PaymentGatewaySeeder::class,
            AnnouncementCategorySeeder::class,
            AnnouncementSeeder::class,
            MoneyOutSettingsSeeder::class,
            ReferralSettingSeeder::class,
            ReferralLevelPackageSeeder::class,
        ]);
    }
}
