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
    }elseif(is_dir($oldDir) && is_dir($newDir)){
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
    }elseif(is_dir($oldPublicDir) && is_dir($newPublicDir)){
        //merge folders
        $files = scandir($oldPublicDir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                rename($oldPublicDir . '/' . $file, $newPublicDir . '/' . $file);
            }
        }
        rmdir($oldPublicDir);
    }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (Exception $e) {
    info("error when running update_{$thisVersion}.php");
    info("Error: " . $e->getMessage());
    return;
}
