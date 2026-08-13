<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Risk\ActionPlanException;
use Wncms\Api\V2\Risk\ActionPlanService;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Http\Middleware\EnforceApiV2RiskPolicy;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class ActionPlanPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => 'v1',
            'keys' => ['v1' => [
                'ip' => 'plan-ip-key-123456789012345678901234',
                'login_identifier' => 'plan-login-key-12345678901234567890',
                'user_agent' => 'plan-agent-key-12345678901234567890',
            ]],
        ]]);
        CarbonImmutable::setTestNow('2026-08-14 00:00:00 UTC');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_plan_is_hash_only_five_minute_and_single_use(): void
    {
        [$context, $operation] = $this->fixture();
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);

        $stored = app('db')->table('api_action_plans')->where('plan_id', $plan['id'])->first();
        $this->assertNotNull($stored);
        $this->assertStringNotContainsString($plan['confirmation'], json_encode($stored, JSON_THROW_ON_ERROR));
        $this->assertSame('2026-08-14T00:05:00+00:00', $plan['expires_at']);

        app(ActionPlanService::class)->consume($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]);
        $this->expectPlanCode('risk.confirmation_reused', fn () => app(ActionPlanService::class)->consume($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]));
    }

    public function test_changed_input_target_scope_or_authorization_makes_plan_stale(): void
    {
        [$context, $operation] = $this->fixture();
        $otherActor = User::query()->whereKeyNot($context->actorId())->firstOrFail();

        foreach ([
            [new AuthenticationContext($context->actor(), ApiCredential::TYPE_INTERACTIVE_ACCESS, 'access-1', 'session-1', ['tokens.create'], [1]), ['name' => 'changed'], ['version' => 7]],
            [new AuthenticationContext($context->actor(), ApiCredential::TYPE_INTERACTIVE_ACCESS, 'access-1', 'session-1', ['tokens.create'], [1]), ['name' => 'exact'], ['version' => 8]],
            [new AuthenticationContext($context->actor(), ApiCredential::TYPE_INTERACTIVE_ACCESS, 'access-1', 'session-1', ['tokens.create'], [2]), ['name' => 'exact'], ['version' => 7]],
            [new AuthenticationContext($context->actor(), ApiCredential::TYPE_INTERACTIVE_ACCESS, 'access-1', 'session-1', [], [1]), ['name' => 'exact'], ['version' => 7]],
            [new AuthenticationContext($context->actor(), ApiCredential::TYPE_INTERACTIVE_ACCESS, 'access-2', 'session-1', ['tokens.create'], [1]), ['name' => 'exact'], ['version' => 7]],
            [new AuthenticationContext($context->actor(), ApiCredential::TYPE_INTERACTIVE_ACCESS, 'access-1', 'session-2', ['tokens.create'], [1]), ['name' => 'exact'], ['version' => 7]],
            [new AuthenticationContext($otherActor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'access-1', 'session-1', ['tokens.create'], [1]), ['name' => 'exact'], ['version' => 7]],
        ] as [$changedContext, $input, $target]) {
            $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
            $this->expectPlanCode('risk.plan_stale', fn () => app(ActionPlanService::class)->consume($changedContext, $operation, $plan['confirmation'], $input, $target));
        }

        $permission = Permission::findOrCreate('api_token_create', 'web');
        $context->actor()->givePermissionTo($permission);
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $context->actor()->revokePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->expectPlanCode('risk.plan_stale', fn () => app(ActionPlanService::class)->consume($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]));
    }

    public function test_plan_expiry_is_stable_conflict(): void
    {
        [$context, $operation] = $this->fixture();
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        CarbonImmutable::setTestNow('2026-08-14 00:05:01 UTC');

        $this->expectPlanCode('risk.plan_expired', fn () => app(ActionPlanService::class)->consume($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]));
    }

    public function test_planned_middleware_maps_missing_and_stale_confirmations_to_428_and_409(): void
    {
        [$context, $operation] = $this->fixture();
        $this->registerOperation($operation);
        uss('api_high_risk_action_mode', 'planned');

        $missing = $this->riskRequest($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $response = app(EnforceApiV2RiskPolicy::class)->handle($missing, fn () => response()->json(['ok' => true]));
        $this->assertSame(428, $response->getStatusCode());
        $this->assertSame('risk.plan_required', $response->getData(true)['meta']['error_code']);

        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $stale = $this->riskRequest($context, $operation, ['name' => 'changed'], ['version' => 7]);
        $stale->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        $response = app(EnforceApiV2RiskPolicy::class)->handle($stale, fn () => response()->json(['ok' => true]));
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('risk.plan_stale', $response->getData(true)['meta']['error_code']);

        $serviceContext = new AuthenticationContext($context->actor(), ApiCredential::TYPE_SERVICE_TOKEN, 'service-1', null, ['tokens.create'], [1]);
        $critical = $this->riskRequest($serviceContext, $operation, ['expiry' => 'permanent'], ['version' => 7]);
        $response = app(EnforceApiV2RiskPolicy::class)->handle($critical, fn () => response()->json(['ok' => true]));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('risk.credential_type_denied', $response->getData(true)['meta']['error_code']);
    }

    public function test_async_plan_is_consumed_only_after_successful_enqueue(): void
    {
        [$context, $operation] = $this->fixture();
        $this->registerOperation($operation);
        uss('api_high_risk_action_mode', 'planned');
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $request = $this->riskRequest($context, $operation, ['name' => 'exact'], ['version' => 7], true);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);

        $failed = app(EnforceApiV2RiskPolicy::class)->handle($request, fn () => new JsonResponse(['queued' => false], 503));
        $this->assertSame(503, $failed->getStatusCode());
        $this->assertNull(app('db')->table('api_action_plans')->where('plan_id', $plan['id'])->value('consumed_at'));

        $succeeded = app(EnforceApiV2RiskPolicy::class)->handle($request, fn () => new JsonResponse(['queued' => true], 202));
        $this->assertSame(202, $succeeded->getStatusCode());
        $this->assertNotNull(app('db')->table('api_action_plans')->where('plan_id', $plan['id'])->value('consumed_at'));
    }

    public function test_async_reservation_prevents_double_enqueue_and_expired_lease_can_be_reclaimed(): void
    {
        [$context, $operation] = $this->fixture();
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $first = app(ActionPlanService::class)->reserve($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]);

        $this->expectPlanCode('risk.confirmation_reused', fn () => app(ActionPlanService::class)->reserve($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]));

        CarbonImmutable::setTestNow('2026-08-14 00:00:31 UTC');
        $second = app(ActionPlanService::class)->reserve($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]);
        $this->assertNotSame($first, $second);
        $this->assertSame($second, app('db')->table('api_action_plans')->where('plan_id', $plan['id'])->value('reservation_id'));
    }

    public function test_two_processes_racing_for_one_confirmation_allow_only_one_enqueue_reservation(): void
    {
        if (! function_exists('pcntl_fork') || ! function_exists('stream_socket_pair')) {
            $this->markTestSkipped('Process concurrency primitives are unavailable.');
        }

        [$context, $operation] = $this->fixture();
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        DB::connection()->commit();
        $resultFile = tempnam(sys_get_temp_dir(), 'wncms-plan-race-');
        $children = [];

        try {
            for ($index = 0; $index < 2; $index++) {
                $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
                $this->assertIsArray($sockets);
                $pid = pcntl_fork();
                if ($pid === 0) {
                    fclose($sockets[0]);
                    fread($sockets[1], 1);
                    DB::disconnect();
                    try {
                        app(ActionPlanService::class)->reserve($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]);
                        $result = 'reserved';
                    } catch (ActionPlanException $exception) {
                        $result = $exception->errorCode;
                    }
                    file_put_contents($resultFile, $result."\n", FILE_APPEND | LOCK_EX);
                    exit(0);
                }

                fclose($sockets[1]);
                $children[] = [$pid, $sockets[0]];
            }

            foreach ($children as [, $socket]) {
                fwrite($socket, '1');
                fclose($socket);
            }
            foreach ($children as [$pid]) {
                pcntl_waitpid($pid, $status);
                $this->assertTrue(pcntl_wifexited($status));
                $this->assertSame(0, pcntl_wexitstatus($status));
            }

            DB::disconnect();
            $results = array_filter(explode("\n", (string) file_get_contents($resultFile)));
            sort($results);
            $this->assertSame(['reserved', 'risk.confirmation_reused'], $results);
            $this->assertSame(1, DB::table('api_action_plans')->where('plan_id', $plan['id'])->whereNotNull('reservation_id')->count());
        } finally {
            @unlink($resultFile);
            DB::table('api_action_plans')->where('plan_id', $plan['id'])->delete();
            DB::table('api_security_events')->where('actor_id', $context->actorId())->delete();
            User::query()->whereKey($context->actorId())->delete();
            DB::connection()->beginTransaction();
        }
    }

    private function fixture(): array
    {
        $actor = User::create([
            'username' => 'risk-plan-'.uniqid(),
            'email' => 'risk-plan-'.uniqid().'@example.test',
            'password' => 'unused-hash',
            'email_verified_at' => now(),
        ]);
        $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'access-1', 'session-1', ['tokens.create'], [1]);
        $operation = $this->operation();

        return [$context, $operation];
    }

    private function operation(string $permission = 'api_token_create'): ApiOperationContract
    {
        return new ApiOperationContract(
            id: 'backend.tokens.store', domain: 'tokens', surface: 'backend', method: 'POST',
            path: '/api/v2/backend/tokens', routeName: 'api.v2.backend.tokens.store',
            permission: $permission, ability: 'tokens.create', websiteScoped: true,
            risk: 'write', implementation: 'domain', request: ApiSchema::object(), response: ApiSchema::object(),
            securityRisk: 'high', actionPlanEligible: true,
        );

    }

    private function registerOperation(ApiOperationContract $operation): void
    {
        $registry = app(ApiContractRegistry::class);
        if (! isset($registry->domains()['tokens'])) {
            $registry->registerDomain(new ApiDomainContract('tokens', 'Tokens'));
        }
        if ($registry->operation($operation->id) === null) {
            $registry->registerOperation($operation);
        }
    }

    private function riskRequest(AuthenticationContext $context, ApiOperationContract $operation, array $input, array $targetState, bool $async = false): Request
    {
        $request = Request::create('/api/v2/backend/tokens', 'POST', [
            'input' => $input,
            'target_state' => $targetState,
        ]);
        $route = new Route(['POST'], '/api/v2/backend/tokens', fn () => null);
        $route->defaults('api_operation_id', $operation->id);
        $route->defaults('api_async_enqueue', $async);
        $request->setRouteResolver(fn () => $route);
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);

        return $request;
    }

    private function expectPlanCode(string $code, callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected action-plan denial.');
        } catch (ActionPlanException $exception) {
            $this->assertSame($code, $exception->errorCode);
            $this->assertSame(409, $exception->httpStatus);
        }
    }
}
