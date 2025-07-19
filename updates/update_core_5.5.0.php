<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '5.4.2';

// This is a test update to see if version is working
info("running update_{$thisVersion}.php");
try {
    // copy files

    // add migration
    // Artisan::call('migrate', [
    //     '--path' => 'vendor/secretwebmaster/wncms-core/database/migrations',
    //     '--force' => true,
    // ]);
    foreach (['faqs', 'menus', 'pages', 'posts', 'search_keywords', 'banners', 'contact_form_submissions'] as $tableName) {
        if (Schema::hasColumn($tableName, 'website_id')) {
            try {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['website_id']);
                });

                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('website_id');
                });
            } catch (\Exception $e) {
            }
        }
    }

    Schema::dropIfExists('post_website');

    if (Schema::hasColumn('products', 'attributes') && !Schema::hasColumn('products', 'properties')) {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('attributes', 'properties');
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
