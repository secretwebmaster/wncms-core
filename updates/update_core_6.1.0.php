<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '6.1.0';

info("running update_{$thisVersion}.php");

try {

    Artisan::call('migrate', ['--force' => true]);

    // migrate theme options to new options table
    if (Schema::hasTable('theme_options')) {
        $themeOptions = DB::table('theme_options')->orderBy('id')->get();

        foreach ($themeOptions as $opt) {
            // skip if already migrated
            $exists = DB::table('options')->where([
                'optionable_type' => 'Wncms\Models\Website',
                'optionable_id'   => $opt->website_id,
                'scope'           => 'theme',
                'group'           => $opt->theme,
                'key'             => $opt->key,
                'sort'            => null,
            ])->exists();

            if ($exists) {
                continue;
            }

            // safely decode json, fallback to raw string
            $decoded = json_decode($opt->value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $opt->value;

            DB::table('options')->insert([
                'optionable_type' => 'Wncms\Models\Website',
                'optionable_id'   => $opt->website_id,
                'scope'           => 'theme',
                'group'           => $opt->theme,
                'key'             => $opt->key,
                'sort'            => null,
                'value'           => $value,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    // migrate page template options (correct version)
    if (Schema::hasTable('page_templates')) {

        $templates = DB::table('page_templates')->orderBy('id')->get();

        foreach ($templates as $row) {

            $templateId = $row->template_id;
            $block = json_decode($row->value, true);

            if (!is_array($block)) {
                continue;
            }

            foreach ($block as $sectionKey => $group) {

                if (!is_array($group)) {
                    continue;
                }

                foreach ($group as $fieldKey => $value) {

                    $key = "{$sectionKey}.{$fieldKey}";

                    $exists = DB::table('options')->where([
                        'optionable_type' => 'Wncms\Models\Page',
                        'optionable_id'   => $row->page_id,
                        'scope'           => 'template',
                        'group'           => $templateId,
                        'key'             => $key,
                        'sort'            => $row->sort,
                    ])->exists();

                    if ($exists) {
                        continue;
                    }

                    if (is_array($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }

                    DB::table('options')->insert([
                        'optionable_type' => 'Wncms\Models\Page',
                        'optionable_id'   => $row->page_id,
                        'scope'           => 'template',
                        'group'           => $templateId,
                        'key'             => $key,
                        'sort'            => $row->sort,
                        'value'           => $value,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        }
    }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (\Throwable $e) {
    info("error when running update_{$thisVersion}.php");
    info("Error: " . $e->getMessage());
    return;
}
