<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Wncms\Tests\TestCase;

class AuthSecuritySchemaTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Verify fresh installations create only WNCMS-owned authentication tables.
     *
     * @return void
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
     *
     * @return void
     */
    public function test_owned_credential_schema_has_unique_secret_material_and_lifecycle_indexes(): void
    {
        $this->assertUniqueIndex('api_sessions', 'session_id');
        $this->assertUniqueIndex('api_access_tokens', 'token_id');
        $this->assertUniqueIndex('api_access_tokens', 'token_hash');
        $this->assertUniqueIndex('api_refresh_tokens', 'token_id');
        $this->assertUniqueIndex('api_refresh_tokens', 'token_hash');
        $this->assertUniqueIndex('api_service_tokens', 'token_id');
        $this->assertUniqueIndex('api_service_tokens', 'token_hash');
        $this->assertUniqueIndex('api_security_events', 'event_id');

        foreach (['api_access_tokens', 'api_service_tokens'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'abilities'), "{$table}.abilities");
            $this->assertTrue(Schema::hasColumn($table, 'website_ids'), "{$table}.website_ids");
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
     * Verify owned credentials cascade with their owner or session without involving host PAT rows.
     *
     * @return void
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
    }

    /**
     * Verify rolling back owned migrations leaves the host PAT table untouched.
     *
     * @return void
     */
    public function test_owned_auth_migration_rollbacks_drop_only_wncms_tables(): void
    {
        $originalConnection = config('database.default');
        $connection = 'auth_schema_rollback_regression';
        config([
            'database.connections.' . $connection => array_merge(
                config('database.connections.' . $originalConnection),
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
                require __DIR__ . '/../../../../database/migrations/0001_01_01_000041_create_api_sessions_table.php',
                require __DIR__ . '/../../../../database/migrations/0001_01_01_000042_create_api_access_tokens_table.php',
                require __DIR__ . '/../../../../database/migrations/0001_01_01_000043_create_api_refresh_tokens_table.php',
                require __DIR__ . '/../../../../database/migrations/0001_01_01_000044_create_api_service_tokens_table.php',
                require __DIR__ . '/../../../../database/migrations/0001_01_01_000045_create_api_security_events_table.php',
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
     *
     * @param  string  $table
     * @param  string  $column
     * @return void
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
     *
     * @param  string  $table
     * @param  string  $column
     * @return void
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

            if (in_array($column, array_map(fn ($item) => $item->name, $columns), true)) {
                $this->addToAssertionCount(1);
                return;
            }
        }

        $this->fail("Expected {$table}.{$column} to have a unique index.");
    }
}
