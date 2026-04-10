<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '6.3.0';

info("running update_{$thisVersion}.php");

try {
    $hasSocialLoginIndex = function () {
        $driver = Schema::getConnection()->getDriverName();
        $usersTable = DB::getTablePrefix() . 'users';

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$usersTable}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === 'users_social_login_provider_unique') {
                    return true;
                }
            }

            return false;
        }

        return !empty(DB::select("SHOW INDEX FROM {$usersTable} WHERE Key_name = ?", [
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

    if (Schema::hasTable('comments') && Schema::hasColumn('comments', 'user_id')) {
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    if (!Schema::hasTable('personal_access_tokens')) {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (\Throwable $e) {
    info("error when running update_{$thisVersion}.php");
    info('Error: ' . $e->getMessage());
    return;
}
