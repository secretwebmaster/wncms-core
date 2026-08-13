<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Wncms\Database\Schema\ApiAuthSchema;
use Wncms\Models\ApiRefreshToken;
use Wncms\Tests\TestCase;

class AuthSecuritySchemaTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Verify fresh installations create only WNCMS-owned authentication tables.
     */
    public function test_fresh_schema_contains_owned_auth_tables_without_altering_pat(): void
    {
        foreach (['api_sessions', 'api_access_tokens', 'api_refresh_tokens', 'api_service_tokens', 'api_security_events'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }

        $this->assertFalse(Schema::hasColumn('personal_access_tokens', 'website_ids'));
        $this->assertFalse(Schema::hasColumn('personal_access_tokens', 'family_id'));
    }

    /**
     * Verify credential identifiers, hashes, scopes, and lifecycle fields are owned by WNCMS tables.
     */
    public function test_owned_credential_schema_has_unique_secret_material_and_lifecycle_indexes(): void
    {
        $this->assertUniqueIndex('api_sessions', 'session_id');
        $this->assertFalse(collect(Schema::getIndexes('api_sessions'))->contains(
            static fn (array $index): bool => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['csrf_hash'],
        ));
        $this->assertUniqueIndex('api_access_tokens', 'token_id');
        $this->assertUniqueIndex('api_access_tokens', 'token_hash');
        $this->assertUniqueIndex('api_refresh_tokens', 'token_id');
        $this->assertUniqueIndex('api_refresh_tokens', 'token_hash');
        $this->assertFalse(collect(Schema::getIndexes('api_refresh_tokens'))->contains(
            static fn (array $index): bool => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['csrf_hash'],
        ));
        $this->assertUniqueIndex('api_service_tokens', 'token_id');
        $this->assertUniqueIndex('api_service_tokens', 'token_hash');
        $this->assertUniqueIndex('api_security_events', 'event_id');
        $this->assertUniqueIndex('api_security_events', 'aggregate_key');

        foreach (['api_access_tokens', 'api_service_tokens'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'abilities'), "{$table}.abilities");
            $this->assertTrue(Schema::hasColumn($table, 'website_ids'), "{$table}.website_ids");
        }

        foreach ([
            ['api_access_tokens', 'abilities'],
            ['api_access_tokens', 'website_ids'],
            ['api_service_tokens', 'abilities'],
            ['api_service_tokens', 'website_ids'],
            ['api_security_events', 'website_ids'],
            ['api_security_events', 'context'],
        ] as [$table, $column]) {
            $this->assertJsonColumn($table, $column);
        }

        foreach ([
            ['api_sessions', 'user_id'],
            ['api_sessions', 'expires_at'],
            ['api_sessions', 'revoked_at'],
            ['api_access_tokens', 'user_id'],
            ['api_access_tokens', 'session_id'],
            ['api_access_tokens', 'expires_at'],
            ['api_access_tokens', 'revoked_at'],
            ['api_refresh_tokens', 'user_id'],
            ['api_refresh_tokens', 'session_id'],
            ['api_refresh_tokens', 'family_id'],
            ['api_refresh_tokens', 'expires_at'],
            ['api_refresh_tokens', 'revoked_at'],
            ['api_service_tokens', 'user_id'],
            ['api_service_tokens', 'expires_at'],
            ['api_service_tokens', 'revoked_at'],
            ['api_security_events', 'occurred_at'],
        ] as [$table, $column]) {
            $this->assertColumnIsIndexed($table, $column);
        }
    }

    /**
     * Verify aggregate identity cannot produce duplicate security-event rows.
     */
    public function test_security_event_aggregate_key_is_unique_when_present(): void
    {
        $attributes = [
            'event_id' => 'aggregate-identity-first',
            'aggregate_key' => hash('sha256', 'aggregate-identity'),
            'occurred_at' => now(),
            'event_type' => 'auth.login.failed',
            'severity' => 'warning',
            'outcome' => 'denied',
            'surface' => 'api_v2',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('api_security_events')->insert($attributes);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        DB::table('api_security_events')->insert([
            ...$attributes,
            'event_id' => 'aggregate-identity-second',
        ]);
    }

    /**
     * Verify JSON refresh credentials can persist multiple portable nullable CSRF proofs.
     */
    public function test_multiple_json_sessions_and_refresh_rows_allow_null_csrf_proofs(): void
    {
        $userId = DB::table('users')->insertGetId([
            'username' => 'nullable-csrf-owner',
            'email' => 'nullable-csrf-owner@example.test',
            'password' => 'not-a-real-password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([1, 2] as $suffix) {
            $sessionId = DB::table('api_sessions')->insertGetId([
                'session_id' => "nullable-csrf-session-{$suffix}",
                'csrf_hash' => null,
                'user_id' => $userId,
                'refresh_transport' => 'json',
                'remembered' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('api_refresh_tokens')->insert([
                'token_id' => "nullable-csrf-token-{$suffix}",
                'token_hash' => hash('sha256', "nullable-csrf-secret-{$suffix}"),
                'csrf_hash' => null,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'family_id' => 'nullable-csrf-family',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertSame(2, DB::table('api_sessions')->where('user_id', $userId)->whereNull('csrf_hash')->count());
        $this->assertSame(2, DB::table('api_refresh_tokens')->where('user_id', $userId)->whereNull('csrf_hash')->count());
    }

    /**
     * Verify owned credentials cascade with their owner or session without involving host PAT rows.
     */
    public function test_owned_credential_foreign_keys_cascade_for_users_and_sessions(): void
    {
        $userId = DB::table('users')->insertGetId([
            'username' => 'auth-schema-owner',
            'email' => 'auth-schema-owner@example.test',
            'password' => 'not-a-real-password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sessionId = DB::table('api_sessions')->insertGetId([
            'session_id' => 'session-public-id',
            'user_id' => $userId,
            'refresh_transport' => 'json',
            'csrf_hash' => hash('sha256', 'csrf'),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('api_access_tokens')->insert([
            'token_id' => 'access-public-id',
            'token_hash' => hash('sha256', 'access'),
            'user_id' => $userId,
            'session_id' => $sessionId,
            'abilities' => json_encode(['auth:read']),
            'website_ids' => json_encode([1]),
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('api_refresh_tokens')->insert([
            'token_id' => 'refresh-public-id',
            'token_hash' => hash('sha256', 'refresh'),
            'user_id' => $userId,
            'session_id' => $sessionId,
            'family_id' => 'refresh-family-id',
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('api_service_tokens')->insert([
            'token_id' => 'service-public-id',
            'token_hash' => hash('sha256', 'service'),
            'user_id' => $userId,
            'name' => 'schema-test',
            'ability_template' => 'read_only',
            'abilities' => json_encode(['auth:read']),
            'website_ids' => json_encode([1]),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('api_sessions')->where('id', $sessionId)->delete();

        $this->assertSame(0, DB::table('api_access_tokens')->where('token_id', 'access-public-id')->count());
        $this->assertSame(0, DB::table('api_refresh_tokens')->where('token_id', 'refresh-public-id')->count());

        DB::table('users')->where('id', $userId)->delete();

        $this->assertSame(0, DB::table('api_service_tokens')->where('token_id', 'service-public-id')->count());

        $secondUserId = DB::table('users')->insertGetId([
            'username' => 'auth-schema-direct-owner',
            'email' => 'auth-schema-direct-owner@example.test',
            'password' => 'not-a-real-password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondSessionId = DB::table('api_sessions')->insertGetId([
            'session_id' => 'direct-owner-session-public-id',
            'user_id' => $secondUserId,
            'refresh_transport' => 'json',
            'csrf_hash' => hash('sha256', 'direct-owner-csrf'),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('api_access_tokens')->insert([
            'token_id' => 'direct-owner-access-public-id',
            'token_hash' => hash('sha256', 'direct-owner-access'),
            'user_id' => $secondUserId,
            'session_id' => $secondSessionId,
            'abilities' => json_encode(['auth:read']),
            'website_ids' => json_encode([1]),
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('api_refresh_tokens')->insert([
            'token_id' => 'direct-owner-refresh-public-id',
            'token_hash' => hash('sha256', 'direct-owner-refresh'),
            'user_id' => $secondUserId,
            'session_id' => $secondSessionId,
            'family_id' => 'direct-owner-refresh-family-id',
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('api_service_tokens')->insert([
            'token_id' => 'direct-owner-service-public-id',
            'token_hash' => hash('sha256', 'direct-owner-service'),
            'user_id' => $secondUserId,
            'name' => 'direct-owner-schema-test',
            'ability_template' => 'read_only',
            'abilities' => json_encode(['auth:read']),
            'website_ids' => json_encode([1]),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $secondUserId)->delete();

        $this->assertSame(0, DB::table('api_sessions')->where('session_id', 'direct-owner-session-public-id')->count());
        $this->assertSame(0, DB::table('api_access_tokens')->where('token_id', 'direct-owner-access-public-id')->count());
        $this->assertSame(0, DB::table('api_refresh_tokens')->where('token_id', 'direct-owner-refresh-public-id')->count());
        $this->assertSame(0, DB::table('api_service_tokens')->where('token_id', 'direct-owner-service-public-id')->count());
    }

    /**
     * Verify permanent refresh tokens remain active until explicitly revoked.
     */
    public function test_permanent_refresh_token_is_active_until_revoked(): void
    {
        $userId = DB::table('users')->insertGetId([
            'username' => 'permanent-refresh-owner',
            'email' => 'permanent-refresh-owner@example.test',
            'password' => 'not-a-real-password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sessionId = DB::table('api_sessions')->insertGetId([
            'session_id' => 'permanent-refresh-session',
            'user_id' => $userId,
            'refresh_transport' => 'json',
            'csrf_hash' => hash('sha256', 'permanent-refresh-csrf'),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token = ApiRefreshToken::create([
            'token_id' => 'permanent-refresh-token',
            'token_hash' => hash('sha256', 'permanent-refresh-token'),
            'user_id' => $userId,
            'session_id' => $sessionId,
            'family_id' => 'permanent-refresh-family',
            'expires_at' => null,
        ]);

        $this->assertNull($token->expires_at);
        $this->assertTrue(ApiRefreshToken::active()->whereKey($token->id)->exists());

        $token->update(['consumed_at' => now()]);

        $this->assertFalse(ApiRefreshToken::active()->whereKey($token->id)->exists());

        $token->update(['consumed_at' => null]);

        $this->assertTrue(ApiRefreshToken::active()->whereKey($token->id)->exists());

        $token->update(['revoked_at' => now()]);

        $this->assertFalse(ApiRefreshToken::active()->whereKey($token->id)->exists());
    }

    /**
     * Verify compatibility checks reject a same-name table that is missing required columns.
     */
    public function test_schema_compatibility_rejects_same_name_table_with_missing_columns(): void
    {
        $this->withCompatibilityDatabase(function (): void {
            Schema::create('api_sessions', function (Blueprint $table): void {
                $table->id();
            });

            $this->expectException(\RuntimeException::class);
            ApiAuthSchema::assertCompatibleExistingTables();
        });
    }

    /**
     * Verify compatibility checks reject a same-name table that omits its primary key contract.
     */
    public function test_schema_compatibility_rejects_same_name_table_without_id_primary_key(): void
    {
        $this->withCompatibilityDatabase(function (): void {
            Schema::create('api_sessions', function (Blueprint $table): void {
                $table->string('session_id', 64)->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('device_name', 120)->nullable();
                $table->string('refresh_transport', 16)->default('json');
                $table->boolean('remembered')->default(false);
                $table->string('csrf_hash', 64)->nullable()->unique();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamp('last_step_up_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('revoked_at')->nullable()->index();
                $table->string('revocation_reason')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'revoked_at']);
            });

            $this->expectException(\RuntimeException::class);
            ApiAuthSchema::createApiSessions();
        });
    }

    /**
     * Verify compatibility checks reject a same-name table without its public-ID uniqueness constraint.
     */
    public function test_schema_compatibility_rejects_same_name_table_without_unique_index(): void
    {
        $this->withCompatibilityDatabase(function (): void {
            ApiAuthSchema::createApiSessions();
            Schema::table('api_sessions', function (Blueprint $table): void {
                $table->dropUnique(['session_id']);
            });

            $this->expectException(\RuntimeException::class);
            ApiAuthSchema::assertCompatibleExistingTables();
        });
    }

    /**
     * Verify a composite unique index cannot satisfy a required single-column unique contract.
     */
    public function test_unique_index_assertion_rejects_composite_unique_index_for_single_column_contract(): void
    {
        $this->withCompatibilityDatabase(function (): void {
            Schema::create('api_unique_helper_fixture', function (Blueprint $table): void {
                $table->id();
                $table->string('csrf_hash');
                $table->unsignedBigInteger('user_id');
                $table->unique(['csrf_hash', 'user_id']);
            });

            $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
            $this->assertUniqueIndex('api_unique_helper_fixture', 'csrf_hash');
        });
    }

    /**
     * Verify compatibility checks reject a same-name table without a cascading owner foreign key.
     */
    public function test_schema_compatibility_rejects_same_name_table_without_cascading_foreign_key(): void
    {
        $this->withCompatibilityDatabase(function (): void {
            ApiAuthSchema::createApiSessions();
            Schema::table('api_sessions', function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
            });

            $this->expectException(\RuntimeException::class);
            ApiAuthSchema::assertCompatibleExistingTables();
        });
    }

    /**
     * Verify rolling back owned migrations leaves the host PAT table untouched.
     */
    public function test_owned_auth_migration_rollbacks_drop_only_wncms_tables(): void
    {
        $originalConnection = config('database.default');
        $connection = 'auth_schema_rollback_regression';
        config([
            'database.connections.'.$connection => array_merge(
                config('database.connections.'.$originalConnection),
                ['database' => ':memory:']
            ),
            'database.default' => $connection,
        ]);
        DB::purge($connection);

        try {
            Schema::create('personal_access_tokens', function (Blueprint $table): void {
                $table->id();
                $table->string('host_marker');
            });

            $migrations = [
                require __DIR__.'/../../../../database/migrations/0001_01_01_000041_create_api_sessions_table.php',
                require __DIR__.'/../../../../database/migrations/0001_01_01_000042_create_api_access_tokens_table.php',
                require __DIR__.'/../../../../database/migrations/0001_01_01_000043_create_api_refresh_tokens_table.php',
                require __DIR__.'/../../../../database/migrations/0001_01_01_000044_create_api_service_tokens_table.php',
                require __DIR__.'/../../../../database/migrations/0001_01_01_000045_create_api_security_events_table.php',
            ];

            foreach ($migrations as $migration) {
                $migration->up();
            }

            foreach (array_reverse($migrations) as $migration) {
                $migration->down();
            }

            $this->assertTrue(Schema::hasTable('personal_access_tokens'));
            $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'host_marker'));

            foreach (['api_sessions', 'api_access_tokens', 'api_refresh_tokens', 'api_service_tokens', 'api_security_events'] as $table) {
                $this->assertFalse(Schema::hasTable($table), $table);
            }
        } finally {
            DB::disconnect($connection);
            DB::purge($connection);
            config(['database.default' => $originalConnection]);
        }
    }

    /**
     * Assert a column is covered by an index on SQLite prepared test databases.
     */
    private function assertColumnIsIndexed(string $table, string $column): void
    {
        $indexes = DB::select("PRAGMA index_list('{$table}')");

        foreach ($indexes as $index) {
            $indexName = (string) $index->name;
            $columns = DB::select("PRAGMA index_info('{$indexName}')");

            if (in_array($column, array_map(fn ($item) => $item->name, $columns), true)) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail("Expected {$table}.{$column} to be indexed.");
    }

    /**
     * Assert a column has a unique index on SQLite prepared test databases.
     */
    private function assertUniqueIndex(string $table, string $column): void
    {
        $indexes = DB::select("PRAGMA index_list('{$table}')");

        foreach ($indexes as $index) {
            if ((int) $index->unique !== 1) {
                continue;
            }

            $indexName = (string) $index->name;
            $columns = DB::select("PRAGMA index_info('{$indexName}')");

            if (array_map(fn ($item) => $item->name, $columns) === [$column]) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail("Expected {$table}.{$column} to have a unique index.");
    }

    /**
     * Assert a JSON column retains its native declaration across supported drivers.
     */
    private function assertJsonColumn(string $table, string $column): void
    {
        $columns = Schema::getColumns($table);
        $definition = collect($columns)->firstWhere('name', $column);

        $this->assertNotNull($definition, "{$table}.{$column}");
        $type = strtolower((string) ($definition['type_name'] ?? $definition['type'] ?? ''));
        $driver = DB::connection()->getDriverName();

        $expectedTypes = match ($driver) {
            'sqlite' => ['text'],
            'mysql', 'mariadb' => ['json'],
            'pgsql' => ['json', 'jsonb'],
            'sqlsrv' => ['nvarchar'],
            default => throw new \RuntimeException("Unsupported schema driver [{$driver}]."),
        };

        $this->assertContains($type, $expectedTypes, "{$table}.{$column}");
    }

    /**
     * Run a compatibility fixture against an isolated SQLite database.
     */
    private function withCompatibilityDatabase(callable $callback): void
    {
        $originalConnection = config('database.default');
        $connection = 'auth_schema_compatibility_regression';
        config([
            'database.connections.'.$connection => array_merge(
                config('database.connections.'.$originalConnection),
                ['database' => ':memory:']
            ),
            'database.default' => $connection,
        ]);
        DB::purge($connection);

        try {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
            });

            $callback();
        } finally {
            DB::disconnect($connection);
            DB::purge($connection);
            config(['database.default' => $originalConnection]);
        }
    }
}
