<?php

namespace App\Models;

if (! class_exists(TrustedWidget::class, false)) {
    class TrustedWidget extends \Wncms\Models\BaseModel
    {
        public static $modelKey = 'trusted_widget';

        protected $table = 'websites';

        protected $guarded = [];
    }
}

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\Risk\ActionPlanService;
use Wncms\Api\V2\Risk\OperationRiskContextResolver;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Http\Middleware\EnforceApiV2RiskPolicy;
use Wncms\Http\Middleware\ResolveApiV2RiskContext;
use Wncms\Http\Middleware\ResolveApiV2WebsiteScope;
use Wncms\Models\ApiSession;
use Wncms\Models\Channel;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\Fixtures\Api\V2\MismatchedModelKey;
use Wncms\Tests\Fixtures\Api\V2\NonStaticModelKey;
use Wncms\Tests\Fixtures\Api\V2\Overrides\TrustedWidget as TrustedWidgetOverride;
use Wncms\Tests\Fixtures\Api\V2\PrivateModelKey;
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
     */
    protected function setUp(): void
    {
        parent::setUp();

        uss('api_high_risk_action_mode', 'direct');

        auth()->forgetGuards();
        app(PermissionRegistrar::class)->registerPermissions(Gate::getFacadeRoot());
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
     * Verify an absent or malformed selector has a stable missing-scope failure.
     */
    public function test_absent_and_malformed_website_selectors_return_scope_missing(): void
    {
        $this->user->givePermissionTo('guarded_domain_read');
        $token = $this->token(['links.read'], [$this->website->id]);

        $this->guardedRequest($token, [])
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'website.scope_missing');

        $this->guardedRequest($token, ['website_key' => 'not-a-canonical-key'])
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'website.scope_missing');

        $otherWebsite = Website::create([
            'user_id' => $this->user->id,
            'domain' => 'guard-contradictory-'.uniqid().'.test',
            'site_name' => 'Guard Contradictory Website',
            'theme' => 'default',
        ]);
        $this->guardedRequest($token, [
            'website_id' => $this->website->id,
            'website_key' => 'website:'.$otherWebsite->id,
        ])
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'website.scope_missing');

        $this->assertSame(0, $this->domainExecutions);
    }

    /**
     * Verify a production resource route enforces credential, ability, permission, and scope in order.
     */
    public function test_links_index_route_enforces_the_production_guard_chain_before_domain_execution(): void
    {
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        Permission::findOrCreate('link_index', 'web');

        $withoutAbility = $this->token([], [$this->website->id]);
        $this->withToken($withoutAbility)
            ->getJson('/api/v2/backend/links?website_id='.PHP_INT_MAX)
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authorization.ability_denied');

        $withAbility = $this->token(['links.read'], [$this->website->id]);
        $this->withToken($withAbility)
            ->getJson('/api/v2/backend/links?website_id='.PHP_INT_MAX)
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authorization.permission_denied');

        $this->user->givePermissionTo('link_index');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->withToken($withAbility)
            ->getJson('/api/v2/backend/links')
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'website.scope_missing');

        $otherWebsite = Website::create([
            'user_id' => $this->user->id,
            'domain' => 'production-guard-other-'.uniqid().'.test',
            'site_name' => 'Production Guard Other Website',
            'theme' => 'default',
        ]);
        $this->user->websites()->syncWithoutDetaching([$otherWebsite->id]);

        $this->withToken($withAbility)
            ->getJson('/api/v2/backend/links?website_id='.$otherWebsite->id)
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'website.scope_denied');

        $this->withToken($withAbility)
            ->getJson('/api/v2/backend/links?website_id='.$this->website->id)
            ->assertOk();
    }

    /**
     * Verify production route declarations retain parameterized guards in execution order.
     */
    public function test_production_resource_route_declares_parameterized_guards_in_order(): void
    {
        $route = Route::getRoutes()->getByName('api.v2.backend.links.index');

        $this->assertNotNull($route);
        $this->assertMiddlewareOrder($route->gatherMiddleware(), [
            'api_v2_token_auth',
            'api_v2_ability:links.read',
            'api_v2_permission:link_index',
            'api_v2_website_scope',
            'api_v2_risk_context',
            'api_v2_risk',
        ]);
        $this->assertNotContains('api_v2_has_website', $route->gatherMiddleware());
    }

    /**
     * Verify high-risk routes resolve context before idempotency and risk confirmation.
     */
    public function test_high_risk_route_resolves_context_then_idempotency_before_confirmation(): void
    {
        $route = Route::getRoutes()->getByName('api.v2.backend.channels.destroy');

        $this->assertNotNull($route);
        $this->assertMiddlewareOrder($route->gatherMiddleware(), [
            'api_v2_token_auth',
            'api_v2_ability:channels.write',
            'api_v2_permission:channel_delete',
            'api_v2_website_scope',
            'api_v2_risk_context',
            'api_v2_idempotency',
            'api_v2_risk',
        ]);
    }

    /**
     * Verify scoped generic mutations cannot reach targets belonging to another token website.
     */
    public function test_generic_resource_mutations_reject_cross_scope_targets(): void
    {
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        config(['wncms.models.channel.website_mode' => 'multi']);
        foreach (['channel_edit', 'channel_delete', 'channel_bulk_delete'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $this->user->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $other = Website::create([
            'user_id' => $this->user->id,
            'domain' => 'scope-b-'.uniqid().'.test',
            'site_name' => 'Scope B',
            'theme' => 'default',
        ]);
        $this->user->websites()->syncWithoutDetaching([$other->id]);
        $context = new AuthenticationContext($this->user, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'scope-test', $this->session->session_id, ['channels.write'], [$this->website->id]);
        $targets = collect(['update', 'destroy', 'bulk'])->map(function (string $suffix) use ($other): Channel {
            $channel = Channel::create(['name' => 'Scope '.$suffix, 'slug' => 'scope-'.$suffix.'-'.uniqid()]);
            $channel->websites()->sync([$other->id]);

            return $channel;
        });

        $update = Request::create('/api/v2/backend/channels/'.$targets[0]->id, 'PATCH', ['name' => 'Must not update', 'website_id' => $this->website->id]);
        $response = $this->scopedResourceRequest($update, 'api.v2.backend.channels.update', $context, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->update($request, 'channels', $targets[0]->id));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('website.scope_denied', $response->getData(true)['meta']['error_code']);
        $this->assertNotSame('Must not update', $targets[0]->fresh()->name);

        $destroy = Request::create('/api/v2/backend/channels/'.$targets[1]->id, 'DELETE', ['website_id' => $this->website->id]);
        $response = $this->scopedResourceRequest($destroy, 'api.v2.backend.channels.destroy', $context, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->destroy($request, 'channels', $targets[1]->id));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull($targets[1]->fresh());

        $bulk = Request::create('/api/v2/backend/channels/bulk_delete', 'POST', ['model_ids' => [$targets[2]->id], 'website_id' => $this->website->id]);
        $response = $this->scopedResourceRequest($bulk, 'api.v2.backend.channels.bulk_delete', $context, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->bulkDelete($request, 'channels'));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull($targets[2]->fresh());
    }

    /**
     * Verify every requested website is scoped and a legal scoped mutation still executes.
     */
    public function test_generic_resource_update_validates_all_website_ids_and_allows_legal_scope(): void
    {
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        config(['wncms.models.channel.website_mode' => 'multi']);
        Permission::findOrCreate('channel_edit', 'web');
        $this->user->givePermissionTo('channel_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $other = Website::create([
            'user_id' => $this->user->id,
            'domain' => 'scope-request-b-'.uniqid().'.test',
            'site_name' => 'Scope Request B',
            'theme' => 'default',
        ]);
        $this->user->websites()->syncWithoutDetaching([$other->id]);
        $channel = Channel::create(['name' => 'Scoped Before', 'slug' => 'scoped-legal-'.uniqid()]);
        $channel->websites()->sync([$this->website->id]);
        $context = new AuthenticationContext($this->user, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'scope-test', $this->session->session_id, ['channels.write'], [$this->website->id]);

        $denied = Request::create('/api/v2/backend/channels/'.$channel->id, 'PATCH', [
            'name' => 'Denied', 'website_id' => $this->website->id, 'website_ids' => [$other->id, $this->website->id],
        ]);
        $response = $this->scopedResourceRequest($denied, 'api.v2.backend.channels.update', $context, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->update($request, 'channels', $channel->id));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Scoped Before', $channel->fresh()->name);

        $allowed = Request::create('/api/v2/backend/channels/'.$channel->id, 'PATCH', [
            'name' => 'Scoped After', 'website_id' => $this->website->id, 'website_ids' => [$this->website->id, $this->website->id],
        ]);
        $response = $this->scopedResourceRequest($allowed, 'api.v2.backend.channels.update', $context, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->update($request, 'channels', $channel->id));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Scoped After', $channel->fresh()->name);
        $this->assertSame([$this->website->id], $channel->fresh()->websites()->pluck('websites.id')->map(fn ($id) => (int) $id)->all());

        $unscoped = Channel::create(['name' => 'No Membership', 'slug' => 'scoped-empty-'.uniqid()]);
        $emptyMembership = Request::create('/api/v2/backend/channels/'.$unscoped->id, 'PATCH', [
            'name' => 'Must remain unchanged', 'website_id' => $this->website->id,
        ]);
        $response = $this->scopedResourceRequest($emptyMembership, 'api.v2.backend.channels.update', $context, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->update($request, 'channels', $unscoped->id));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('No Membership', $unscoped->fresh()->name);

        $malformed = Request::create('/api/v2/backend/channels/'.$channel->id, 'PATCH', [
            'name' => 'Must remain scoped', 'website_id' => $this->website->id, 'website_ids' => [$this->website->id, 'invalid'],
        ]);
        $response = $this->scopedResourceRequest($malformed, 'api.v2.backend.channels.update', $context, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->update($request, 'channels', $channel->id));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Scoped After', $channel->fresh()->name);
    }

    /**
     * Verify direct generic-resource mutations share one canonical website binding.
     */
    public function test_direct_resource_store_accepts_website_key_and_update_rejects_empty_list_override(): void
    {
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        config(['wncms.models.channel.website_mode' => 'multi']);
        foreach (['channel_create', 'channel_edit'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $this->user->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $context = new AuthenticationContext($this->user, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'binding-direct', $this->session->session_id, ['channels.write'], [$this->website->id]);

        $store = Request::create('/api/v2/backend/channels', 'POST', [
            'name' => 'Key-bound channel',
            'slug' => 'key-bound-'.uniqid(),
            'website_key' => 'website:'.$this->website->id,
        ]);
        $response = $this->scopedResourceRequest($store, 'api.v2.backend.channels.store', $context, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->store($request, 'channels'));
        $this->assertSame(201, $response->getStatusCode(), (string) $response->getContent());
        $channel = Channel::query()->where('name', 'Key-bound channel')->firstOrFail();
        $this->assertSame([$this->website->id], $channel->websites()->pluck('websites.id')->map(fn ($id) => (int) $id)->all());

        $update = Request::create('/api/v2/backend/channels/'.$channel->id, 'PATCH', [
            'name' => 'Must not detach',
            'website_id' => $this->website->id,
            'website_ids' => [],
        ]);
        $response = $this->scopedResourceRequest($update, 'api.v2.backend.channels.update', $context, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->update($request, 'channels', $channel->id));
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Key-bound channel', $channel->fresh()->name);
        $this->assertSame([$this->website->id], $channel->fresh()->websites()->pluck('websites.id')->map(fn ($id) => (int) $id)->all());
    }

    /**
     * Verify planned execution and its controller consume the same canonical website-key binding.
     */
    public function test_planned_resource_update_uses_canonical_website_key_binding(): void
    {
        uss('api_high_risk_action_mode', 'planned');
        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => 'v1',
            'keys' => ['v1' => [
                'ip' => 'binding-ip-key-123456789012345678901',
                'login_identifier' => 'binding-login-key-123456789012345',
                'user_agent' => 'binding-agent-key-1234567890123456',
            ]],
        ]]);
        config(['wncms.models.channel.website_mode' => 'multi']);
        Permission::findOrCreate('channel_edit', 'web');
        $this->user->givePermissionTo('channel_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $channel = Channel::create(['name' => 'Planned before', 'slug' => 'planned-binding-'.uniqid()]);
        $channel->websites()->sync([$this->website->id]);
        $context = new AuthenticationContext($this->user, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'binding-planned', $this->session->session_id, ['channels.write'], [$this->website->id]);
        $request = Request::create('/api/v2/backend/channels/'.$channel->id, 'PATCH', [
            'name' => 'Planned after',
            'website_key' => 'website:'.$this->website->id,
        ]);
        $route = Route::getRoutes()->getByName('api.v2.backend.channels.update');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        auth()->setUser($this->user);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $snapshot = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]);
        $plan = app(ActionPlanService::class)->createResolved($context, $operation, $snapshot);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $snapshot);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);

        $response = app(EnforceApiV2RiskPolicy::class)->handle($request, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->update($request, 'channels', $channel->id));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());
        $this->assertSame('Planned after', $channel->fresh()->name);
        $this->assertSame([$this->website->id], $channel->fresh()->websites()->pluck('websites.id')->map(fn ($id) => (int) $id)->all());
    }

    /**
     * Verify global generic resources retain their unscoped binding semantics.
     */
    public function test_global_resource_update_allows_empty_website_binding_input(): void
    {
        config(['wncms.models.channel.website_mode' => 'global']);
        Permission::findOrCreate('channel_edit', 'web');
        $this->user->givePermissionTo('channel_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $channel = Channel::create(['name' => 'Global before', 'slug' => 'global-binding-'.uniqid()]);
        $context = new AuthenticationContext($this->user, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'binding-global', $this->session->session_id, ['channels.write'], [$this->website->id]);
        $request = Request::create('/api/v2/backend/channels/'.$channel->id, 'PATCH', [
            'name' => 'Global after',
            'website_id' => $this->website->id,
            'website_ids' => [],
        ]);

        $response = $this->scopedResourceRequest($request, 'api.v2.backend.channels.update', $context, fn (Request $request) => app(\Wncms\Http\Controllers\Api\V2\Backend\ResourceController::class)->update($request, 'channels', $channel->id));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());
        $this->assertSame('Global after', $channel->fresh()->name);
    }

    /**
     * Verify generic model mutations enforce permission before their unsupported risk boundary.
     */
    public function test_generic_model_update_enforces_the_validated_target_permission(): void
    {
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        foreach (['link_edit', 'user_edit'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $target = User::create([
            'username' => 'generic-target-before-'.uniqid(),
            'email' => 'generic-target-'.uniqid().'@example.test',
            'password' => 'not-a-real-password',
            'email_verified_at' => now(),
        ]);
        $token = $this->token(['models.write'], [$this->website->id]);

        $this->user->givePermissionTo('link_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withToken($token)->withHeader('Idempotency-Key', 'guard-model-update-allowed')->postJson('/api/v2/backend/models/update', [
            'model' => 'user',
            'model_id' => $target->id,
            'column' => 'username',
            'value' => 'generic-target-denied',
            'website_id' => $this->website->id,
        ])
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authorization.permission_denied');
        $this->assertNotSame('generic-target-denied', $target->fresh()->username);

        $this->user->givePermissionTo('user_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withToken($token)->withHeader('Idempotency-Key', 'guard-trusted-update-allowed')->postJson('/api/v2/backend/models/update', [
            'model' => 'users',
            'model_id' => $target->id,
            'column' => 'username',
            'value' => 'generic-target-allowed',
            'website_id' => $this->website->id,
        ])->assertOk();
        $this->assertSame('generic-target-allowed', $target->fresh()->username);
    }

    /**
     * Verify direct mode mutates the exact trusted override rather than a decoy.
     */
    public function test_generic_model_update_mutates_the_exact_trusted_override_instead_of_a_same_name_decoy(): void
    {
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        $this->configureCatalogModel('trusted_widget', TrustedWidgetOverride::class);
        Permission::findOrCreate('trusted_widget_edit', 'web');
        $this->user->givePermissionTo('trusted_widget_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        do {
            $target = User::create([
                'username' => 'trusted-target-'.uniqid(),
                'email' => 'trusted-target-'.uniqid().'@example.test',
                'password' => 'not-a-real-password',
                'email_verified_at' => now(),
            ]);
        } while (Website::query()->whereKey($target->id)->exists());

        $decoy = Website::create([
            'id' => $target->id,
            'user_id' => $this->user->id,
            'domain' => 'trusted-decoy-'.uniqid().'.test',
            'site_name' => 'Trusted Decoy',
            'theme' => 'default',
        ]);
        $targetBefore = (string) DB::table('users')->where('id', $target->id)->value('updated_at');
        $decoyBefore = (string) DB::table('websites')->where('id', $decoy->id)->value('updated_at');
        $newTimestamp = '2025-01-02 03:04:05';
        $token = $this->token(['models.write'], [$this->website->id]);

        $this->withToken($token)->withHeader('Idempotency-Key', 'guard-trusted-override-update')->postJson('/api/v2/backend/models/update', [
            'model' => 'trusted_widgets',
            'model_id' => $target->id,
            'column' => 'updated_at',
            'value' => $newTimestamp,
            'website_id' => $this->website->id,
        ])->assertOk();

        $this->assertSame($newTimestamp, (string) DB::table('users')->where('id', $target->id)->value('updated_at'));
        $this->assertNotSame($targetBefore, (string) DB::table('users')->where('id', $target->id)->value('updated_at'));
        $this->assertSame($decoyBefore, (string) DB::table('websites')->where('id', $decoy->id)->value('updated_at'));
    }

    /**
     * Verify invalid modelKey metadata returns a stable permission denial instead of an exception.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidCatalogModelProvider')]
    public function test_generic_model_update_rejects_invalid_catalog_metadata_without_a_server_error(
        string $modelKey,
        string $modelClass,
    ): void {
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        $this->configureCatalogModel($modelKey, $modelClass);
        Permission::findOrCreate($modelKey.'_edit', 'web');
        $this->user->givePermissionTo($modelKey.'_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $token = $this->token(['models.write'], [$this->website->id]);

        $this->withToken($token)->postJson('/api/v2/backend/models/update', [
            'model' => $modelKey,
            'model_id' => $this->user->id,
            'column' => 'username',
            'value' => 'must-not-change',
            'website_id' => $this->website->id,
            'wncms_api_v2_model_resolution' => [
                'model_key' => 'user',
                'model_class' => User::class,
                'permission' => 'user_edit',
            ],
        ])
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authorization.permission_denied');
    }

    /**
     * Provide catalog classes with invalid modelKey contracts.
     *
     * @return array<string, array{string, string}>
     */
    public static function invalidCatalogModelProvider(): array
    {
        return [
            'private model key' => ['private_model_key', PrivateModelKey::class],
            'non-static model key' => ['non_static_model_key', NonStaticModelKey::class],
            'mismatched model key' => ['mismatched_model_key', MismatchedModelKey::class],
        ];
    }

    /**
     * Verify unknown generic model selectors fail before website resolution.
     */
    public function test_generic_model_update_rejects_an_unknown_model_selector_before_scope(): void
    {
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        Permission::findOrCreate('setting_edit', 'web');
        $this->user->givePermissionTo('setting_edit');
        $token = $this->token(['models.write'], [$this->website->id]);

        $this->withToken($token)->postJson('/api/v2/backend/models/update', [
            'model' => 'App\\Models\\User',
            'model_id' => $this->user->id,
            'column' => 'username',
            'value' => 'must-not-change',
        ])
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authorization.permission_denied');
    }

    /**
     * Verify generic model routes advertise their dynamic permission templates and middleware.
     */
    public function test_generic_model_routes_declare_dynamic_target_permission_before_scope(): void
    {
        $expectations = [
            'models.update' => ['{model}_edit', 'model_template', 'api_v2_model_permission:edit'],
            'models.bulk_delete' => ['{model}_bulk_delete', 'model_template', 'api_v2_model_permission:bulk_delete'],
            'models.bulk_force_delete' => ['{model}_bulk_delete', 'model_template', 'api_v2_model_permission:bulk_delete'],
        ];

        foreach ($expectations as $name => [$permission, $permissionMode, $permissionMiddleware]) {
            $route = Route::getRoutes()->getByName('api.v2.backend.'.$name);
            $this->assertNotNull($route);
            $this->assertMiddlewareOrder($route->gatherMiddleware(), [
                'api_v2_token_auth',
                'api_v2_ability:models.write',
                $permissionMiddleware,
                'api_v2_website_scope',
            ]);
            $this->assertSame(
                $permission,
                app(\Wncms\Api\V2\ApiContractRegistry::class)->operation('backend.'.$name)?->permission,
            );
            $this->assertSame(
                $permissionMode,
                app(\Wncms\Api\V2\ApiContractRegistry::class)->operation('backend.'.$name)?->permissionMode,
            );
        }
    }

    /**
     * Verify every configured resource and bridge operation declares its own ordered guards.
     */
    public function test_every_production_resource_and_bridge_route_declares_operation_specific_guards(): void
    {
        foreach (config('wncms-backend-api-v2.resources', []) as $resource => $resourceConfig) {
            $enabledActions = $resourceConfig['enabled_actions'] ?? ['index', 'show', 'store', 'update', 'destroy', 'bulk_delete'];

            foreach ($enabledActions as $action) {
                if ($action === 'bulk_delete' && ($resourceConfig['enable_bulk_delete'] ?? true) !== true) {
                    continue;
                }

                $route = Route::getRoutes()->getByName("api.v2.backend.{$resource}.{$action}");
                $this->assertNotNull($route, "Missing configured route api.v2.backend.{$resource}.{$action}.");
                $ability = $resource.'.'.(in_array($action, ['index', 'show'], true) ? 'read' : 'write');
                $permission = (string) ($resourceConfig['permissions'][$action] ?? '');
                $this->assertNotSame('', $permission, "Missing fixture permission for {$resource}.{$action}.");
                $this->assertMiddlewareOrder($route->gatherMiddleware(), [
                    'api_v2_token_auth',
                    'api_v2_ability:'.$ability,
                    'api_v2_permission:'.$permission,
                    'api_v2_website_scope',
                ]);
                $this->assertNotContains('api_v2_has_website', $route->gatherMiddleware());
            }
        }

        foreach (config('wncms-backend-api-v2.actions', []) as $action) {
            $route = Route::getRoutes()->getByName('api.v2.backend.'.(string) $action['name']);

            $this->assertNotNull($route, 'Missing configured route api.v2.backend.'.(string) $action['name'].'.');
            $operation = app(\Wncms\Api\V2\ApiContractRegistry::class)->operation('backend.'.(string) $action['name']);
            $this->assertNotNull($operation);
            $ability = $operation->ability;
            $permissionMiddleware = isset($action['permission_template'])
                ? 'api_v2_model_permission:'.str_replace(['{model}_', '{model}'], '', (string) $action['permission_template'])
                : 'api_v2_permission:'.(string) ($action['permission'] ?? '');
            $this->assertMiddlewareOrder($route->gatherMiddleware(), [
                'api_v2_token_auth',
                'api_v2_ability:'.$ability,
                $permissionMiddleware,
                'api_v2_website_scope',
            ]);
            $this->assertNotContains('api_v2_has_website', $route->gatherMiddleware());
        }
    }

    /**
     * Verify bridge authorization and risk follow semantic operation metadata.
     */
    public function test_production_bridge_semantics_are_not_inferred_from_http_method(): void
    {
        $registry = app(\Wncms\Api\V2\ApiContractRegistry::class);
        $expectations = [
            'backend.menus.get_menu_item' => ['menus.read', 'read'],
            'backend.settings.google_test' => ['settings.write', 'write'],
        ];

        foreach ($expectations as $operationId => [$ability, $risk]) {
            $operation = $registry->operation($operationId);
            $this->assertNotNull($operation);
            $this->assertSame($ability, $operation->ability);
            $this->assertSame($risk, $operation->risk);
            $route = Route::getRoutes()->getByName($operation->routeName);
            $this->assertNotNull($route);
            $this->assertContains('api_v2_ability:'.$ability, $route->gatherMiddleware());
        }
    }

    /**
     * Verify numeric IDs and canonical website keys select the same stable website identity.
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
     * Execute production scope and risk middleware around a real generic resource controller.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function scopedResourceRequest(Request $request, string $routeName, AuthenticationContext $context, callable $domain)
    {
        $route = Route::getRoutes()->getByName($routeName);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        auth()->setUser($this->user);

        return app(ResolveApiV2WebsiteScope::class)->handle($request, fn (Request $request) => app(ResolveApiV2RiskContext::class)->handle($request, fn (Request $request) => app(EnforceApiV2RiskPolicy::class)->handle($request, $domain)
        )
        );
    }

    /**
     * Send a request through the complete ordered guard chain.
     *
     * @param  array<string, mixed>  $query
     * @return \Illuminate\Testing\TestResponse
     */
    private function guardedRequest(string $token, array $query)
    {
        auth()->forgetGuards();

        return $this->withToken($token)->getJson('/api/v2/_test/ordered-guards?'.http_build_query($query));
    }

    /**
     * Assert middleware identities occur in the expected relative order.
     *
     * @param  array<int, string>  $middleware
     * @param  array<int, string>  $expected
     */
    private function assertMiddlewareOrder(array $middleware, array $expected): void
    {
        $positions = array_map(
            static fn (string $name): int|false => array_search($name, $middleware, true),
            $expected,
        );

        foreach ($positions as $position) {
            $this->assertNotFalse($position);
        }

        $this->assertSame($positions, array_values($positions));
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
    }

    /**
     * Add one model to the backend allowlist and WNCMS override map.
     */
    private function configureCatalogModel(string $modelKey, string $modelClass): void
    {
        config([
            "wncms-backend-api-v2.resources.{$modelKey}" => [
                'model_key' => $modelKey,
                'enabled_actions' => [],
            ],
            "wncms.models.{$modelKey}" => ['class' => $modelClass],
        ]);
    }
}
