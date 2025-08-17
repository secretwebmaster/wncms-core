<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '5.5.3';

// This is a test update to see if version is working
info("running update_{$thisVersion}.php");
try {
    // copy files

    // add migration
    // Artisan::call('migrate', [
    //     '--path' => 'vendor/secretwebmaster/wncms-core/database/migrations',
    //     '--force' => true,
    // ]);
    if (!Schema::hasTable('model_has_websites')) {
        Schema::create('model_has_websites', function (Blueprint $table) {
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->morphs('model');
            $table->primary(['website_id', 'model_id', 'model_type']);
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
