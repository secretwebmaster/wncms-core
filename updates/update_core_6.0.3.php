<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '6.0.3';

// This is a test update to see if version is working
info("running update_{$thisVersion}.php");
try {


} catch (Exception $e) {
    info("error when running update_{$thisVersion}.php");
    info("Error: " . $e->getMessage());
    return;
}
