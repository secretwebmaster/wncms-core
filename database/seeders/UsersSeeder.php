<?php

namespace Wncms\Database\Seeders;

use Wncms\Models\User;
use Wncms\Models\UserInfo;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
        $adminPayload = [
            'username' => 'admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('wncms.cc'),
            'email_verified_at' => now(),
            'api_token' => Str::uuid(),
        ];
        if (Schema::hasColumn('users', 'name')) {
            $adminPayload['name'] = 'admin';
        }
        $demoAdmin = User::create($adminPayload);
        $demoAdmin->assignRole('superadmin');
        $demoAdmin->assignRole('admin');

        $managerPayload = [
            'username' => 'manager',
            'email' => 'manager@demo.com',
            'password' => Hash::make('wncms.cc'),
            'email_verified_at' => now(),
            'api_token' => Str::uuid(),
        ];
        if (Schema::hasColumn('users', 'name')) {
            $managerPayload['name'] = 'manager';
        }
        $demoManager = User::create($managerPayload);
        $demoManager->assignRole('manager');



    }
}
