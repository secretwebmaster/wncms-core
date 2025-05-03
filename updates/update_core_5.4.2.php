<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '5.4.2';

// This is a test update to see if version is working
info("running update_{$thisVersion}.php");
try{
    // copy files

    // add migration
    // Artisan::call('migrate', [
    //     '--path' => 'vendor/secretwebmaster/wncms-core/database/migrations',
    //     '--force' => true,
    // ]);
    Schema::table('links', function (Blueprint $table) {
        $table->text('description')->nullable()->change();
    });

    // update existing database

    // add roles

    // add permissions


    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
}catch(Exception $e){
    info("error when running update_{$thisVersion}.php");
    info("Error: ".$e->getMessage());
    return;
}


