<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '6.0.0';

info("running update_{$thisVersion}.php");

try {

    /*
     |--------------------------------------------------------------------------
     | 1. resources/views/frontend/theme → themes
     |--------------------------------------------------------------------------
     */
    $oldDir = resource_path('views/frontend/theme');
    $newDir = resource_path('views/frontend/themes');

    if (is_dir($oldDir)) {

        if (!is_dir($newDir)) {
            mkdir($newDir, 0755, true);
        }

        foreach (scandir($oldDir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $from = $oldDir . '/' . $file;
            $to   = $newDir . '/' . $file;

            if (!file_exists($to)) {
                @rename($from, $to);
            }
        }

        @rmdir($oldDir);
    }

    /*
     |--------------------------------------------------------------------------
     | 2. public/theme → public/themes
     |--------------------------------------------------------------------------
     */
    $oldPublicDir = public_path('theme');
    $newPublicDir = public_path('themes');

    if (is_dir($oldPublicDir)) {

        if (!is_dir($newPublicDir)) {
            mkdir($newPublicDir, 0755, true);
        }

        foreach (scandir($oldPublicDir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $from = $oldPublicDir . '/' . $file;
            $to   = $newPublicDir . '/' . $file;

            if (!file_exists($to)) {
                @rename($from, $to);
            }
        }

        @rmdir($oldPublicDir);
    }

    /*
     |--------------------------------------------------------------------------
     | 3. tags: order_column → sort
     |--------------------------------------------------------------------------
     */
    if (Schema::hasColumn('tags', 'order_column')) {

        if (!Schema::hasColumn('tags', 'sort')) {
            Schema::table('tags', function (Blueprint $table) {
                $table->integer('sort')->nullable()->after('icon');
            });
        }

        DB::table('tags')->update([
            'sort' => DB::raw('order_column')
        ]);

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('order_column');
        });
    }

    if (Schema::hasColumn('tags', 'sort')) {
        Schema::table('tags', function (Blueprint $table) {
            $table->index('sort');
        });
    }

    /*
     |--------------------------------------------------------------------------
     | 4. order → sort (generic tables)
     |--------------------------------------------------------------------------
     */
    foreach ([
        'links',
        'page_templates',
        'menu_items',
        'advertisements',
        'posts',
    ] as $tableName) {

        if (Schema::hasColumn($tableName, 'order')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('order', 'sort');
            });
        } elseif (!Schema::hasColumn($tableName, 'sort')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('sort')->nullable();
            });
        }
    }

    /*
     |--------------------------------------------------------------------------
     | 5. tags.group column
     |--------------------------------------------------------------------------
     */
    if (!Schema::hasColumn('tags', 'group')) {
        Schema::table('tags', function (Blueprint $table) {
            $table->string('group')->nullable()->after('type');
        });
    }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");

} catch (\Throwable $e) {

    // last-resort guard: NEVER break update chain
    info("error when running update_{$thisVersion}.php");
    info("Error: " . $e->getMessage());

    return;
}
