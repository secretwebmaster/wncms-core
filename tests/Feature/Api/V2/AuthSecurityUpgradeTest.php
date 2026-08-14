<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Database\Schema\ApiAuthSchema;
use Wncms\Tests\TestCase;

class AuthSecurityUpgradeTest extends TestCase
{
    private string $originalConnection;

    private string $upgradeConnection = 'auth_security_upgrade';

    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = (string) config('database.default');
        $path = tempnam(sys_get_temp_dir(), 'wncms-auth-upgrade-');
        if ($path === false) {
            $this->fail('Unable to create the isolated upgrade database.');
        }
        $this->databasePath = $path;
        config([
            'database.connections.'.$this->upgradeConnection => [
                'driver' => 'sqlite',
                'database' => $this->databasePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.default' => $this->upgradeConnection,
            'cache.default' => 'array',
        ]);
        DB::purge($this->upgradeConnection);
        DB::connection($this->upgradeConnection)->getPdo();
        $this->createExistingInstallationBaseline();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::disconnect($this->upgradeConnection);
        DB::purge($this->upgradeConnection);
        config(['database.default' => $this->originalConnection]);
        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }
        parent::tearDown();
    }

    public function test_upgrade_creates_complete_schema_settings_permissions_and_preserves_host_data(): void
    {
        CarbonImmutable::setTestNow('2026-08-14 00:00:00 UTC');
        $patColumns = Schema::getColumnListing('personal_access_tokens');
        DB::table('settings')->insert([
            'key' => 'api_access_token_lifetime_minutes', 'value' => '30', 'group' => '',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->runUpdater();

        foreach ($this->authTables() as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
        $this->assertSame($patColumns, Schema::getColumnListing('personal_access_tokens'));
        $this->assertSame('host-pat-secret', DB::table('personal_access_tokens')->value('token'));
        $this->assertSame('host-content-value', DB::table('host_content')->value('value'));
        $this->assertSame('30', (string) $this->setting('api_access_token_lifetime_minutes'));
        $this->assertSame('1', (string) $this->setting('api_legacy_personal_tokens_enabled'));
        $this->assertSame(
            CarbonImmutable::now('UTC')->addDays(90)->toIso8601String(),
            (string) $this->setting('api_legacy_personal_tokens_cutoff_at'),
        );
        $this->assertSame('7.0.0', (string) $this->setting('core_version'));

        $permissions = [
            'api_token_create', 'api_token_create_cross_site', 'api_token_create_permanent',
            'api_token_index', 'api_token_show', 'api_token_rotate', 'api_token_revoke',
            'security_event_index', 'security_event_show', 'blade_mode_manage',
        ];
        $this->assertSame($permissions, Permission::query()->whereIn('name', $permissions)->orderBy('id')->pluck('name')->all());
        foreach (Role::query()->whereIn('name', ['superadmin', 'admin'])->get() as $role) {
            $this->assertCount(count($permissions), $role->permissions()->whereIn('name', $permissions)->get());
        }
    }

    public function test_upgrade_accepts_compatible_partial_schema_and_is_repeatable(): void
    {
        ApiAuthSchema::createApiSessions();
        $userId = DB::table('users')->value('id');
        DB::table('api_sessions')->insert([
            'session_id' => 'preserved-session', 'user_id' => $userId, 'refresh_transport' => 'json',
            'remembered' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->runUpdater();
        $firstCutoff = $this->setting('api_legacy_personal_tokens_cutoff_at');
        $this->runUpdater();

        $this->assertSame(1, DB::table('api_sessions')->where('session_id', 'preserved-session')->count());
        $this->assertSame($firstCutoff, $this->setting('api_legacy_personal_tokens_cutoff_at'));
        foreach ($this->authTables() as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
    }

    public function test_upgrade_rejects_incompatible_owned_table_before_mutation(): void
    {
        Schema::create('api_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('host_marker');
        });
        $beforeTables = Schema::getTableListing();

        try {
            $this->runUpdater();
            $this->fail('An incompatible WNCMS-owned table must reject the upgrade.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('api_sessions', $exception->getMessage());
        }

        $this->assertSame($beforeTables, Schema::getTableListing());
        $this->assertSame('6.0.0', (string) $this->setting('core_version'));
        $this->assertNull($this->setting('api_legacy_personal_tokens_enabled'));
    }

    public function test_upgrade_failure_after_schema_work_does_not_advance_version(): void
    {
        Schema::drop('permissions');
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('incompatible_marker');
        });

        try {
            $this->runUpdater();
            $this->fail('A permission seed failure must fail the upgrade.');
        } catch (\Throwable) {
            $this->assertSame('6.0.0', (string) $this->setting('core_version'));
        }

        foreach ($this->authTables() as $table) {
            $this->assertFalse(Schema::hasTable($table), "{$table} should roll back on SQLite");
        }
        $this->assertNull($this->setting('api_legacy_personal_tokens_enabled'));
        $this->assertSame('host-pat-secret', DB::table('personal_access_tokens')->value('token'));
    }

    private function createExistingInstallationBaseline(): void
    {
        foreach ([
            '0001_01_01_000010_create_users_table.php',
            '0001_01_01_000030_create_permission_tables.php',
            '0001_01_01_000040_create_personal_access_tokens_table.php',
            '0001_01_01_000130_create_settings_table.php',
        ] as $migrationFile) {
            $migration = require dirname(__DIR__, 4).'/database/migrations/'.$migrationFile;
            $migration->up();
        }

        Schema::create('host_content', function (Blueprint $table): void {
            $table->id();
            $table->string('value');
        });
        $userId = DB::table('users')->insertGetId([
            'username' => 'upgrade-owner', 'email' => 'upgrade-owner@example.test', 'password' => 'hash',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'Host\\User', 'tokenable_id' => $userId, 'name' => 'host token',
            'token' => 'host-pat-secret', 'abilities' => '["host.read"]', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('host_content')->insert(['value' => 'host-content-value']);
        DB::table('settings')->insert([
            'key' => 'core_version', 'value' => '6.0.0', 'group' => '', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['superadmin', 'admin'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function runUpdater(): void
    {
        (static function (): void {
            include dirname(__DIR__, 4).'/updates/update_core_7.0.0.php';
        })();
    }

    private function setting(string $key): mixed
    {
        return DB::table('settings')->where('key', $key)->where('group', '')->value('value');
    }

    /** @return array<int, string> */
    private function authTables(): array
    {
        return [
            'api_sessions', 'api_access_tokens', 'api_refresh_tokens', 'api_service_tokens',
            'api_security_events', 'api_step_up_proofs', 'api_action_plans',
        ];
    }
}
