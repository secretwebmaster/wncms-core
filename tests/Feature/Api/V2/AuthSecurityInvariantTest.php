<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Wncms\Api\V2\ApiContractValidator;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class AuthSecurityInvariantTest extends TestCase
{
    use DatabaseTransactions;

    public function test_contract_has_no_security_errors_and_critical_operations_reject_legacy(): void
    {
        $this->assertSame([], app(ApiContractValidator::class)->validate()['errors']);
        foreach (app(ApiContractRegistry::class)->operations() as $operation) {
            if ($operation->securityRisk === 'critical') {
                $this->assertFalse($operation->legacyTokenAllowed, $operation->id);
                $this->assertNotContains('legacy_personal_access_token', $operation->acceptedCredentialTypes, $operation->id);
            }
        }
    }

    public function test_login_persists_only_hashes_and_unauthorized_requests_do_not_run_event_queries(): void
    {
        config([
            'wncms-api-v2.auth_security.security_event_correlation.active_key_version' => 'v1',
            'wncms-api-v2.auth_security.security_event_correlation.keys.v1' => ['ip' => str_repeat('i', 32), 'login_identifier' => str_repeat('l', 32), 'user_agent' => str_repeat('u', 32)],
            'wncms.auth_security.login_progressive_delay_seconds' => [0],
        ]);
        uss('enable_api_access', 1); uss('api_access_whitelist', ''); uss('api_refresh_transport', 'json');
        $password = 'Invariant-Password-123!';
        $user = User::create(['username' => uniqid('invariant-'), 'email' => uniqid('invariant-').'@example.test', 'password' => Hash::make($password)]);
        $user->websites()->sync([Website::firstOrFail()->id]);
        $response = $this->postJson('/api/v2/backend/auth/login', ['email' => $user->email, 'password' => $password, 'device_name' => 'invariant'])->assertOk();
        $access = $response->json('data.access_token');
        $refresh = $response->json('data.refresh_token');
        $this->assertFalse(DB::table('api_access_tokens')->where('token_hash', $access)->exists());
        $this->assertFalse(DB::table('api_refresh_tokens')->where('token_hash', $refresh)->exists());
        $this->assertTrue(DB::table('api_access_tokens')->where('token_hash', hash('sha256', $access))->exists());
        $this->assertTrue(DB::table('api_refresh_tokens')->where('token_hash', hash('sha256', $refresh))->exists());

        $before = DB::table('api_security_events')->count();
        $this->getJson('/api/v2/backend/security/events')->assertUnauthorized();
        $this->assertSame($before, DB::table('api_security_events')->count());
    }
}
