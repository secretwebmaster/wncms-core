<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Providers\LegacyBackendContractProvider;
use Wncms\Api\V2\Risk\ActionPlanService;
use Wncms\Api\V2\Risk\OperationRiskContextResolver;
use Wncms\Api\V2\Risk\RiskContextException;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Http\Controllers\Api\V2\Backend\ActionPlanController;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Http\Middleware\EnforceApiV2RiskPolicy;
use Wncms\Http\Middleware\ResolveApiV2RiskContext;
use Wncms\Models\Channel;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class ProductionRiskOperationTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $databaseSnapshot = [];

    private bool $suspendedTestTransaction = false;

    /**
     * Configure mandatory security event correlation for plan tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        foreach ($this->snapshotTables() as $table) {
            $this->databaseSnapshot[$table] = DB::table($table)->get()->map(static fn ($row): array => (array) $row)->all();
        }
        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => 'v1',
            'keys' => ['v1' => [
                'ip' => 'production-risk-ip-key-123456789012345',
                'login_identifier' => 'production-risk-login-key-123456789',
                'user_agent' => 'production-risk-agent-key-123456789',
            ]],
        ]]);
    }

    protected function tearDown(): void
    {
        if ($this->suspendedTestTransaction) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::statement('PRAGMA foreign_keys = OFF');
            foreach (array_reverse($this->snapshotTables()) as $table) {
                DB::table($table)->delete();
            }
            foreach ($this->snapshotTables() as $table) {
                if ($this->databaseSnapshot[$table] !== []) {
                    DB::table($table)->insert($this->databaseSnapshot[$table]);
                }
            }
            DB::statement('PRAGMA foreign_keys = ON');
            DB::beginTransaction();
            Cache::flush();
        }

        parent::tearDown();
    }

    /**
     * Verify an ineligible external bridge fails closed in planned mode.
     */
    public function test_external_production_bridge_fails_closed_before_dispatch(): void
    {
        uss('api_high_risk_action_mode', 'planned');
        $operation = app(ApiContractRegistry::class)->operation('backend.cache.flush');
        $this->assertNotNull($operation);
        $request = Request::create('/api/v2/backend/cache/flush', 'POST');
        $route = app('router')->getRoutes()->getByName('api.v2.backend.cache.flush');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $context = $this->context();
        $context->actor()->givePermissionTo(Permission::findOrCreate('cache_flush', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, app(OperationRiskContextResolver::class)->resolveRequest($request, $operation));
        $executions = 0;

        $response = $this->executeRiskMiddleware($request, function () use (&$executions) {
            $executions++;

            return response()->json(['ok' => true]);
        });

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame(0, $executions);
    }

    /**
     * Verify direct mode permits an authorized custom side effect without planned guarantees.
     */
    public function test_external_production_bridge_executes_in_direct_mode(): void
    {
        uss('api_high_risk_action_mode', 'direct');
        $operation = app(ApiContractRegistry::class)->operation('backend.cache.flush');
        $request = Request::create('/api/v2/backend/cache/flush', 'POST');
        $route = app('router')->getRoutes()->getByName('api.v2.backend.cache.flush');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $context = $this->context();
        $context->actor()->givePermissionTo(Permission::findOrCreate('cache_flush', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, app(OperationRiskContextResolver::class)->resolveRequest($request, $operation));
        $executions = 0;

        $response = $this->executeRiskMiddleware($request, function () use (&$executions) {
            $executions++;

            return response()->json(['ok' => true]);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $executions);
    }

    /**
     * Verify a real generic resource target is refreshed before plan reservation.
     */
    public function test_generic_resource_execution_uses_fresh_locked_target(): void
    {
        uss('api_high_risk_action_mode', 'planned');
        $channel = Channel::create(['name' => 'Before', 'slug' => 'risk-before-'.uniqid()]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $this->assertNotNull($operation);
        $request = Request::create("/api/v2/backend/channels/{$channel->id}", 'PATCH', ['name' => 'After']);
        $route = app('router')->getRoutes()->getByName('api.v2.backend.channels.update');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $context = $this->context();
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        $snapshot = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]);
        $plan = app(ActionPlanService::class)->createResolved($context, $operation, $snapshot);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $snapshot);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        $channel->update(['remark' => 'concurrently changed']);
        $executions = 0;

        $response = $this->executeRiskMiddleware($request, function () use (&$executions) {
            $executions++;

            return response()->json(['ok' => true]);
        });

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(0, $executions);
    }

    /**
     * Verify runtime environment changes are refreshed before confirmation.
     */
    public function test_runtime_ip_change_makes_a_production_plan_stale(): void
    {
        uss('api_high_risk_action_mode', 'planned');
        $channel = Channel::create(['name' => 'Before', 'slug' => 'risk-ip-'.uniqid()]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $request = Request::create("/api/v2/backend/channels/{$channel->id}", 'PATCH', ['name' => 'After'], [], [], ['REMOTE_ADDR' => '192.0.2.10', 'HTTP_USER_AGENT' => 'Device A']);
        $route = app('router')->getRoutes()->getByName('api.v2.backend.channels.update');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $context = $this->context();
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        $snapshot = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]);
        $plan = app(ActionPlanService::class)->createResolved($context, $operation, $snapshot);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $snapshot);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        $request->server->set('REMOTE_ADDR', '192.0.2.11');

        $response = $this->executeRiskMiddleware($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('risk.plan_stale', $response->getData(true)['meta']['error_code']);
    }

    /**
     * Verify target website pivot changes after planning make confirmation stale.
     */
    public function test_target_website_membership_change_makes_production_plan_stale(): void
    {
        uss('api_high_risk_action_mode', 'planned');
        config(['wncms.models.channel.website_mode' => 'multi']);
        $user = User::query()->firstOrFail();
        $websiteA = Website::query()->firstOrFail();
        $websiteB = Website::create([
            'user_id' => $user->id,
            'domain' => 'risk-membership-b-'.uniqid().'.test',
            'site_name' => 'Risk Membership B',
            'theme' => 'default',
        ]);
        $user->websites()->syncWithoutDetaching([$websiteA->id, $websiteB->id]);
        $channel = Channel::create(['name' => 'Before', 'slug' => 'risk-membership-'.uniqid()]);
        $channel->websites()->sync([$websiteA->id]);
        [$request, $context, $operation] = $this->plannedChannelRequest($channel, [$websiteA->id, $websiteB->id], [
            'name' => 'After', 'website_id' => $websiteA->id, 'website_ids' => [$websiteA->id],
        ]);
        $snapshot = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]);
        $plan = app(ActionPlanService::class)->createResolved($context, $operation, $snapshot);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $snapshot);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        $channel->websites()->sync([$websiteB->id]);

        $response = $this->executeRiskMiddleware($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('risk.plan_stale', $response->getData(true)['meta']['error_code']);
    }

    /**
     * Verify requested website existence is rebound inside the execution transaction.
     */
    public function test_requested_website_deletion_makes_production_plan_stale(): void
    {
        uss('api_high_risk_action_mode', 'planned');
        config(['wncms.models.channel.website_mode' => 'multi']);
        $user = User::query()->firstOrFail();
        $websiteA = Website::query()->firstOrFail();
        $websiteB = Website::create([
            'user_id' => $user->id,
            'domain' => 'risk-existence-b-'.uniqid().'.test',
            'site_name' => 'Risk Existence B',
            'theme' => 'default',
        ]);
        $user->websites()->syncWithoutDetaching([$websiteA->id, $websiteB->id]);
        $channel = Channel::create(['name' => 'Before', 'slug' => 'risk-existence-'.uniqid()]);
        $channel->websites()->sync([$websiteA->id]);
        [$request, $context, $operation] = $this->plannedChannelRequest($channel, [$websiteA->id, $websiteB->id], [
            'name' => 'After', 'website_id' => $websiteA->id, 'website_ids' => [$websiteA->id, $websiteB->id],
        ]);
        $snapshot = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]);
        $plan = app(ActionPlanService::class)->createResolved($context, $operation, $snapshot);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $snapshot);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        $websiteB->delete();

        $response = $this->executeRiskMiddleware($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('risk.plan_stale', $response->getData(true)['meta']['error_code']);
    }

    /**
     * Verify relationship connections are checked before a production side effect.
     */
    public function test_scoped_relationship_cross_connection_fails_before_side_effect(): void
    {
        uss('api_high_risk_action_mode', 'direct');
        $database = tempnam(sys_get_temp_dir(), 'wncms-cross-');

        try {
            config(['database.connections.task8_scope_cross_connection' => array_merge(
                config('database.connections.sqlite'),
                ['database' => $database],
            )]);
            config(['wncms.models.channel' => array_merge((array) config('wncms.models.channel', []), [
                'class' => Task8ScopedCrossConnectionChannel::class,
                'website_mode' => 'multi',
            ])]);
            $alias = __NAMESPACE__.'\\CrossConnectionFixture\\Channel';
            if (! class_exists($alias, false)) {
                class_alias(Task8ScopedCrossConnectionChannel::class, $alias);
            }
            wncms()->registerModel($alias);
            DB::purge('task8_scope_cross_connection');
            DB::connection('task8_scope_cross_connection')->statement(
                'CREATE TABLE channels (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR NOT NULL, slug VARCHAR NOT NULL UNIQUE, contact VARCHAR NULL, remark VARCHAR NULL, created_at DATETIME NULL, updated_at DATETIME NULL)',
            );
            DB::connection('task8_scope_cross_connection')->statement(
                'CREATE TABLE model_has_websites (website_id INTEGER NOT NULL, model_id INTEGER NOT NULL, model_type VARCHAR NOT NULL, PRIMARY KEY (website_id, model_id, model_type))',
            );
            $website = Website::query()->firstOrFail();
            $channel = Task8ScopedCrossConnectionChannel::create(['name' => 'Cross', 'slug' => 'risk-cross-'.uniqid()]);
            $channel->websites()->sync([$website->id]);
            $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
            $request = Request::create('/api/v2/backend/channels/'.$channel->id, 'PATCH', [
                'name' => 'Must not execute',
                'website_id' => $website->id,
            ]);
            $route = app('router')->getRoutes()->getByName('api.v2.backend.channels.update');
            $route->bind($request);
            $request->setRouteResolver(fn () => $route);
            $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $this->context([$website->id]));
            $request->attributes->set(
                ResolveApiV2RiskContext::ATTRIBUTE,
                app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]),
            );
            $executions = 0;

            $response = $this->executeRiskMiddleware($request, function () use (&$executions) {
                $executions++;

                return response()->json(['ok' => true]);
            });

            $this->assertSame(503, $response->getStatusCode());
            $this->assertSame(0, $executions);
            $this->assertSame('Cross', $channel->fresh()->name);
        } finally {
            DB::purge('task8_scope_cross_connection');
            wncms()->registerModel(Channel::class);
            @unlink($database);
        }
    }

    /**
     * Verify bulk target creation or deletion after planning invalidates membership.
     */
    public function test_bulk_target_membership_changes_make_production_plan_stale(): void
    {
        uss('api_high_risk_action_mode', 'planned');
        $existing = Channel::create(['name' => 'Existing', 'slug' => 'risk-bulk-existing-'.uniqid()]);
        $missingId = $existing->id + 10000;
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.bulk_delete');
        $this->assertNotNull($operation);
        $request = Request::create('/api/v2/backend/channels/bulk_delete', 'POST', ['model_ids' => [$missingId, $existing->id]]);
        $route = app('router')->getRoutes()->getByName('api.v2.backend.channels.bulk_delete');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $context = $this->context();
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        try {
            app(OperationRiskContextResolver::class)->resolveRequest($request, $operation);
            $this->fail('Plan creation must reject an initially incomplete bulk set.');
        } catch (RiskContextException $exception) {
            $this->assertSame('validation.failed', $exception->errorCode);
            $this->assertSame(422, $exception->httpStatus);
        }
        Channel::create(['id' => $missingId, 'name' => 'Appeared', 'slug' => 'risk-bulk-new-'.uniqid()]);

        $freshRequest = Request::create('/api/v2/backend/channels/bulk_delete', 'POST', ['model_ids' => [$missingId, $existing->id]]);
        $route->bind($freshRequest);
        $freshRequest->setRouteResolver(fn () => $route);
        $freshRequest->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        $freshSnapshot = app(OperationRiskContextResolver::class)->resolveRequest($freshRequest, $operation);
        $freshPlan = app(ActionPlanService::class)->createResolved($context, $operation, $freshSnapshot);
        $freshRequest->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $freshSnapshot);
        $freshRequest->headers->set('X-WNCMS-Confirmation', $freshPlan['confirmation']);
        $existing->delete();

        $deleted = $this->executeRiskMiddleware($freshRequest, fn () => response()->json(['ok' => true]));
        $this->assertSame(409, $deleted->getStatusCode());
    }

    /**
     * Verify a second process changing a target after planning is rejected at execution.
     */
    public function test_two_process_target_change_before_reservation_returns_stale_conflict(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Process concurrency primitives are unavailable.');
        }

        uss('api_high_risk_action_mode', 'planned');
        $channel = Channel::create(['name' => 'Before', 'slug' => 'risk-process-'.uniqid()]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $request = Request::create("/api/v2/backend/channels/{$channel->id}", 'PATCH', ['name' => 'After']);
        $route = app('router')->getRoutes()->getByName('api.v2.backend.channels.update');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $context = $this->context();
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        $snapshot = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]);
        $plan = app(ActionPlanService::class)->createResolved($context, $operation, $snapshot);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $snapshot);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        DB::connection()->commit();
        $barrier = tempnam(sys_get_temp_dir(), 'wncms-risk-target-');

        try {
            $pid = pcntl_fork();
            if ($pid === 0) {
                DB::disconnect();
                Channel::query()->whereKey($channel->id)->update(['remark' => 'changed-by-second-process']);
                file_put_contents($barrier, 'changed', LOCK_EX);
                exit(0);
            }
            $deadline = microtime(true) + 10;
            while (filesize($barrier) === 0 && microtime(true) < $deadline) {
                clearstatcache(true, $barrier);
                usleep(10_000);
            }
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
            DB::disconnect();

            $response = $this->executeRiskMiddleware($request, fn () => response()->json(['ok' => true]));

            $this->assertSame(409, $response->getStatusCode());
            $this->assertSame('risk.plan_stale', $response->getData(true)['meta']['error_code']);
        } finally {
            @unlink($barrier);
            DB::table('api_action_plans')->where('plan_id', $plan['id'])->delete();
            DB::table('api_security_events')->where('actor_id', $context->actorId())->delete();
            Channel::query()->whereKey($channel->id)->delete();
            uss('api_high_risk_action_mode', 'direct');
            DB::connection()->beginTransaction();
        }
    }

    /**
     * Verify service credentials cannot reach a production password mutation.
     */
    public function test_service_token_is_denied_before_production_credential_mutation_dispatch(): void
    {
        $operation = app(ApiContractRegistry::class)->operation('backend.users.account.password.update');
        $request = Request::create('/api/v2/backend/users/account/password/update', 'POST');
        $route = app('router')->getRoutes()->getByName('api.v2.backend.users.account.password.update');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $interactive = $this->context();
        $service = new AuthenticationContext($interactive->actor(), ApiCredential::TYPE_SERVICE_TOKEN, 'service-risk', null, ['users.write'], []);
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $service);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, app(OperationRiskContextResolver::class)->resolveRequest($request, $operation));
        $executions = 0;

        $response = $this->executeRiskMiddleware($request, function () use (&$executions) {
            $executions++;

            return response()->json(['ok' => true]);
        });

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('risk.credential_type_denied', $response->getData(true)['meta']['error_code']);
        $this->assertSame(0, $executions);
    }

    /**
     * Verify Website route targets are authoritative scope even though Website is a global model.
     */
    public function test_website_resource_targets_cannot_escape_interactive_or_service_scope(): void
    {
        $actor = User::query()->firstOrFail();
        $websiteA = Website::query()->firstOrFail();
        $websiteB = Website::create([
            'user_id' => $actor->id,
            'domain' => 'risk-website-target-b-'.uniqid().'.test',
            'site_name' => 'Risk Website Target B',
            'theme' => 'default',
        ]);
        $actor->websites()->syncWithoutDetaching([$websiteA->id, $websiteB->id]);

        foreach ([ApiCredential::TYPE_INTERACTIVE_ACCESS, ApiCredential::TYPE_SERVICE_TOKEN] as $credentialType) {
            $context = new AuthenticationContext($actor, $credentialType, 'website-target-'.$credentialType, null, ['websites.write'], [$websiteA->id]);
            foreach (['update', 'destroy'] as $action) {
                $operation = app(ApiContractRegistry::class)->operation('backend.websites.'.$action);
                $request = $this->productionRequest('api.v2.backend.websites.'.$action, $context, ['website_id' => $websiteA->id], ['id' => $websiteB->id]);

                $this->expectRiskContextCode('website.scope_denied', fn () => app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $websiteB->id]));
            }
        }
    }

    /**
     * Verify one out-of-scope Website in a bulk request denies the whole target set.
     */
    public function test_website_bulk_delete_mixed_scope_is_atomically_denied(): void
    {
        $actor = User::query()->firstOrFail();
        $websiteA = Website::query()->firstOrFail();
        $websiteB = Website::create([
            'user_id' => $actor->id,
            'domain' => 'risk-website-bulk-b-'.uniqid().'.test',
            'site_name' => 'Risk Website Bulk B',
            'theme' => 'default',
        ]);
        $actor->websites()->syncWithoutDetaching([$websiteA->id, $websiteB->id]);
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_SERVICE_TOKEN, 'website-bulk', null, ['websites.write'], [$websiteA->id]);
        $resources = config('wncms-backend-api-v2.resources');
        $resources['websites']['enable_bulk_delete'] = true;
        $resources['websites']['enabled_actions'] = ['index', 'show', 'store', 'update', 'destroy', 'bulk_delete'];
        $resources['websites']['permissions']['bulk_delete'] = 'website_bulk_delete';
        config(['wncms-backend-api-v2.resources' => $resources]);
        $registry = new ApiContractRegistry;
        (new LegacyBackendContractProvider)->register($registry);
        $operation = $registry->operation('backend.websites.bulk_delete');
        $request = $this->productionRequest('api.v2.backend.websites.bulk_delete', $context, [
            'website_id' => $websiteA->id,
            'model_ids' => [$websiteA->id, $websiteB->id],
        ]);

        $this->expectRiskContextCode('website.scope_denied', fn () => app(OperationRiskContextResolver::class)->resolveRequest($request, $operation));
        $this->assertTrue(Website::query()->whereKey($websiteA->id)->exists());
        $this->assertTrue(Website::query()->whereKey($websiteB->id)->exists());
    }

    /**
     * Verify transaction-fresh authorization rejects actor website access revoked after planning.
     */
    public function test_actor_website_revocation_after_snapshot_denies_direct_and_planned_execution(): void
    {
        config(['wncms.models.channel.website_mode' => 'multi']);
        $actor = User::query()->firstOrFail();
        $actor->givePermissionTo(Permission::findOrCreate('channel_edit', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $website = Website::query()->firstOrFail();
        $websiteB = Website::create([
            'user_id' => null,
            'domain' => 'risk-revoke-b-'.uniqid().'.test',
            'site_name' => 'Risk Revoke B',
            'theme' => 'default',
        ]);
        $actor->websites()->syncWithoutDetaching([$website->id, $websiteB->id]);
        $website->update(['user_id' => null]);
        $channel = Channel::create(['name' => 'Before revocation', 'slug' => 'risk-revoke-'.uniqid()]);
        $channel->websites()->sync([$website->id, $websiteB->id]);

        foreach (['direct', 'planned'] as $mode) {
            uss('api_high_risk_action_mode', $mode);
            $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'revocation-'.$mode, 'session-'.$mode, ['channels.write'], [$website->id, $websiteB->id]);
            $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
            $request = $this->productionRequest('api.v2.backend.channels.update', $context, [
                'name' => 'Must not execute',
                'website_id' => $website->id,
                'website_ids' => [$website->id, $websiteB->id],
            ], ['id' => $channel->id]);
            $snapshot = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]);
            $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $snapshot);
            if ($mode === 'planned') {
                $plan = app(ActionPlanService::class)->createResolved($context, $operation, $snapshot);
                $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
            }
            $actor->websites()->detach($websiteB->id);
            $executions = 0;

            $response = $this->executeRiskMiddleware($request, function () use (&$executions) {
                $executions++;

                return response()->json(['ok' => true]);
            });

            $this->assertSame(403, $response->getStatusCode(), (string) $response->getContent());
            $this->assertSame('website.scope_denied', $response->getData(true)['meta']['error_code']);
            $this->assertSame(0, $executions);
            $actor->websites()->syncWithoutDetaching([$websiteB->id]);
        }
    }

    /**
     * Verify a concurrent actor-membership revocation wins before plan execution.
     */
    public function test_concurrent_actor_website_revocation_denies_planned_execution(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Process concurrency primitives are unavailable.');
        }
        uss('api_high_risk_action_mode', 'planned');
        config(['wncms.models.channel.website_mode' => 'multi']);
        $actor = User::query()->firstOrFail();
        $websiteA = Website::query()->firstOrFail();
        $websiteB = Website::create([
            'user_id' => null,
            'domain' => 'risk-concurrent-revoke-'.uniqid().'.test',
            'site_name' => 'Risk Concurrent Revoke',
            'theme' => 'default',
        ]);
        $actor->websites()->syncWithoutDetaching([$websiteA->id, $websiteB->id]);
        $channel = Channel::create(['name' => 'Concurrent revoke', 'slug' => 'risk-concurrent-revoke-'.uniqid()]);
        $channel->websites()->sync([$websiteA->id, $websiteB->id]);
        $context = $this->context([$websiteA->id, $websiteB->id]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $request = $this->productionRequest('api.v2.backend.channels.update', $context, [
            'name' => 'Must not execute',
            'website_id' => $websiteA->id,
            'website_ids' => [$websiteA->id, $websiteB->id],
        ], ['id' => $channel->id]);
        $snapshot = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]);
        $plan = app(ActionPlanService::class)->createResolved($context, $operation, $snapshot);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $snapshot);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        DB::connection()->commit();
        $barrier = tempnam(sys_get_temp_dir(), 'wncms-risk-access-');

        try {
            $pid = pcntl_fork();
            if ($pid === 0) {
                DB::disconnect();
                User::query()->findOrFail($actor->id)->websites()->detach($websiteB->id);
                file_put_contents($barrier, 'revoked', LOCK_EX);
                exit(0);
            }
            $deadline = microtime(true) + 10;
            while (filesize($barrier) === 0 && microtime(true) < $deadline) {
                clearstatcache(true, $barrier);
                usleep(10_000);
            }
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
            DB::disconnect();

            $response = $this->executeRiskMiddleware($request, fn () => response()->json(['ok' => true]));

            $this->assertSame(403, $response->getStatusCode(), (string) $response->getContent());
            $this->assertSame('website.scope_denied', $response->getData(true)['meta']['error_code']);
        } finally {
            @unlink($barrier);
            DB::table('api_action_plans')->where('plan_id', $plan['id'])->delete();
            DB::table('api_security_events')->where('actor_id', $context->actorId())->delete();
            Channel::query()->whereKey($channel->id)->delete();
            Website::query()->whereKey($websiteB->id)->delete();
            uss('api_high_risk_action_mode', 'direct');
            DB::connection()->beginTransaction();
        }
    }

    /**
     * Verify a permission revoked after planning is denied from fresh actor state.
     */
    public function test_permission_revoked_after_planning_denies_before_execution(): void
    {
        uss('api_high_risk_action_mode', 'planned');
        config(['wncms.models.channel.website_mode' => 'multi']);
        $actor = User::query()->firstOrFail();
        $website = Website::query()->firstOrFail();
        $actor->websites()->syncWithoutDetaching([$website->id]);
        Permission::findOrCreate('channel_edit', 'web');
        $actor->givePermissionTo('channel_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $channel = Channel::create(['name' => 'Permission before', 'slug' => 'risk-permission-'.uniqid()]);
        $channel->websites()->sync([$website->id]);
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'permission-fresh', 'permission-session', ['channels.write'], [$website->id]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $request = $this->productionRequest('api.v2.backend.channels.update', $context, [
            'name' => 'Must not execute', 'website_id' => $website->id,
        ], ['id' => $channel->id]);
        $snapshot = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]);
        $plan = app(ActionPlanService::class)->createResolved($context, $operation, $snapshot);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $snapshot);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        $actor->checkPermissionTo('channel_edit');
        $actor->revokePermissionTo('channel_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $executions = 0;

        $response = $this->executeRiskMiddleware($request, function () use (&$executions) {
            $executions++;

            return response()->json(['ok' => true]);
        });

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('authorization.permission_denied', $response->getData(true)['meta']['error_code']);
        $this->assertSame(0, $executions);
    }

    /**
     * Verify a revoke racing after authorization is serialized behind the side effect.
     */
    public function test_concurrent_permission_revoke_cannot_pass_between_authorization_and_mutation(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Process concurrency primitives are unavailable.');
        }
        uss('api_high_risk_action_mode', 'direct');
        config(['wncms.models.channel.website_mode' => 'multi']);
        $actor = User::factory()->create();
        $website = Website::query()->firstOrFail();
        $actor->websites()->syncWithoutDetaching([$website->getKey()]);
        $permission = Permission::findOrCreate('channel_edit', 'web');
        $actor->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $channel = Channel::create(['name' => 'Before authority race', 'slug' => 'authority-race-'.uniqid()]);
        $channel->websites()->sync([$website->getKey()]);
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'authority-race', 'authority-session', ['channels.write'], [$website->getKey()]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $request = $this->productionRequest('api.v2.backend.channels.update', $context, [
            'name' => 'After authority race', 'website_id' => $website->getKey(),
        ], ['id' => $channel->getKey()]);
        $request->attributes->set(
            ResolveApiV2RiskContext::ATTRIBUTE,
            app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->getKey()]),
        );
        DB::connection()->commit();
        $started = tempnam(sys_get_temp_dir(), 'wncms-permission-started-');
        $completed = tempnam(sys_get_temp_dir(), 'wncms-permission-completed-');
        $pid = pcntl_fork();
        $revokedBeforeMutation = false;

        if ($pid === 0) {
            DB::disconnect();
            $deadline = microtime(true) + 10;
            while (filesize($started) === 0 && microtime(true) < $deadline) {
                clearstatcache(true, $started);
                usleep(10_000);
            }
            while (microtime(true) < $deadline) {
                try {
                    $deleted = DB::table(config('permission.table_names.model_has_permissions'))
                        ->where('model_type', $actor->getMorphClass())
                        ->where(config('permission.column_names.model_morph_key'), $actor->getKey())
                        ->where('permission_id', $permission->getKey())
                        ->delete();
                    if ($deleted === 1) {
                        file_put_contents($completed, 'completed', LOCK_EX);
                        exit(0);
                    }
                } catch (\Illuminate\Database\QueryException) {
                    DB::disconnect();
                }
                usleep(20_000);
            }
            exit(2);
        }

        try {
            $response = $this->executeRiskMiddleware($request, function () use (&$revokedBeforeMutation, $started, $completed, $channel) {
                file_put_contents($started, 'started', LOCK_EX);
                usleep(100_000);
                clearstatcache(true, $completed);
                $revokedBeforeMutation = filesize($completed) > 0;
                Channel::query()->whereKey($channel->getKey())->update(['name' => 'After authority race']);

                return response()->json(['ok' => true]);
            });

            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
            DB::disconnect();
            if ($response->getStatusCode() === 200) {
                $this->assertFalse($revokedBeforeMutation, 'A successful mutation must serialize before permission revoke.');
                $this->assertSame('After authority race', Channel::query()->findOrFail($channel->getKey())->name);
            } else {
                $this->assertSame(503, $response->getStatusCode(), (string) $response->getContent());
                $this->assertSame('Before authority race', Channel::query()->findOrFail($channel->getKey())->name);
            }
            $this->assertFalse(DB::table(config('permission.table_names.model_has_permissions'))
                ->where('model_type', $actor->getMorphClass())
                ->where(config('permission.column_names.model_morph_key'), $actor->getKey())
                ->where('permission_id', $permission->getKey())
                ->exists());
        } finally {
            pcntl_waitpid($pid, $status, WNOHANG);
            @unlink($started);
            @unlink($completed);
            Channel::query()->whereKey($channel->getKey())->delete();
            User::query()->whereKey($actor->getKey())->delete();
            DB::connection()->beginTransaction();
        }
    }

    /**
     * Verify missing requested Website rows fail closed before direct execution.
     */
    public function test_missing_requested_website_fails_closed_in_direct_mode(): void
    {
        uss('api_high_risk_action_mode', 'direct');
        config(['wncms.models.channel.website_mode' => 'multi']);
        $missing = (int) Website::query()->max('id') + 1000;
        $actor = User::query()->firstOrFail();
        $existing = Website::query()->firstOrFail();
        $actor->websites()->syncWithoutDetaching([$existing->id]);
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'missing-website', 'missing-session', ['channels.write'], [$existing->id, $missing]);
        $channel = Channel::create(['name' => 'Missing website', 'slug' => 'risk-missing-'.uniqid()]);
        $channel->websites()->sync([$existing->id]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $request = $this->productionRequest('api.v2.backend.channels.update', $context, [
            'name' => 'Must not execute',
            'website_id' => $missing,
        ], ['id' => $channel->id]);

        $this->expectRiskContextCode('website.scope_denied', fn () => app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]));
        $this->assertSame('Missing website', $channel->fresh()->name);
    }

    /**
     * Verify an unvalidated confirmation header cannot enable stale-plan resolution.
     */
    public function test_direct_mode_garbage_confirmation_does_not_allow_missing_targets(): void
    {
        uss('api_high_risk_action_mode', 'direct');
        $missing = (int) Channel::query()->max('id') + 1000;
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $request = $this->productionRequest('api.v2.backend.channels.update', $this->context(), [
            'name' => 'Must not execute',
        ], ['id' => $missing]);
        $request->headers->set('X-WNCMS-Confirmation', 'garbage');

        try {
            app(OperationRiskContextResolver::class)->resolveExecution($request, $operation, ['id' => $missing]);
            $this->fail('Expected direct missing target denial.');
        } catch (RiskContextException $exception) {
            $this->assertSame('resource_not_found', $exception->errorCode);
            $this->assertSame(404, $exception->httpStatus);
        }
    }

    /**
     * Verify a different morph type cannot impersonate revoked actor membership.
     */
    public function test_actor_membership_recheck_uses_the_relation_morph_discriminator(): void
    {
        config(['wncms.models.channel.website_mode' => 'multi']);
        $actor = User::query()->firstOrFail();
        $otherActor = User::factory()->create();
        $website = Website::create([
            'user_id' => $otherActor->getKey(),
            'domain' => 'morph-collision-'.uniqid().'.test',
            'site_name' => 'Morph collision',
            'theme' => 'default',
        ]);
        $channel = Channel::create([
            'id' => $actor->getKey(),
            'name' => 'Morph collision',
            'slug' => 'morph-collision-'.uniqid(),
        ]);
        $actor->websites()->syncWithoutDetaching([$website->getKey()]);
        $channel->websites()->syncWithoutDetaching([$website->getKey()]);
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'morph-collision', 'morph-session', ['channels.write'], [$website->getKey()]);
        $riskContext = new \Wncms\Api\V2\Risk\RiskContext([], [
            'website_scope' => [
                'requested_ids' => [$website->getKey()],
                'requested_rows' => [$website->getAttributes()],
                'target_ids' => [$website->getKey()],
                'target_count' => 1,
                'scoped_model' => true,
            ],
        ], []);

        $actor->websites()->detach($website->getKey());

        $this->expectRiskContextCode('website.scope_denied', fn () => app(\Wncms\Auth\Api\V2\WebsiteScopeGuard::class)->assertResolvedScope($context, $riskContext, true));
    }

    /**
     * Verify direct and plan-create bulk resolution reject a partially missing set.
     */
    public function test_bulk_resolution_requires_every_requested_target_to_exist(): void
    {
        $existing = Channel::create(['name' => 'Existing', 'slug' => 'complete-bulk-'.uniqid()]);
        $missing = (int) Channel::query()->max('id') + 1000;
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.bulk_delete');
        $request = $this->productionRequest('api.v2.backend.channels.bulk_delete', $this->context(), [
            'model_ids' => [$existing->getKey(), $missing],
        ]);

        try {
            app(OperationRiskContextResolver::class)->resolveRequest($request, $operation);
            $this->fail('Expected incomplete target denial.');
        } catch (RiskContextException $exception) {
            $this->assertSame('validation.failed', $exception->errorCode);
            $this->assertSame(422, $exception->httpStatus);
        }
        $this->assertNotNull($existing->fresh());
    }

    /**
     * Verify action-plan creation enforces the target operation guards before persistence.
     */
    public function test_action_plan_creation_rejects_target_credential_ability_and_permission_before_persistence(): void
    {
        config(['wncms.models.channel.website_mode' => 'multi']);
        $actor = User::query()->firstOrFail();
        $website = Website::query()->firstOrFail();
        $actor->websites()->syncWithoutDetaching([$website->id]);
        $channel = Channel::create(['name' => 'Plan target', 'slug' => 'plan-target-'.uniqid()]);
        $channel->websites()->sync([$website->id]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $beforePlans = DB::table('api_action_plans')->count();
        $beforeEvents = DB::table('api_security_events')->where('event_type', 'risk.plan.created')->count();

        $noAbility = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'plan-no-ability', 'plan-session', [], [$website->id]);
        $response = app(ActionPlanController::class)->store($this->actionPlanRequest($noAbility, $operation->id, [
            'name' => 'After', 'website_id' => $website->id,
        ], ['id' => $channel->id]));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('authorization.ability_denied', $response->getData(true)['meta']['error_code']);

        $noPermission = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'plan-no-permission', 'plan-session', ['channels.write'], [$website->id]);
        Permission::findOrCreate('channel_edit', 'web');
        $actor->givePermissionTo('channel_edit');
        $actor->checkPermissionTo('channel_edit');
        $actor->revokePermissionTo('channel_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $response = app(ActionPlanController::class)->store($this->actionPlanRequest($noPermission, $operation->id, [
            'name' => 'After', 'website_id' => $website->id,
        ], ['id' => $channel->id]));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('authorization.permission_denied', $response->getData(true)['meta']['error_code']);

        $credentialOperation = app(ApiContractRegistry::class)->operation('backend.users.update');
        $service = new AuthenticationContext($actor, ApiCredential::TYPE_SERVICE_TOKEN, 'plan-service', null, ['users.write'], [$website->id]);
        $response = app(ActionPlanController::class)->store($this->actionPlanRequest($service, $credentialOperation->id, [
            'website_id' => $website->id,
        ], ['id' => $actor->id]));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('risk.credential_type_denied', $response->getData(true)['meta']['error_code']);

        $this->assertSame($beforePlans, DB::table('api_action_plans')->count());
        $this->assertSame($beforeEvents, DB::table('api_security_events')->where('event_type', 'risk.plan.created')->count());
    }

    /**
     * Verify plan row and mandatory event commit atomically after a locked authorization snapshot.
     */
    public function test_action_plan_creation_commits_authorized_snapshot_and_event_atomically(): void
    {
        config(['wncms.models.channel.website_mode' => 'multi']);
        $actor = User::query()->firstOrFail();
        $website = Website::query()->firstOrFail();
        $actor->websites()->syncWithoutDetaching([$website->id]);
        Permission::findOrCreate('channel_edit', 'web');
        $actor->givePermissionTo('channel_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $channel = Channel::create(['name' => 'Atomic plan', 'slug' => 'atomic-plan-'.uniqid()]);
        $channel->websites()->sync([$website->id]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'plan-atomic', 'plan-session', ['channels.write'], [$website->id]);

        $response = app(ActionPlanController::class)->store($this->actionPlanRequest($context, $operation->id, [
            'name' => 'After', 'website_id' => $website->id,
        ], ['id' => $channel->id]));

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getContent());
        $this->assertSame(1, DB::table('api_action_plans')->where('credential_id', 'plan-atomic')->count());
        $this->assertSame(1, DB::table('api_security_events')->where('event_type', 'risk.plan.created')->where('credential_id', 'plan-atomic')->count());
    }

    public function test_single_mode_plan_creation_rejects_multiple_websites_and_accepts_one_key(): void
    {
        config(['wncms.models.channel.website_mode' => 'single']);
        $actor = User::query()->firstOrFail();
        $website = Website::query()->firstOrFail();
        $other = Website::create([
            'user_id' => $actor->id,
            'domain' => 'single-plan-'.uniqid().'.test',
            'site_name' => 'Single Plan Other',
            'theme' => 'default',
        ]);
        $actor->websites()->syncWithoutDetaching([$website->id, $other->id]);
        $actor->givePermissionTo(Permission::findOrCreate('channel_edit', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $channel = Channel::create(['name' => 'Single plan', 'slug' => 'single-plan-'.uniqid()]);
        $channel->websites()->sync([$website->id]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'plan-single', 'plan-session', ['channels.write'], [$website->id, $other->id]);
        $beforePlans = DB::table('api_action_plans')->count();

        $rejected = app(ActionPlanController::class)->store($this->actionPlanRequest($context, $operation->id, [
            'name' => 'Must not plan', 'website_id' => $website->id,
            'website_ids' => [$website->id, $other->id],
        ], ['id' => $channel->id]));
        $this->assertSame(422, $rejected->getStatusCode());
        $this->assertSame('validation.failed', $rejected->getData(true)['meta']['error_code']);
        $this->assertSame($beforePlans, DB::table('api_action_plans')->count());

        $accepted = app(ActionPlanController::class)->store($this->actionPlanRequest($context, $operation->id, [
            'name' => 'Valid plan', 'website_key' => 'website:'.$other->id,
        ], ['id' => $channel->id]));
        $this->assertSame(201, $accepted->getStatusCode(), (string) $accepted->getContent());
        $this->assertSame($beforePlans + 1, DB::table('api_action_plans')->count());
    }

    public function test_single_mode_omitted_patch_requires_one_existing_binding_in_direct_resolution(): void
    {
        config(['wncms.models.channel.website_mode' => 'single']);
        $actor = User::query()->firstOrFail();
        $first = Website::query()->firstOrFail();
        $second = Website::create([
            'user_id' => $actor->id,
            'domain' => 'single-direct-'.uniqid().'.test',
            'site_name' => 'Single Direct Other',
            'theme' => 'default',
        ]);
        $actor->websites()->syncWithoutDetaching([$first->id, $second->id]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');

        foreach ([[], [$first->id, $second->id]] as $websiteIds) {
            $channel = Channel::create(['name' => 'Single direct', 'slug' => 'single-direct-'.uniqid()]);
            $channel->websites()->sync($websiteIds);
            [$request] = $this->plannedChannelRequest($channel, [$first->id, $second->id], ['name' => 'After']);

            try {
                app(OperationRiskContextResolver::class)->resolveRequest($request, $operation, ['id' => $channel->id]);
                $this->fail('Expected invalid omitted single-site binding.');
            } catch (RiskContextException $exception) {
                $this->assertSame('validation.failed', $exception->errorCode);
                $this->assertSame(422, $exception->httpStatus);
            }
        }
    }

    public function test_single_mode_omitted_patch_is_identical_for_planning_and_preserves_one_binding(): void
    {
        config(['wncms.models.channel.website_mode' => 'single']);
        $actor = User::query()->firstOrFail();
        $website = Website::query()->firstOrFail();
        $actor->websites()->syncWithoutDetaching([$website->id]);
        $actor->givePermissionTo(Permission::findOrCreate('channel_edit', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'plan-single-omitted', 'plan-session', ['channels.write'], [$website->id]);

        $invalid = Channel::create(['name' => 'Single invalid plan', 'slug' => 'single-invalid-plan-'.uniqid()]);
        $response = app(ActionPlanController::class)->store($this->actionPlanRequest($context, $operation->id, ['name' => 'After'], ['id' => $invalid->id]));
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('validation.failed', $response->getData(true)['meta']['error_code']);

        $valid = Channel::create(['name' => 'Single valid plan', 'slug' => 'single-valid-plan-'.uniqid()]);
        $valid->websites()->sync([$website->id]);
        $response = app(ActionPlanController::class)->store($this->actionPlanRequest($context, $operation->id, ['name' => 'After'], ['id' => $valid->id]));
        $this->assertSame(201, $response->getStatusCode(), (string) $response->getContent());
        $this->assertSame([$website->id], $valid->fresh()->websites()->pluck('websites.id')->map(static fn ($id): int => (int) $id)->all());
    }

    /**
     * Verify mandatory audit failure rolls back plan storage without returning a secret.
     */
    public function test_action_plan_creation_audit_failure_returns_no_confirmation_and_rolls_back(): void
    {
        config(['wncms.models.channel.website_mode' => 'multi']);
        $actor = User::query()->firstOrFail();
        $website = Website::query()->firstOrFail();
        $actor->websites()->syncWithoutDetaching([$website->id]);
        Permission::findOrCreate('channel_edit', 'web');
        $actor->givePermissionTo('channel_edit');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $channel = Channel::create(['name' => 'Rollback plan', 'slug' => 'rollback-plan-'.uniqid()]);
        $channel->websites()->sync([$website->id]);
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'plan-rollback', 'plan-session', ['channels.write'], [$website->id]);
        $beforePlans = DB::table('api_action_plans')->count();
        config(['wncms-api-v2.auth_security.security_event_correlation.keys' => []]);

        $response = app(ActionPlanController::class)->store($this->actionPlanRequest($context, $operation->id, [
            'name' => 'After', 'website_id' => $website->id,
        ], ['id' => $channel->id]));

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('security.audit_unavailable', $response->getData(true)['meta']['error_code']);
        $this->assertArrayNotHasKey('confirmation', (array) $response->getData(true)['data']);
        $this->assertSame($beforePlans, DB::table('api_action_plans')->count());
    }

    /**
     * Build one action-plan controller request with authentication context.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $parameters
     */
    private function actionPlanRequest(AuthenticationContext $context, string $operation, array $input, array $parameters): Request
    {
        $request = Request::create('/api/v2/backend/action-plans', 'POST', compact('operation', 'input', 'parameters'));
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);

        return $request;
    }

    /**
     * Build one bound production route request.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $parameters
     */
    private function productionRequest(string $routeName, AuthenticationContext $context, array $input, array $parameters = []): Request
    {
        $path = '/api/v2/backend/_risk-target';
        $request = Request::create($path, 'POST', $input);
        $route = new Route(['POST'], $path, fn () => null);
        $route->defaults('api_operation_id', str_replace('api.v2.', '', $routeName));
        $route->bind($request);
        foreach ($parameters as $key => $value) {
            $route->setParameter($key, $value);
        }
        $request->setRouteResolver(fn () => $route);
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);

        return $request;
    }

    /**
     * Assert one stable risk-context denial.
     */
    private function expectRiskContextCode(string $code, callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected risk-context denial.');
        } catch (RiskContextException $exception) {
            $this->assertSame($code, $exception->errorCode);
            $this->assertSame(403, $exception->httpStatus);
        }
    }

    /**
     * Execute risk middleware without the test harness transaction becoming its owner.
     */
    private function executeRiskMiddleware(Request $request, callable $next): mixed
    {
        $suspended = DB::transactionLevel() > 0;
        if ($suspended) {
            while (DB::transactionLevel() > 0) {
                DB::commit();
            }
            $this->suspendedTestTransaction = true;
        }

        try {
            return app(EnforceApiV2RiskPolicy::class)->handle($request, $next);
        } finally {
            if ($suspended) {
                DB::beginTransaction();
            }
        }
    }

    /** @return array<int, string> */
    private function snapshotTables(): array
    {
        return [
            'users',
            'websites',
            'channels',
            'settings',
            config('permission.table_names.roles', 'roles'),
            config('permission.table_names.permissions', 'permissions'),
            config('permission.table_names.model_has_roles', 'model_has_roles'),
            config('permission.table_names.model_has_permissions', 'model_has_permissions'),
            config('permission.table_names.role_has_permissions', 'role_has_permissions'),
            'model_has_websites',
            'api_action_plans',
            'api_security_events',
        ];
    }

    /**
     * Build one interactive context for production risk metadata.
     */
    private function context(array $websiteIds = []): AuthenticationContext
    {
        $user = User::query()->firstOrFail();
        foreach (['channel_edit', 'channel_bulk_delete'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return new AuthenticationContext($user, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'production-risk', 'session-risk', ['*'], $websiteIds);
    }

    /**
     * Build a production channel update request with a scoped actor.
     *
     * @param  array<int, int>  $websiteIds
     * @param  array<string, mixed>  $input
     * @return array{\Illuminate\Http\Request, \Wncms\Auth\Api\V2\AuthenticationContext, \Wncms\Api\V2\Data\ApiOperationContract}
     */
    private function plannedChannelRequest(Channel $channel, array $websiteIds, array $input): array
    {
        $operation = app(ApiContractRegistry::class)->operation('backend.channels.update');
        $request = Request::create('/api/v2/backend/channels/'.$channel->id, 'PATCH', $input);
        $route = app('router')->getRoutes()->getByName('api.v2.backend.channels.update');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $context = $this->context($websiteIds);
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);

        return [$request, $context, $operation];
    }
}

class Task8ScopedCrossConnectionChannel extends Channel
{
    protected $connection = 'task8_scope_cross_connection';

    protected $table = 'channels';

    public static function getMultiWebsiteMode(): string
    {
        return 'multi';
    }
}
