<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Risk\ActionPlanService;
use Wncms\Api\V2\Risk\OperationRiskContextResolver;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Http\Middleware\EnforceApiV2RiskPolicy;
use Wncms\Http\Middleware\ResolveApiV2RiskContext;
use Wncms\Models\Channel;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class ProductionRiskOperationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Configure mandatory security event correlation for plan tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => 'v1',
            'keys' => ['v1' => [
                'ip' => 'production-risk-ip-key-123456789012345',
                'login_identifier' => 'production-risk-login-key-123456789',
                'user_agent' => 'production-risk-agent-key-123456789',
            ]],
        ]]);
    }

    /**
     * Verify an external production bridge fails closed before controller dispatch.
     *
     * @return void
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
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $this->context());
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, app(OperationRiskContextResolver::class)->resolveRequest($request, $operation));
        $executions = 0;

        $response = app(EnforceApiV2RiskPolicy::class)->handle($request, function () use (&$executions) {
            $executions++;

            return response()->json(['ok' => true]);
        });

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame(0, $executions);
    }

    /**
     * Verify a real generic resource target is refreshed before plan reservation.
     *
     * @return void
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

        $response = app(EnforceApiV2RiskPolicy::class)->handle($request, function () use (&$executions) {
            $executions++;

            return response()->json(['ok' => true]);
        });

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(0, $executions);
    }

    /**
     * Verify runtime environment changes are refreshed before confirmation.
     *
     * @return void
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

        $response = app(EnforceApiV2RiskPolicy::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('risk.plan_stale', $response->getData(true)['meta']['error_code']);
    }

    /**
     * Verify bulk target creation or deletion after planning invalidates membership.
     *
     * @return void
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
        $snapshot = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation);
        $plan = app(ActionPlanService::class)->createResolved($context, $operation, $snapshot);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $snapshot);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        Channel::create(['id' => $missingId, 'name' => 'Appeared', 'slug' => 'risk-bulk-new-'.uniqid()]);

        $created = app(EnforceApiV2RiskPolicy::class)->handle($request, fn () => response()->json(['ok' => true]));
        $this->assertSame(409, $created->getStatusCode());

        $freshRequest = Request::create('/api/v2/backend/channels/bulk_delete', 'POST', ['model_ids' => [$missingId, $existing->id]]);
        $route->bind($freshRequest);
        $freshRequest->setRouteResolver(fn () => $route);
        $freshRequest->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        $freshSnapshot = app(OperationRiskContextResolver::class)->resolveRequest($freshRequest, $operation);
        $freshPlan = app(ActionPlanService::class)->createResolved($context, $operation, $freshSnapshot);
        $freshRequest->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, $freshSnapshot);
        $freshRequest->headers->set('X-WNCMS-Confirmation', $freshPlan['confirmation']);
        $existing->delete();

        $deleted = app(EnforceApiV2RiskPolicy::class)->handle($freshRequest, fn () => response()->json(['ok' => true]));
        $this->assertSame(409, $deleted->getStatusCode());
    }

    /**
     * Verify a second process changing a target after planning is rejected at execution.
     *
     * @return void
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

            $response = app(EnforceApiV2RiskPolicy::class)->handle($request, fn () => response()->json(['ok' => true]));

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
     *
     * @return void
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

        $response = app(EnforceApiV2RiskPolicy::class)->handle($request, function () use (&$executions) {
            $executions++;

            return response()->json(['ok' => true]);
        });

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('risk.credential_type_denied', $response->getData(true)['meta']['error_code']);
        $this->assertSame(0, $executions);
    }

    /**
     * Build one interactive context for production risk metadata.
     *
     * @return \Wncms\Auth\Api\V2\AuthenticationContext
     */
    private function context(): AuthenticationContext
    {
        $user = User::query()->firstOrFail();

        return new AuthenticationContext($user, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'production-risk', 'session-risk', ['*'], []);
    }
}
