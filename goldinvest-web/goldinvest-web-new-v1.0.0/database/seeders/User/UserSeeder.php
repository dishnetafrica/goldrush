<?php

namespace Database\Seeders\User;

use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'firstname'         => "test",
                'lastname'          => "user1",
                'email'             => "user@appdevs.net",
                'username'          => "testuser1",
                'status'            => true,
                'password'          => Hash::make("appdevs"),
                'email_verified'    => true,
                'kyc_verified'      => true,
                'created_at'        => now(),
                'referral_id'       => '00000001',
                'current_referral_level_id' => 3
            ],
            [
                'firstname'         => "test",
                'lastname'          => "user2",
                'email'             => "user2@appdevs.net",
                'username'          => "testuser2",
                'status'            => true,
                'password'          => Hash::make("appdevs"),
                'email_verified'    => true,
                'kyc_verified'      => true,
                'created_at'        => now(),
                'referral_id'       => '00000002',
                'current_referral_level_id' => 3
            ]

        ];

        User::insert($data);

        $user_wallets = [];
        $id = 1;

        for($user_id = 1; $user_id <= 2; $user_id++){
            $user_wallets[] = [
                'id'    => $id++,
                'user_id' => $user_id,
                'currency_id' => 1,
                'balance' => '10000.00000000',
                'status'  => 1,
                'created_at' => now(),
                'updated_at' => NULL
            ];
        }

        UserWallet::insert($user_wallets);
    }
}
