<?php

namespace Wncms\Database\Seeders;

use Wncms\Models\User;
use Wncms\Models\UserInfo;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Str;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Generator $faker)
    {
        $demoAdmin = User::create([
            'username' => 'admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('wncms.cc'),
            'email_verified_at' => now(),
            'api_token' => Str::uuid(),
        ]);
        $demoAdmin->assignRole('superadmin');
        $demoAdmin->assignRole('admin');

        $demoManager = User::create([
            'username' => 'manager',
            'email' => 'manager@demo.com',
            'password' => Hash::make('wncms.cc'),
            'email_verified_at' => now(),
            'api_token' => Str::uuid(),
        ]);
        $demoManager->assignRole('manager');



    }
}
