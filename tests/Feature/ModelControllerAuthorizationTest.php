<?php

namespace Wncms\Tests\Feature;

use Wncms\Auth\Api\V2\AccessTokenService;
use Wncms\Http\Middleware\HasWebsite;
use Wncms\Http\Middleware\IsInstalled;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class ModelControllerAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([HasWebsite::class, IsInstalled::class]);
    }

    public function test_member_cannot_use_model_mutation_endpoints(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $target = User::factory()->create([
            'username' => uniqid('model_guard_target_', true),
        ]);

        $this->actingAs($member);

        foreach ($this->modelMutationPayloads($target) as $routeName => $payload) {
            $response = $this->postJson(route($routeName), $payload);

            $response->assertForbidden();
            $response->assertJsonPath('status', 'fail');
            $response->assertJsonPath('code', 403);
        }

        $this->assertSame($target->username, $target->fresh()->username);
        $this->assertNotNull($target->fresh());
    }

    public function test_admin_and_superadmin_can_use_model_update_endpoint(): void
    {
        foreach (['admin', 'superadmin'] as $roleName) {
            $user = User::factory()->create();
            $user->assignRole($roleName);

            $target = User::factory()->create([
                'username' => uniqid("model_guard_{$roleName}_before_", true),
            ]);
            $updatedUsername = uniqid("model_guard_{$roleName}_after_", true);

            $response = $this->actingAs($user)->postJson(route('models.update'), [
                'model' => 'user',
                'model_id' => $target->id,
                'column' => 'username',
                'value' => $updatedUsername,
            ]);

            $response->assertOk();
            $response->assertJsonPath('status', 'success');
            $this->assertSame($updatedUsername, $target->fresh()->username);
        }
    }

    public function test_member_cannot_use_api_v2_model_update_to_modify_another_user(): void
    {
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');

        $member = User::factory()->create();
        $member->assignRole('member');
        $website = Website::firstOrFail();
        $member->websites()->syncWithoutDetaching([$website->id]);
        $session = ApiSession::create([
            'session_id' => 'model-guard-session-'.uniqid(),
            'user_id' => $member->id,
            'refresh_transport' => 'json',
            'remembered' => false,
            'expires_at' => now()->addDay(),
        ]);

        $target = User::factory()->create([
            'api_token' => uniqid('model_guard_original_token_', true),
        ]);
        $attemptedToken = uniqid('model_guard_owned_token_', true);

        $token = app(AccessTokenService::class)->issue(
            $member,
            $session,
            ['models.write'],
            [$website->id],
        )['token'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v2/backend/models/update', [
                'model' => 'user',
                'model_id' => $target->id,
                'column' => 'api_token',
                'value' => $attemptedToken,
                'website_id' => $website->id,
            ]);

        $response->assertForbidden();
        $response->assertJsonPath('status', 'fail');
        $response->assertJsonPath('meta.error_code', 'authorization.permission_denied');
        $this->assertNotSame($attemptedToken, $target->fresh()->api_token);
    }

    protected function modelMutationPayloads(User $target): array
    {
        return [
            'models.update' => [
                'model' => 'user',
                'model_id' => $target->id,
                'column' => 'username',
                'value' => uniqid('model_guard_blocked_', true),
            ],
            'models.bulk_delete' => [
                'model' => 'user',
                'model_ids' => [$target->id],
            ],
            'models.bulk_force_delete' => [
                'model' => 'user',
                'model_ids' => [$target->id],
            ],
        ];
    }
}
