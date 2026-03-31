<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '6.2.3';

info("running update_{$thisVersion}.php");

try {
    $hasSocialLoginIndex = function () {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('users')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === 'users_social_login_provider_unique') {
                    return true;
                }
            }

            return false;
        }

        return !empty(DB::select('SHOW INDEX FROM users WHERE Key_name = ?', [
            'users_social_login_provider_unique',
        ]));
    };

    if (Schema::hasTable('users')) {
        if (!Schema::hasColumn('users', 'social_login_type') || !Schema::hasColumn('users', 'social_login_id')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'social_login_type')) {
                    $table->string('social_login_type')->nullable()->after('password');
                }

                if (!Schema::hasColumn('users', 'social_login_id')) {
                    $table->string('social_login_id')->nullable()->after('social_login_type');
                }
            });
        }

        if (Schema::hasColumn('users', 'social_login_type') && Schema::hasColumn('users', 'social_login_id') && !$hasSocialLoginIndex()) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['social_login_type', 'social_login_id'], 'users_social_login_provider_unique');
            });
        }
    }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (\Throwable $e) {
    info("error when running update_{$thisVersion}.php");
    info('Error: ' . $e->getMessage());
    return;
}
