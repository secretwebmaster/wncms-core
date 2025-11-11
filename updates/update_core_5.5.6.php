<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '5.5.6';

// This is a test update to see if version is working
info("running update_{$thisVersion}.php");
try {
    // copy files

    // add migration
    Artisan::call('migrate', [
        '--path' => 'vendor/secretwebmaster/wncms-core/database/migrations',
        '--force' => true,
    ]);

    // update existing database

    // add roles
    Artisan::call('wncms:create-model-permission package');

    // add permissions

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (Exception $e) {
    info("error when running update_{$thisVersion}.php");
    info("Error: " . $e->getMessage());
    return;
}
