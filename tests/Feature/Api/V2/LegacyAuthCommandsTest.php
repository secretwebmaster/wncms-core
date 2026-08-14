<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Wncms\Tests\TestCase;

class LegacyAuthCommandsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => 'v1', 'keys' => ['v1' => [
                'ip' => 'legacy-cli-ip-key-123456789012345',
                'login_identifier' => 'legacy-cli-login-key-123456789012',
                'user_agent' => 'legacy-cli-agent-key-123456789012',
            ]],
        ]]);
    }

    public function test_status_and_cutoff_commands_have_stable_json_and_bounds(): void
    {
        $this->artisan('wncms:auth:legacy-status --json')->assertSuccessful()->expectsOutputToContain('"schema"');
        $this->artisan('wncms:auth:legacy-cutoff', ['datetime' => '2026-09-01 00:00:00'])->assertFailed();
        $this->artisan('wncms:auth:legacy-cutoff', ['datetime' => now('UTC')->addDays(366)->toIso8601String(), '--json' => true])->assertFailed();
        $cutoff = now('UTC')->addDays(30)->startOfSecond();
        $this->artisan('wncms:auth:legacy-cutoff', ['datetime' => $cutoff->toIso8601String(), '--json' => true])->assertSuccessful();
        $this->assertSame($cutoff->toIso8601String(), gss('api_legacy_personal_tokens_cutoff_at'));
    }

    public function test_revoke_all_changes_only_acceptance_settings_and_is_idempotent(): void
    {
        $before = DB::table('personal_access_tokens')->count();
        uss('api_legacy_personal_tokens_enabled', 1);
        $this->artisan('wncms:auth:legacy-revoke-all', ['--json' => true])->assertFailed();
        $this->artisan('wncms:auth:legacy-revoke-all', ['--force' => true, '--json' => true])->assertSuccessful();
        $this->artisan('wncms:auth:legacy-revoke-all', ['--force' => true, '--json' => true])->assertSuccessful();
        $this->assertFalse((bool) gss('api_legacy_personal_tokens_enabled'));
        $this->assertSame($before, DB::table('personal_access_tokens')->count());
    }
}
