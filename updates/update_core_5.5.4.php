<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '5.5.4';

// This is a test update to see if version is working
info("running update_{$thisVersion}.php");
try {
    // copy files

    // add migration
    // Artisan::call('migrate', [
    //     '--path' => 'vendor/secretwebmaster/wncms-core/database/migrations',
    //     '--force' => true,
    // ]);
    Schema::dropIfExists('banners');

    if (Schema::hasColumn('pages', 'website_id')) {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('website_id');
        });
    }
    
    if (Schema::hasColumn('contact_form_submissions', 'website_id')) {
        Schema::table('contact_form_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('website_id');
        });
    }

    if (Schema::hasColumn('menus', 'website_id')) {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('website_id');
        });
    }

    // update existing database

    // add roles

    // add permissions

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (Exception $e) {
    info("error when running update_{$thisVersion}.php");
    info("Error: " . $e->getMessage());
    return;
}
