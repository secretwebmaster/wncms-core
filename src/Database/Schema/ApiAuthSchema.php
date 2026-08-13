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
        if (Schema::hasTable('api_sessions')) {
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
        if (Schema::hasTable('api_access_tokens')) {
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
        if (Schema::hasTable('api_refresh_tokens')) {
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
            $table->timestamp('expires_at')->index();
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
        if (Schema::hasTable('api_service_tokens')) {
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
        if (Schema::hasTable('api_security_events')) {
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
        foreach (self::requiredColumns() as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $missingColumns = array_values(array_filter($columns, fn (string $column): bool => !Schema::hasColumn($table, $column)));

            if ($missingColumns !== []) {
                throw new \RuntimeException("Existing {$table} table is incompatible; missing columns: " . implode(', ', $missingColumns));
            }
        }
    }

    /**
     * Return the minimum columns that identify a WNCMS-owned table as compatible.
     *
     * @return array<string, array<int, string>>
     */
    private static function requiredColumns(): array
    {
        return [
            'api_sessions' => ['id', 'session_id', 'user_id', 'refresh_transport', 'csrf_hash', 'expires_at', 'revoked_at'],
            'api_access_tokens' => ['id', 'token_id', 'token_hash', 'user_id', 'session_id', 'abilities', 'website_ids', 'expires_at', 'revoked_at'],
            'api_refresh_tokens' => ['id', 'token_id', 'token_hash', 'user_id', 'session_id', 'family_id', 'consumed_at', 'expires_at', 'revoked_at'],
            'api_service_tokens' => ['id', 'token_id', 'token_hash', 'user_id', 'name', 'ability_template', 'abilities', 'website_ids', 'expires_at', 'revoked_at'],
            'api_security_events' => ['id', 'event_id', 'occurred_at', 'event_type', 'severity', 'outcome', 'surface', 'website_ids', 'context'],
        ];
    }
}
