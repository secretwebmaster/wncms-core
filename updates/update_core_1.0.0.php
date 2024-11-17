<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '1.0.0';

info("running update_{$thisVersion}.php");

// uppdate user tables
// Check if the index exists and drop it
if (Schema::hasColumn('users', 'email')) {
    $prefix = DB::getTablePrefix();
    Schema::table('users', function (Blueprint $table) use ($prefix) {
        $table->dropUnique($prefix . 'users_email_unique'); // Adjust the index name as needed
    });
}
// Apply the new column definition
Schema::table('users', function (Blueprint $table) {
    $table->string('email')->unique()->nullable()->change();
});

info("updated users table email column to nullable");

uss('core_version', $thisVersion);

info("completed update_{$thisVersion}.php");
