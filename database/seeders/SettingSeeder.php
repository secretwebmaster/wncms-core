<?php

namespace Wncms\Database\Seeders;

use Wncms\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->default_settings() as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }

    public function default_settings()
    {
        return [
            'version' => config('installer.version'),
            'check_beta_functions' => '0',
            'data_cache_time' => '3600',
            'live_data_cache_time' => null,
            'active_models' => '["Advertisement","Menu","Page","Post","Link","Setting","Tag","User"]',
            'request_timeout' => '60',
            'cache_view_count' => 1,
            'enable_cache' => 1,
            'system_name' => 'WNCMS',
            'multi_website' => false,
            'disable_registration' => true,
            'disable_core_update' => true,

            // localization
            'enable_translation' => false,
            'use_accept_language_header' => false,
            'hide_default_locale_in_url' => true,
            'use_locales_mapping' => true,
        ];
    }
}
