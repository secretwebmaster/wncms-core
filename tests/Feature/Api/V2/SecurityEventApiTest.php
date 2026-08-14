<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Services\Security\SecurityEventService;
use Wncms\Tests\TestCase;

class SecurityEventApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_event_api_enforces_permissions_scope_filters_and_safe_projection(): void
    {
        $this->configureCorrelation();
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        $password = 'security-event-password';
        $user = User::create(['username' => uniqid('events-'), 'email' => uniqid('events-').'@example.test', 'password' => Hash::make($password)]);
        $own = Website::firstOrFail();
        $other = Website::create(['user_id' => $user->id, 'domain' => uniqid('other-').'.test', 'site_name' => 'Other', 'theme' => 'default']);
        $user->websites()->sync([$own->id]);
        $events = app(SecurityEventService::class);
        $visible = $events->record('auth.login.succeeded', 'info', 'succeeded', [
            'surface' => 'api_v2', 'website_ids' => [$own->id], 'ip' => '203.0.113.1', 'context' => ['reason' => 'test'],
        ]);
        $hidden = $events->record('auth.login.failed', 'warning', 'denied', [
            'surface' => 'api_v2', 'website_ids' => [$other->id], 'ip' => '203.0.113.2', 'context' => ['reason' => 'test'],
        ]);
        $ownGlobal = $events->record('auth.logout.succeeded', 'info', 'succeeded', [
            'surface' => 'api_v2', 'actor_type' => $user::class, 'actor_id' => $user->id, 'website_ids' => [],
        ]);
        $otherGlobal = $events->record('auth.logout.succeeded', 'info', 'succeeded', [
            'surface' => 'api_v2', 'actor_type' => $user::class, 'actor_id' => $user->id + 100000, 'website_ids' => [],
            'credential_id' => 'credential-that-must-not-leak', 'session_id' => 'session-that-must-not-leak',
        ]);
        $access = $this->postJson('/api/v2/backend/auth/login', ['email' => $user->email, 'password' => $password, 'device_name' => 'events-test'])
            ->assertOk()->json('data.access_token');

        $this->withToken($access)->getJson('/api/v2/backend/security/events')->assertForbidden();
        $user->givePermissionTo(Permission::findOrCreate('security_event_index', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('security_event_show', 'web'));

        $response = $this->withToken($access)->getJson('/api/v2/backend/security/events?type=auth.login.succeeded')->assertOk();
        $ids = collect($response->json('data.data'))->pluck('event_id');
        $this->assertTrue($ids->contains($visible->event_id));
        $this->assertFalse($ids->contains($hidden->event_id));
        $payload = json_encode($response->json(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('ip_hash', $payload);
        $this->assertStringNotContainsString('context', $payload);

        $this->withToken($access)->getJson('/api/v2/backend/security/events/'.$hidden->event_id)->assertNotFound();
        $this->withToken($access)->getJson('/api/v2/backend/security/events/'.$visible->event_id)
            ->assertOk()->assertJsonPath('data.event_id', $visible->event_id);
        $this->withToken($access)->getJson('/api/v2/backend/security/events/'.$ownGlobal->event_id)->assertOk();
        $this->withToken($access)->getJson('/api/v2/backend/security/events/'.$otherGlobal->event_id)->assertNotFound();
    }

    private function configureCorrelation(): void
    {
        config([
            'wncms-api-v2.auth_security.security_event_correlation.active_key_version' => 'v1',
            'wncms-api-v2.auth_security.security_event_correlation.keys.v1' => [
                'ip' => str_repeat('i', 32), 'login_identifier' => str_repeat('l', 32), 'user_agent' => str_repeat('u', 32),
            ],
            'wncms.auth_security.login_progressive_delay_seconds' => [0],
        ]);
    }
}
