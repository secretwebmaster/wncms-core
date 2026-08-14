<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class BladeSecurityApiTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $databaseSnapshot = [];
    private bool $suspendedTestTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (DB::getSchemaBuilder()->getTableListing() as $table) {
            $this->databaseSnapshot[$table] = DB::table($table)->get()->map(static fn ($row): array => (array) $row)->all();
        }
    }

    protected function tearDown(): void
    {
        if ($this->suspendedTestTransaction) {
            while (DB::transactionLevel() > 0) DB::rollBack();
            DB::statement('PRAGMA foreign_keys = OFF');
            foreach (array_reverse(array_keys($this->databaseSnapshot)) as $table) DB::table($table)->delete();
            foreach ($this->databaseSnapshot as $table => $rows) if ($rows !== []) DB::table($table)->insert($rows);
            DB::statement('PRAGMA foreign_keys = ON');
            DB::beginTransaction();
        }
        parent::tearDown();
    }

    public function test_status_requires_permission_and_update_requires_step_up_and_idempotency(): void
    {
        config([
            'wncms-api-v2.idempotency.store' => 'array',
            'wncms-api-v2.auth_security.security_event_correlation.active_key_version' => 'v1',
            'wncms-api-v2.auth_security.security_event_correlation.keys.v1' => [
                'ip' => str_repeat('i', 32), 'login_identifier' => str_repeat('l', 32), 'user_agent' => str_repeat('u', 32),
            ],
            'wncms.auth_security.login_progressive_delay_seconds' => [0],
        ]);
        Cache::flush();
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');

        $password = 'blade-security-password';
        $user = User::create(['username' => uniqid('blade-'), 'email' => uniqid('blade-').'@example.test', 'password' => Hash::make($password)]);
        $access = $this->postJson('/api/v2/backend/auth/login', ['email' => $user->email, 'password' => $password, 'device_name' => 'blade-test'])
            ->assertOk()->json('data.access_token');
        $this->withToken($access)->getJson('/api/v2/backend/security/blade')->assertForbidden();

        $user->givePermissionTo(Permission::findOrCreate('blade_mode_manage', 'web'));
        $this->withToken($access)->getJson('/api/v2/backend/security/blade')->assertOk()->assertJsonPath('data.enabled', true);
        $this->withToken($access)->patchJson('/api/v2/backend/security/blade', ['enabled' => false])
            ->assertBadRequest()->assertJsonPath('meta.error_code', 'idempotency.key_missing');

        $proof = $this->withToken($access)->postJson('/api/v2/backend/auth/reauthenticate', [
            'password' => $password, 'operation' => 'backend.security.blade.update', 'purpose' => 'blade.mode',
        ])->assertOk()->json('data.proof');
        while (DB::transactionLevel() > 0) DB::commit();
        $this->suspendedTestTransaction = true;
        $this->withToken($access)->withHeader('Idempotency-Key', 'blade-update-0001')->withHeader('X-WNCMS-Step-Up', $proof)
            ->patchJson('/api/v2/backend/security/blade', ['enabled' => false])->assertOk()->assertJsonPath('data.enabled', false);
    }
}
