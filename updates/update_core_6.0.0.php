<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '6.0.0';

// This is a test update to see if version is working
info("running update_{$thisVersion}.php");
try {
    // copy files

    // add migration
    // Artisan::call('migrate', [
    //     '--path' => 'vendor/secretwebmaster/wncms-core/database/migrations',
    //     '--force' => true,
    // ]);

    // migrate laravel-optionable table

    // update existing database

    // add roles

    // add permissions

    // rename dir
    // change resources/views/frontend/theme to resources/views/frontend/themes
    $oldDir = resource_path('views/frontend/theme');
    $newDir = resource_path('views/frontend/themes');
    if (is_dir($oldDir) && !is_dir($newDir)) {
        rename($oldDir, $newDir);
    } elseif (is_dir($oldDir) && is_dir($newDir)) {
        //merge folders
        $files = scandir($oldDir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                rename($oldDir . '/' . $file, $newDir . '/' . $file);
            }
        }
        rmdir($oldDir);
    }

    // change public/theme to public/themes
    $oldPublicDir = public_path('theme');
    $newPublicDir = public_path('themes');
    if (is_dir($oldPublicDir) && !is_dir($newPublicDir)) {
        rename($oldPublicDir, $newPublicDir);
    } elseif (is_dir($oldPublicDir) && is_dir($newPublicDir)) {
        //merge folders
        $files = scandir($oldPublicDir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                rename($oldPublicDir . '/' . $file, $newPublicDir . '/' . $file);
            }
        }
        rmdir($oldPublicDir);
    }

    // tags table (order_column → sort)
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

    // links table (order → sort)
    if (Schema::hasColumn('links', 'order')) {
        Schema::table('links', function (Blueprint $table) {
            $table->renameColumn('order', 'sort');
        });
    } elseif (! Schema::hasColumn('links', 'sort')) {
        Schema::table('links', function (Blueprint $table) {
            $table->integer('sort')->nullable();
        });
    }

    // page_templates table (order → sort)
    if (Schema::hasColumn('page_templates', 'order')) {
        Schema::table('page_templates', function (Blueprint $table) {
            $table->renameColumn('order', 'sort');
        });
    } elseif (! Schema::hasColumn('page_templates', 'sort')) {
        Schema::table('page_templates', function (Blueprint $table) {
            $table->integer('sort')->nullable();
        });
    }

    // menu_items table (order → sort)
    if (Schema::hasColumn('menu_items', 'order')) {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->renameColumn('order', 'sort');
        });
    } elseif (! Schema::hasColumn('menu_items', 'sort')) {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->integer('sort')->nullable();
        });
    }

    // advertisements table (order → sort)
    if (Schema::hasColumn('advertisements', 'order')) {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->renameColumn('order', 'sort');
        });
    } elseif (! Schema::hasColumn('advertisements', 'sort')) {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->integer('sort')->nullable();
        });
    }

    // posts table (order → sort)
    if (Schema::hasColumn('posts', 'order')) {
        Schema::table('posts', function (Blueprint $table) {
            $table->renameColumn('order', 'sort');
        });
    } elseif (! Schema::hasColumn('posts', 'sort')) {
        Schema::table('posts', function (Blueprint $table) {
            $table->integer('sort')->nullable();
        });
    }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (Exception $e) {
    info("error when running update_{$thisVersion}.php");
    info("Error: " . $e->getMessage());
    return;
}
