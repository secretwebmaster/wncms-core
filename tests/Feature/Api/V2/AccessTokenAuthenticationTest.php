<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AccessTokenService;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\CredentialParser;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class AccessTokenAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private ApiSession $session;

    private Website $website;

    /**
     * Register an isolated access-token authentication endpoint.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        auth()->forgetGuards();
        $this->user = User::create([
            'username' => 'access-token-user-'.uniqid(),
            'email' => 'access-token-user-'.uniqid().'@example.test',
            'password' => 'not-a-real-password',
            'email_verified_at' => now(),
        ]);
        $this->website = Website::firstOrFail();
        $this->user->websites()->syncWithoutDetaching([$this->website->id]);
        $this->session = ApiSession::create([
            'session_id' => 'session-'.uniqid(),
            'user_id' => $this->user->id,
            'refresh_transport' => 'json',
            'remembered' => false,
            'expires_at' => now()->addDay(),
        ]);

        Route::match(['GET', 'POST'], '/api/v2/_test/access-token', function (Request $request) {
            $context = $request->attributes->get('wncms_api_v2_auth_context');

            return app(ApiResponseFactory::class)->success([
                'actor_id' => $request->user()?->getAuthIdentifier(),
                'credential_type' => $context?->credentialType(),
                'credential_id' => $context?->credentialPublicId(),
                'session_id' => $context?->sessionPublicId(),
            ]);
        })->middleware(['api_v2_request_id', 'api_v2_token_auth']);
    }

    /**
     * Verify issuing an access token returns plaintext once and stores only its hash.
     *
     * @return void
     */
    public function test_issue_uses_token_hasher_and_persists_hash_only(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 12:00:00');

        try {
            $issued = app(AccessTokenService::class)->issue(
                $this->user,
                $this->session,
                ['links.read'],
                [$this->website->id],
            );

            $this->assertMatchesRegularExpression('/^wncms_at_[0-9A-HJKMNP-TV-Z]{26}\.[A-Za-z0-9_-]{43}$/', $issued['token']);
            $this->assertSame('2026-08-13T12:15:00+00:00', $issued['expires_at']->toIso8601String());
            $this->assertSame(hash('sha256', $issued['token']), $issued['model']->token_hash);
            $this->assertNotSame($issued['token'], $issued['model']->token_hash);
            $this->assertSame(['links.read'], $issued['model']->abilities);
            $this->assertSame([$this->website->id], $issued['model']->website_ids);
            $this->assertStringNotContainsString($issued['token'], json_encode($issued['model'], JSON_THROW_ON_ERROR));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    /**
     * Verify access tokens cannot be issued across user/session ownership boundaries.
     *
     * @return void
     */
    public function test_issue_rejects_a_session_owned_by_another_user_without_creating_a_token(): void
    {
        $otherUser = User::create([
            'username' => 'access-token-other-'.uniqid(),
            'email' => 'access-token-other-'.uniqid().'@example.test',
            'password' => 'not-a-real-password',
            'email_verified_at' => now(),
        ]);
        $before = DB::table('api_access_tokens')->count();

        try {
            app(AccessTokenService::class)->issue($otherUser, $this->session, [], []);
            $this->fail('Cross-user session issuance should be rejected.');
        } catch (AuthenticationException $exception) {
            $this->assertSame('authentication.invalid_token', $exception->getMessage());
        }

        $this->assertSame($before, DB::table('api_access_tokens')->count());
    }

    /**
     * Verify access tokens cannot be issued from a revoked session.
     *
     * @return void
     */
    public function test_issue_rejects_a_revoked_session_without_creating_a_token(): void
    {
        $this->session->update(['revoked_at' => now()]);
        $before = DB::table('api_access_tokens')->count();

        try {
            app(AccessTokenService::class)->issue($this->user, $this->session, [], []);
            $this->fail('Revoked session issuance should be rejected.');
        } catch (AuthenticationException $exception) {
            $this->assertSame('authentication.token_revoked', $exception->getMessage());
        }

        $this->assertSame($before, DB::table('api_access_tokens')->count());
    }

    /**
     * Verify access tokens cannot be issued from an expired session.
     *
     * @return void
     */
    public function test_issue_rejects_an_expired_session_without_creating_a_token(): void
    {
        $this->session->update(['expires_at' => now()->subSecond()]);
        $before = DB::table('api_access_tokens')->count();

        try {
            app(AccessTokenService::class)->issue($this->user, $this->session, [], []);
            $this->fail('Expired session issuance should be rejected.');
        } catch (AuthenticationException $exception) {
            $this->assertSame('authentication.invalid_token', $exception->getMessage());
        }

        $this->assertSame($before, DB::table('api_access_tokens')->count());
    }

    /**
     * Verify valid access credentials establish both Laravel actor and immutable context.
     *
     * @return void
     */
    public function test_valid_access_token_sets_actor_context_and_preserves_request_id(): void
    {
        $issued = $this->issueAccessToken();
        $requestId = '123e4567-e89b-42d3-a456-426614174501';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$issued['token'],
            'X-Request-ID' => $requestId,
        ])->getJson('/api/v2/_test/access-token');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('meta.request_id', $requestId)
            ->assertJsonPath('data.actor_id', $this->user->id)
            ->assertJsonPath('data.credential_type', ApiCredential::TYPE_INTERACTIVE_ACCESS)
            ->assertJsonPath('data.credential_id', $issued['model']->token_id)
            ->assertJsonPath('data.session_id', $this->session->session_id);
    }

    /**
     * Verify expired and revoked access tokens fail before endpoint execution.
     *
     * @return void
     */
    public function test_expired_and_revoked_access_tokens_are_rejected(): void
    {
        $expired = $this->issueAccessToken(['expires_at' => now()->subSecond()]);

        $this->requestWithToken($expired['token'])
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.access_token_expired');

        $revoked = $this->issueAccessToken(['revoked_at' => now()]);

        $this->requestWithToken($revoked['token'])
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.token_revoked');
    }

    /**
     * Verify existing service tokens may be permanent while an explicit past expiry is rejected.
     *
     * @return void
     */
    public function test_service_token_validation_accepts_null_expiry_and_rejects_past_expiry(): void
    {
        $permanent = $this->issueServiceToken(['expires_at' => null]);

        $this->requestWithToken($permanent['token'])
            ->assertOk()
            ->assertJsonPath('data.credential_type', ApiCredential::TYPE_SERVICE_TOKEN);

        $expired = $this->issueServiceToken(['expires_at' => now()->subSecond()]);

        $this->requestWithToken($expired['token'])
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.access_token_expired');
    }

    /**
     * Verify suspended actors and inactive interactive sessions cannot authenticate.
     *
     * @return void
     */
    public function test_disabled_user_and_session_are_rejected(): void
    {
        $suspended = Role::findOrCreate('suspended', 'web');
        $this->user->assignRole($suspended);
        $disabledUserToken = $this->issueAccessToken();

        $this->requestWithToken($disabledUserToken['token'])
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.invalid_token');

        $this->user->removeRole($suspended);
        $this->session->update(['revoked_at' => now()]);
        $disabledSessionToken = $this->issueAccessToken();

        $this->requestWithToken($disabledSessionToken['token'])
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.token_revoked');
    }

    /**
     * Verify a failed new-format lookup never falls back to an enabled legacy token row.
     *
     * @return void
     */
    public function test_failed_new_prefix_never_falls_back_to_legacy_authentication(): void
    {
        $plainText = 'wncms_at_missing-id.secret';
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => get_class($this->user),
            'tokenable_id' => $this->user->id,
            'name' => 'prefix-isolation',
            'token' => hash('sha256', $plainText),
            'abilities' => json_encode(['*']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        config([
            'wncms.auth_security.legacy_personal_tokens_enabled' => true,
            'wncms.auth_security.legacy_personal_tokens_cutoff_at' => now()->addDay()->toIso8601String(),
        ]);

        $this->requestWithToken($plainText)
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.invalid_token');
    }

    /**
     * Verify v2 authentication accepts only the Authorization bearer transport.
     *
     * @return void
     */
    public function test_request_body_api_token_is_rejected_even_for_a_valid_access_token(): void
    {
        $issued = $this->issueAccessToken();

        auth()->forgetGuards();
        $response = $this->postJson('/api/v2/_test/access-token', [
            'api_token' => $issued['token'],
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.missing_token');
    }

    /**
     * Create an access-token fixture using the production hasher and owned table.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{token: string, model: \Wncms\Models\ApiAccessToken}
     */
    private function issueAccessToken(array $overrides = []): array
    {
        $material = app(TokenHasher::class)->issue('wncms_at');
        $modelClass = wncms()->getModelClass('api_access_token');
        $model = $modelClass::create(array_merge([
            'token_id' => $material['public_id'],
            'token_hash' => $material['hash'],
            'user_id' => $this->user->id,
            'session_id' => $this->session->id,
            'abilities' => ['links.read'],
            'website_ids' => [$this->website->id],
            'expires_at' => now()->addMinutes(15),
        ], $overrides));

        return ['token' => $material['plain_text'], 'model' => $model];
    }

    /**
     * Create a service-token fixture without exercising its later lifecycle implementation.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{token: string, model: \Wncms\Models\ApiServiceToken}
     */
    private function issueServiceToken(array $overrides = []): array
    {
        $material = app(TokenHasher::class)->issue('wncms_st');
        $modelClass = wncms()->getModelClass('api_service_token');
        $model = $modelClass::create(array_merge([
            'token_id' => $material['public_id'],
            'token_hash' => $material['hash'],
            'user_id' => $this->user->id,
            'name' => 'Validation fixture',
            'ability_template' => 'read_only',
            'abilities' => ['links.read'],
            'website_ids' => [$this->website->id],
            'expires_at' => now()->addDay(),
        ], $overrides));

        return ['token' => $material['plain_text'], 'model' => $model];
    }

    /**
     * Send an isolated request after clearing session-authenticated state.
     *
     * @param  string  $token
     * @return \Illuminate\Testing\TestResponse
     */
    private function requestWithToken(string $token)
    {
        auth()->forgetGuards();

        return $this->withToken($token)->getJson('/api/v2/_test/access-token');
    }
}
