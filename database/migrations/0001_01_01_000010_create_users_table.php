<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('nickname')->nullable();
                $table->string('username');
                $table->string('email')->unique()->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->string('password');
                $table->string('social_login_type')->nullable();
                $table->string('social_login_id')->nullable();
                $table->string('api_token', 80)->unique()->nullable()->default(null);
                $table->rememberToken();
                $table->timestamps();

                // 推薦系統
                $table->unsignedBigInteger('referrer_id')->nullable();
                $table->foreign('referrer_id')->references('id')->on('users')->nullOnDelete();
                $table->unique(['social_login_type', 'social_login_id'], 'users_social_login_provider_unique');
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'first_name')) {
                    $table->string('first_name')->nullable()->after('id');
                }
                if (!Schema::hasColumn('users', 'last_name')) {
                    $table->string('last_name')->nullable()->after('first_name');
                }
                if (!Schema::hasColumn('users', 'nickname')) {
                    $table->string('nickname')->nullable()->after('last_name');
                }
                if (!Schema::hasColumn('users', 'username')) {
                    $table->string('username')->nullable()->after('nickname');
                }
                if (!Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
                }
                if (!Schema::hasColumn('users', 'social_login_type')) {
                    $table->string('social_login_type')->nullable()->after('password');
                }
                if (!Schema::hasColumn('users', 'social_login_id')) {
                    $table->string('social_login_id')->nullable()->after('social_login_type');
                }
                if (!Schema::hasColumn('users', 'api_token')) {
                    $table->string('api_token', 80)->nullable()->default(null)->after('social_login_id');
                }
                if (!Schema::hasColumn('users', 'referrer_id')) {
                    $table->unsignedBigInteger('referrer_id')->nullable()->after('remember_token');
                }
            });
        }

        if (Schema::hasColumn('users', 'social_login_type') && Schema::hasColumn('users', 'social_login_id') && !$this->hasIndex('users', 'users_social_login_provider_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['social_login_type', 'social_login_id'], 'users_social_login_provider_unique');
            });
        }

        if (Schema::hasColumn('users', 'api_token') && !$this->hasIndex('users', 'users_api_token_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('api_token');
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        $tableName = DB::getTablePrefix() . $table;

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$tableName}')");
            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'pgsql') {
            $row = DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ? LIMIT 1',
                [$tableName, $indexName]
            );

            return $row !== null;
        }

        return !empty(DB::select("SHOW INDEX FROM {$tableName} WHERE Key_name = ?", [$indexName]));
    }

};
