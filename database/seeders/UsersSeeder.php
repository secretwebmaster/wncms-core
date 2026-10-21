<?php

namespace Wncms\Database\Seeders;

use Wncms\Models\User;
use Wncms\Models\UserInfo;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Wncms\Services\Installer\InstallationAdmin;
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
        if (!app()->bound(InstallationAdmin::class)) {
            throw new \RuntimeException('Set administrator credentials through the WNCMS installer before seeding users.');
        }
        $administrator = app(InstallationAdmin::class);

        $adminPayload = [
            'username' => 'admin',
            'email' => $administrator->email,
            'password' => $administrator->passwordHash,
            'email_verified_at' => now(),
            'api_token' => Str::uuid(),
        ];
        if (Schema::hasColumn('users', 'name')) {
            $adminPayload['name'] = 'admin';
        }
        $demoAdmin = User::create($adminPayload);
        $demoAdmin->assignRole('superadmin');
        $demoAdmin->assignRole('admin');





    }
}
