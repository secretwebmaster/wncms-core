<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class ApiGuardOrderTest extends TestCase
{
    use DatabaseTransactions;

    private int $domainExecutions = 0;

    private User $user;

    private ApiSession $session;

    private Website $website;

    /**
     * Register a fully guarded endpoint with deterministic authorization requirements.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        auth()->forgetGuards();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('guarded_domain_read', 'web');

        $this->user = User::create([
            'username' => 'guard-order-user-'.uniqid(),
            'email' => 'guard-order-user-'.uniqid().'@example.test',
            'password' => 'not-a-real-password',
            'email_verified_at' => now(),
        ]);
        $this->website = Website::firstOrFail();
        $this->user->websites()->syncWithoutDetaching([$this->website->id]);
        $this->session = ApiSession::create([
            'session_id' => 'guard-session-'.uniqid(),
            'user_id' => $this->user->id,
            'refresh_transport' => 'json',
            'remembered' => false,
            'expires_at' => now()->addDay(),
        ]);

        Route::get('/api/v2/_test/ordered-guards', function (Request $request) {
            $this->domainExecutions++;
            $website = $request->attributes->get('wncms_api_v2_website');

            return app(ApiResponseFactory::class)->success([
                'website_id' => $website?->getKey(),
                'website_identity' => $request->attributes->get('wncms_api_v2_website_identity'),
            ]);
        })->middleware([
            'api_v2_request_id',
            'api_v2_website_scope',
            'api_v2_permission:guarded_domain_read',
            'api_v2_ability:links.read',
            'api_v2_token_auth',
        ]);
    }

    /**
     * Verify ability denial prevents permission, website, and domain evaluation.
     *
     * @return void
     */
    public function test_ability_denial_short_circuits_later_guards_and_domain_execution(): void
    {
        $this->user->givePermissionTo('guarded_domain_read');
        $token = $this->token([], [$this->website->id]);

        $response = $this->guardedRequest($token, [
            'website_id' => PHP_INT_MAX,
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authorization.ability_denied');
        $this->assertSame(0, $this->domainExecutions);
    }

    /**
     * Verify permission denial follows ability and prevents website/domain evaluation.
     *
     * @return void
     */
    public function test_permission_denial_short_circuits_website_and_domain_execution(): void
    {
        $token = $this->token(['links.read'], [$this->website->id]);

        $response = $this->guardedRequest($token, [
            'website_id' => PHP_INT_MAX,
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authorization.permission_denied');
        $this->assertSame(0, $this->domainExecutions);
    }

    /**
     * Verify website scope is the final authorization guard before domain execution.
     *
     * @return void
     */
    public function test_website_denial_short_circuits_domain_execution(): void
    {
        $this->user->givePermissionTo('guarded_domain_read');
        $otherWebsite = Website::create([
            'user_id' => $this->user->id,
            'domain' => 'guard-other-'.uniqid().'.test',
            'site_name' => 'Guard Other Website',
            'theme' => 'default',
        ]);
        $this->user->websites()->syncWithoutDetaching([$otherWebsite->id]);
        $token = $this->token(['links.read'], [$this->website->id]);

        $response = $this->guardedRequest($token, [
            'website_id' => $otherWebsite->id,
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'website.scope_denied');
        $this->assertSame(0, $this->domainExecutions);
    }

    /**
     * Verify numeric IDs and canonical website keys select the same stable website identity.
     *
     * @return void
     */
    public function test_explicit_website_id_and_key_resolve_the_same_stable_identity(): void
    {
        $this->user->givePermissionTo('guarded_domain_read');
        $token = $this->token(['links.read'], [$this->website->id]);

        $this->guardedRequest($token, ['website_id' => $this->website->id])
            ->assertOk()
            ->assertJsonPath('data.website_id', $this->website->id)
            ->assertJsonPath('data.website_identity', 'website:'.$this->website->id);

        $this->guardedRequest($token, ['website_key' => 'website:'.$this->website->id])
            ->assertOk()
            ->assertJsonPath('data.website_id', $this->website->id)
            ->assertJsonPath('data.website_identity', 'website:'.$this->website->id);

        $this->assertSame(2, $this->domainExecutions);
    }

    /**
     * Verify changing a website domain does not change token or idempotency identity.
     *
     * @return void
     */
    public function test_domain_changes_do_not_change_the_explicit_website_identity(): void
    {
        $this->user->givePermissionTo('guarded_domain_read');
        $token = $this->token(['links.read'], [$this->website->id]);
        $key = 'website:'.$this->website->id;

        $this->guardedRequest($token, ['website_key' => $key])
            ->assertOk()
            ->assertJsonPath('data.website_identity', $key);

        $this->website->update(['domain' => 'guard-renamed-'.uniqid().'.test']);

        $this->guardedRequest($token, ['website_key' => $key])
            ->assertOk()
            ->assertJsonPath('data.website_identity', $key);
        $this->assertSame(2, $this->domainExecutions);
    }

    /**
     * Verify actor website access remains an independent ceiling over token scope.
     *
     * @return void
     */
    public function test_token_scope_cannot_bypass_current_actor_website_access(): void
    {
        $this->user->givePermissionTo('guarded_domain_read');
        $token = $this->token(['links.read'], [$this->website->id]);
        $this->user->websites()->detach($this->website->id);
        $this->website->update(['user_id' => null]);

        $this->guardedRequest($token, ['website_id' => $this->website->id])
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'website.scope_denied');
        $this->assertSame(0, $this->domainExecutions);
    }

    /**
     * Verify every guard failure retains the outer request identifier.
     *
     * @return void
     */
    public function test_guard_failure_preserves_the_request_id(): void
    {
        $requestId = '123e4567-e89b-42d3-a456-426614174502';
        $token = $this->token([], [$this->website->id]);

        $response = $this->withHeader('X-Request-ID', $requestId)
            ->withToken($token)
            ->getJson('/api/v2/_test/ordered-guards?website_id='.$this->website->id);

        $response
            ->assertForbidden()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('meta.request_id', $requestId);
    }

    /**
     * Create a scoped access-token fixture.
     *
     * @param  array<int, string>  $abilities
     * @param  array<int, int>  $websiteIds
     * @return string
     */
    private function token(array $abilities, array $websiteIds): string
    {
        $material = app(TokenHasher::class)->issue('wncms_at');
        $modelClass = wncms()->getModelClass('api_access_token');
        $modelClass::create([
            'token_id' => $material['public_id'],
            'token_hash' => $material['hash'],
            'user_id' => $this->user->id,
            'session_id' => $this->session->id,
            'abilities' => $abilities,
            'website_ids' => $websiteIds,
            'expires_at' => now()->addMinutes(15),
        ]);

        return $material['plain_text'];
    }

    /**
     * Send a request through the complete ordered guard chain.
     *
     * @param  string  $token
     * @param  array<string, mixed>  $query
     * @return \Illuminate\Testing\TestResponse
     */
    private function guardedRequest(string $token, array $query)
    {
        auth()->forgetGuards();

        return $this->withToken($token)->getJson('/api/v2/_test/ordered-guards?'.http_build_query($query));
    }
}
