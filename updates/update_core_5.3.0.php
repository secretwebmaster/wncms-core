<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '5.3.0';

// This is a test update to see if version is working
info("running update_{$thisVersion}.php");
try{
    // copy files

    // add migration

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


