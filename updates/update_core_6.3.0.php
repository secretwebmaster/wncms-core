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

    $isCommentsUserIdNullable = function () {
        $driver = Schema::getConnection()->getDriverName();
        $commentsTable = DB::getTablePrefix() . 'comments';

        if ($driver === 'sqlite') {
            $columns = DB::select("PRAGMA table_info('{$commentsTable}')");
            foreach ($columns as $column) {
                if (($column->name ?? null) === 'user_id') {
                    return ((int) ($column->notnull ?? 1)) === 0;
                }
            }

            return false;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $databaseName = DB::getDatabaseName();
            $row = DB::selectOne(
                'SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
                [$databaseName, $commentsTable, 'user_id']
            );

            return strtoupper((string) ($row->IS_NULLABLE ?? 'NO')) === 'YES';
        }

        if ($driver === 'pgsql') {
            $row = DB::selectOne(
                'SELECT is_nullable FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ? LIMIT 1',
                [$commentsTable, 'user_id']
            );

            return strtoupper((string) ($row->is_nullable ?? 'NO')) === 'YES';
        }

        return false;
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

    if (Schema::hasTable('comments') && Schema::hasColumn('comments', 'user_id') && !$isCommentsUserIdNullable()) {
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

    $databaseConfigPath = base_path('config/database.php');
    if (is_file($databaseConfigPath) && is_readable($databaseConfigPath) && is_writable($databaseConfigPath)) {
        $databaseConfigContent = file_get_contents($databaseConfigPath);
        if ($databaseConfigContent !== false && str_contains($databaseConfigContent, 'PDO::MYSQL_ATTR_SSL_CA')) {
            $replacement = "(defined('Pdo\\\\Mysql::ATTR_SSL_CA') ? constant('Pdo\\\\Mysql::ATTR_SSL_CA') : constant('PDO::MYSQL_ATTR_SSL_CA'))";
            $updatedDatabaseConfigContent = preg_replace('/PDO::MYSQL_ATTR_SSL_CA(?=\s*=>)/', $replacement, $databaseConfigContent);

            if (is_string($updatedDatabaseConfigContent) && $updatedDatabaseConfigContent !== $databaseConfigContent) {
                file_put_contents($databaseConfigPath, $updatedDatabaseConfigContent);
                info("patched {$databaseConfigPath} to avoid PDO::MYSQL_ATTR_SSL_CA deprecation");
            }
        }
    } else {
        info("skip patching config/database.php due to missing file or insufficient permissions at {$databaseConfigPath}");
    }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (\Throwable $e) {
    info("error when running update_{$thisVersion}.php");
    info('Error: ' . $e->getMessage());
    return;
}
