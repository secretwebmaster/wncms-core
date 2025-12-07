<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '6.1.0';

// This is a test update to see if version is working
info("running update_{$thisVersion}.php");
try {
    // migrations
    // move theme options to new options table
    // suggest to save theme option once in backend after update
       // Migrate theme options
    // $themeOptions = DB::table('theme_options')->get();

    // foreach ($themeOptions as $opt) {
    //     DB::table('options')->insert([
    //         'optionable_type' => 'Wncms\Models\Website',
    //         'optionable_id'   => $opt->website_id,
    //         'scope'           => 'theme',
    //         'group'           => $opt->theme,
    //         'sort'            => null,
    //         'key'             => $opt->key,
    //         'value'           => $this->castValue($opt->value),
    //         'created_at'      => now(),
    //         'updated_at'      => now(),
    //     ]);
    // }

    // // Migrate page template blocks
    // $templates = DB::table('page_templates')->get();

    // foreach ($templates as $row) {
    //     DB::table('options')->insert([
    //         'optionable_type' => 'Wncms\Models\Page',
    //         'optionable_id'   => $row->page_id,
    //         'scope'           => 'template',
    //         'group'           => $row->template_id,
    //         'sort'            => $row->sort,
    //         'key'             => null,
    //         'value'           => json_decode($row->value, true),
    //         'created_at'      => now(),
    //         'updated_at'      => now(),
    //     ]);
    // }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (Exception $e) {
    info("error when running update_{$thisVersion}.php");
    info("Error: " . $e->getMessage());
    return;
}
