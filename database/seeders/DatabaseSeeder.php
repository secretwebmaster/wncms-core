<?php

namespace Wncms\Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Artisan::call('storage:link');
        
        $this->call([
            RolesSeeder::class,
            UsersSeeder::class,
            TagSeeder::class,
            SettingSeeder::class,
            ContactFormSeeder::class
        ]);
    }
}
