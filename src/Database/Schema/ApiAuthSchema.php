<?php

namespace Wncms\Database\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class ApiAuthSchema
{
    /**
     * Create the interactive session table.
     *
     * @return void
     */
    public static function createApiSessions(): void
    {
        if (self::hasCompatibleTable('api_sessions')) {
            return;
        }

        Schema::create('api_sessions', function (Blueprint $table): void {
            $table->id();
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
    }

    /**
     * Create the short-lived interactive access token table.
     *
     * @return void
     */
    public static function createApiAccessTokens(): void
    {
        if (self::hasCompatibleTable('api_access_tokens')) {
            return;
        }

        Schema::create('api_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('token_id', 64)->unique();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('api_sessions')->cascadeOnDelete();
            $table->json('abilities');
            $table->json('website_ids');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
            $table->index(['session_id', 'revoked_at']);
        });
    }

    /**
     * Create the one-time refresh token table.
     *
     * @return void
     */
    public static function createApiRefreshTokens(): void
    {
        if (self::hasCompatibleTable('api_refresh_tokens')) {
            return;
        }

        Schema::create('api_refresh_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('token_id', 64)->unique();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('api_sessions')->cascadeOnDelete();
            $table->string('family_id', 64)->index();
            $table->string('parent_token_id', 64)->nullable()->index();
            $table->string('replaced_by_token_id', 64)->nullable()->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
            $table->index(['session_id', 'family_id']);
        });
    }

    /**
     * Create the scoped service token table.
     *
     * @return void
     */
    public static function createApiServiceTokens(): void
    {
        if (self::hasCompatibleTable('api_service_tokens')) {
            return;
        }

        Schema::create('api_service_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('token_id', 64)->unique();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('ability_template', 64)->index();
            $table->json('abilities');
            $table->json('website_ids');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    /**
     * Create the append-only security event table.
     *
     * @return void
     */
    public static function createApiSecurityEvents(): void
    {
        if (self::hasCompatibleTable('api_security_events')) {
            return;
        }

        Schema::create('api_security_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id', 64)->unique();
            $table->timestamp('occurred_at')->index();
            $table->string('event_type')->index();
            $table->string('severity', 32)->index();
            $table->string('outcome', 32)->index();
            $table->string('surface', 32)->index();
            $table->string('request_id', 64)->nullable()->index();
            $table->string('run_id', 64)->nullable()->index();
            $table->string('actor_type')->nullable()->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('target_type')->nullable()->index();
            $table->unsignedBigInteger('target_id')->nullable()->index();
            $table->string('credential_type', 64)->nullable()->index();
            $table->string('credential_id', 64)->nullable()->index();
            $table->string('session_id', 64)->nullable()->index();
            $table->json('website_ids')->nullable();
            $table->string('error_code', 128)->nullable()->index();
            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('login_identifier_hash', 64)->nullable()->index();
            $table->string('user_agent_hash', 64)->nullable()->index();
            $table->string('correlation_key_version', 32)->nullable();
            $table->unsignedBigInteger('mutation_audit_id')->nullable()->index();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'occurred_at']);
            $table->index(['actor_type', 'actor_id']);
            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Assert pre-existing WNCMS-owned tables have the complete compatible schema.
     *
     * Missing tables are valid because the authorized updater creates them after this preflight.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public static function assertCompatibleExistingTables(): void
    {
        foreach (array_keys(self::tableDefinitions()) as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            self::assertTableCompatible($table);
        }
    }

    /**
     * Determine whether a table is absent or fully compatible before creation.
     *
     * @param  string  $table
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    private static function hasCompatibleTable(string $table): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        self::assertTableCompatible($table);

        return true;
    }

    /**
     * Assert one WNCMS-owned table matches its canonical columns, indexes, and foreign keys.
     *
     * @param  string  $table
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    private static function assertTableCompatible(string $table): void
    {
        $definition = self::tableDefinitions()[$table];
        $columns = collect(Schema::getColumns($table))->keyBy('name');

        foreach ($definition['columns'] as $column => $expected) {
            $actual = $columns->get($column);

            if ($actual === null) {
                throw new \RuntimeException("Existing {$table} table is incompatible; missing column: {$column}");
            }

            $type = strtolower((string) ($actual['type_name'] ?? $actual['type'] ?? ''));
            if (!in_array($type, $expected['types'], true) || (bool) $actual['nullable'] !== $expected['nullable']) {
                throw new \RuntimeException("Existing {$table} table is incompatible; invalid column: {$column}");
            }
        }

        $indexes = Schema::getIndexes($table);
        foreach ($definition['indexes'] as $expected) {
            $found = collect($indexes)->contains(function (array $index) use ($expected): bool {
                return $index['columns'] === $expected['columns'] && (bool) $index['unique'] === $expected['unique'];
            });

            if (!$found) {
                throw new \RuntimeException("Existing {$table} table is incompatible; missing index: " . implode(',', $expected['columns']));
            }
        }

        $foreignKeys = Schema::getForeignKeys($table);
        foreach ($definition['foreign_keys'] as $expected) {
            $found = collect($foreignKeys)->contains(function (array $foreignKey) use ($expected): bool {
                return $foreignKey['columns'] === [$expected['column']]
                    && $foreignKey['foreign_table'] === $expected['table']
                    && $foreignKey['foreign_columns'] === ['id']
                    && strtolower((string) $foreignKey['on_delete']) === 'cascade';
            });

            if (!$found) {
                throw new \RuntimeException("Existing {$table} table is incompatible; missing cascading foreign key: {$expected['column']}");
            }
        }
    }

    /**
     * Return exact portable metadata for WNCMS-owned authentication tables.
     *
     * @return array<string, array{columns: array<string, array{types: array<int, string>, nullable: bool}>, indexes: array<int, array{columns: array<int, string>, unique: bool}>, foreign_keys: array<int, array{column: string, table: string}>}>
     */
    private static function tableDefinitions(): array
    {
        $integer = ['integer', 'bigint', 'int8'];
        $string = ['varchar', 'character varying', 'nvarchar'];
        $timestamp = ['datetime', 'timestamp', 'timestamp without time zone'];
        $boolean = ['tinyint', 'boolean', 'bool', 'bit'];
        $json = ['text', 'json', 'jsonb', 'nvarchar'];
        $column = fn (array $types, bool $nullable): array => ['types' => $types, 'nullable' => $nullable];

        return [
            'api_sessions' => [
                'columns' => ['session_id' => $column($string, false), 'user_id' => $column($integer, false), 'device_name' => $column($string, true), 'refresh_transport' => $column($string, false), 'remembered' => $column($boolean, false), 'csrf_hash' => $column($string, true), 'last_activity_at' => $column($timestamp, true), 'last_step_up_at' => $column($timestamp, true), 'expires_at' => $column($timestamp, true), 'revoked_at' => $column($timestamp, true), 'revocation_reason' => $column($string, true), 'created_at' => $column($timestamp, true), 'updated_at' => $column($timestamp, true)],
                'indexes' => [['columns' => ['session_id'], 'unique' => true], ['columns' => ['csrf_hash'], 'unique' => true], ['columns' => ['expires_at'], 'unique' => false], ['columns' => ['revoked_at'], 'unique' => false], ['columns' => ['user_id', 'revoked_at'], 'unique' => false]],
                'foreign_keys' => [['column' => 'user_id', 'table' => 'users']],
            ],
            'api_access_tokens' => [
                'columns' => ['token_id' => $column($string, false), 'token_hash' => $column($string, false), 'user_id' => $column($integer, false), 'session_id' => $column($integer, false), 'abilities' => $column($json, false), 'website_ids' => $column($json, false), 'last_used_at' => $column($timestamp, true), 'expires_at' => $column($timestamp, false), 'revoked_at' => $column($timestamp, true), 'created_at' => $column($timestamp, true), 'updated_at' => $column($timestamp, true)],
                'indexes' => [['columns' => ['token_id'], 'unique' => true], ['columns' => ['token_hash'], 'unique' => true], ['columns' => ['expires_at'], 'unique' => false], ['columns' => ['revoked_at'], 'unique' => false], ['columns' => ['user_id', 'revoked_at'], 'unique' => false], ['columns' => ['session_id', 'revoked_at'], 'unique' => false]],
                'foreign_keys' => [['column' => 'user_id', 'table' => 'users'], ['column' => 'session_id', 'table' => 'api_sessions']],
            ],
            'api_refresh_tokens' => [
                'columns' => ['token_id' => $column($string, false), 'token_hash' => $column($string, false), 'user_id' => $column($integer, false), 'session_id' => $column($integer, false), 'family_id' => $column($string, false), 'parent_token_id' => $column($string, true), 'replaced_by_token_id' => $column($string, true), 'consumed_at' => $column($timestamp, true), 'expires_at' => $column($timestamp, true), 'revoked_at' => $column($timestamp, true), 'created_at' => $column($timestamp, true), 'updated_at' => $column($timestamp, true)],
                'indexes' => [['columns' => ['token_id'], 'unique' => true], ['columns' => ['token_hash'], 'unique' => true], ['columns' => ['family_id'], 'unique' => false], ['columns' => ['parent_token_id'], 'unique' => false], ['columns' => ['replaced_by_token_id'], 'unique' => false], ['columns' => ['consumed_at'], 'unique' => false], ['columns' => ['expires_at'], 'unique' => false], ['columns' => ['revoked_at'], 'unique' => false], ['columns' => ['user_id', 'revoked_at'], 'unique' => false], ['columns' => ['session_id', 'family_id'], 'unique' => false]],
                'foreign_keys' => [['column' => 'user_id', 'table' => 'users'], ['column' => 'session_id', 'table' => 'api_sessions']],
            ],
            'api_service_tokens' => [
                'columns' => ['token_id' => $column($string, false), 'token_hash' => $column($string, false), 'user_id' => $column($integer, false), 'name' => $column($string, false), 'ability_template' => $column($string, false), 'abilities' => $column($json, false), 'website_ids' => $column($json, false), 'last_used_at' => $column($timestamp, true), 'expires_at' => $column($timestamp, true), 'revoked_at' => $column($timestamp, true), 'created_at' => $column($timestamp, true), 'updated_at' => $column($timestamp, true)],
                'indexes' => [['columns' => ['token_id'], 'unique' => true], ['columns' => ['token_hash'], 'unique' => true], ['columns' => ['ability_template'], 'unique' => false], ['columns' => ['expires_at'], 'unique' => false], ['columns' => ['revoked_at'], 'unique' => false], ['columns' => ['user_id', 'revoked_at'], 'unique' => false]],
                'foreign_keys' => [['column' => 'user_id', 'table' => 'users']],
            ],
            'api_security_events' => [
                'columns' => ['event_id' => $column($string, false), 'occurred_at' => $column($timestamp, false), 'event_type' => $column($string, false), 'severity' => $column($string, false), 'outcome' => $column($string, false), 'surface' => $column($string, false), 'request_id' => $column($string, true), 'run_id' => $column($string, true), 'actor_type' => $column($string, true), 'actor_id' => $column($integer, true), 'target_type' => $column($string, true), 'target_id' => $column($integer, true), 'credential_type' => $column($string, true), 'credential_id' => $column($string, true), 'session_id' => $column($string, true), 'website_ids' => $column($json, true), 'error_code' => $column($string, true), 'http_status' => $column($integer, true), 'ip_hash' => $column($string, true), 'login_identifier_hash' => $column($string, true), 'user_agent_hash' => $column($string, true), 'correlation_key_version' => $column($string, true), 'mutation_audit_id' => $column($integer, true), 'context' => $column($json, true), 'created_at' => $column($timestamp, true), 'updated_at' => $column($timestamp, true)],
                'indexes' => [['columns' => ['event_id'], 'unique' => true], ['columns' => ['occurred_at'], 'unique' => false], ['columns' => ['event_type'], 'unique' => false], ['columns' => ['severity'], 'unique' => false], ['columns' => ['outcome'], 'unique' => false], ['columns' => ['surface'], 'unique' => false], ['columns' => ['request_id'], 'unique' => false], ['columns' => ['run_id'], 'unique' => false], ['columns' => ['actor_type'], 'unique' => false], ['columns' => ['actor_id'], 'unique' => false], ['columns' => ['target_type'], 'unique' => false], ['columns' => ['target_id'], 'unique' => false], ['columns' => ['credential_type'], 'unique' => false], ['columns' => ['credential_id'], 'unique' => false], ['columns' => ['session_id'], 'unique' => false], ['columns' => ['error_code'], 'unique' => false], ['columns' => ['http_status'], 'unique' => false], ['columns' => ['ip_hash'], 'unique' => false], ['columns' => ['login_identifier_hash'], 'unique' => false], ['columns' => ['user_agent_hash'], 'unique' => false], ['columns' => ['mutation_audit_id'], 'unique' => false], ['columns' => ['event_type', 'occurred_at'], 'unique' => false], ['columns' => ['actor_type', 'actor_id'], 'unique' => false], ['columns' => ['target_type', 'target_id'], 'unique' => false]],
                'foreign_keys' => [],
            ],
        ];
    }
}
