<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Risk\ActionPlanException;
use Wncms\Api\V2\Risk\ActionPlanService;
use Wncms\Api\V2\Risk\OperationRiskContextResolver;
use Wncms\Api\V2\Risk\RiskContext;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Events\ApiSecurityEventRecorded;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Http\Middleware\AssignApiV2RequestId;
use Wncms\Http\Middleware\EnforceApiV2Idempotency;
use Wncms\Http\Middleware\EnforceApiV2RiskPolicy;
use Wncms\Http\Middleware\ResolveApiV2RiskContext;
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
        config(['wncms-api-v2.idempotency.store' => 'array']);
        Cache::flush();
        Cache::flushLocks();
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

    public function test_server_target_or_environment_change_makes_plan_stale(): void
    {
        [$context, $operation] = $this->fixture();
        $version = 7;
        $environmentRisk = 'normal';
        $resolver = app(OperationRiskContextResolver::class);
        $resolver->register($operation->id, function () use (&$version, &$environmentRisk): array {
            return [
                'target_state' => ['version' => $version],
                'environment' => ['security_risk' => $environmentRisk],
            ];
        });
        $created = $resolver->resolve($operation, ['name' => 'exact']);
        $targetPlan = app(ActionPlanService::class)->createResolved($context, $operation, $created);
        $version = 8;

        $this->expectPlanCode('risk.plan_stale', fn () => app(ActionPlanService::class)->consumeResolved(
            $context, $operation, $targetPlan['confirmation'], $resolver->resolve($operation, ['name' => 'exact']),
        ));

        $version = 7;
        $environmentPlan = app(ActionPlanService::class)->createResolved(
            $context, $operation, $resolver->resolve($operation, ['name' => 'exact']),
        );
        $environmentRisk = 'critical';
        $this->expectPlanCode('risk.plan_stale', fn () => app(ActionPlanService::class)->consumeResolved(
            $context, $operation, $environmentPlan['confirmation'], $resolver->resolve($operation, ['name' => 'exact']),
        ));
    }

    public function test_middleware_re_resolves_target_inside_execution_transaction(): void
    {
        [$context, $operation] = $this->fixture();
        $this->registerOperation($operation);
        uss('api_high_risk_action_mode', 'planned');
        $version = 7;
        app(OperationRiskContextResolver::class)->register($operation->id, function () use (&$version): array {
            return ['target_state' => ['version' => $version], 'model_keys' => ['setting']];
        });
        $request = $this->riskRequest($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $created = app(OperationRiskContextResolver::class)->resolveRequest($request, $operation);
        $plan = app(ActionPlanService::class)->createResolved($context, $operation, $created);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        $version = 8;
        $executions = 0;

        $response = app(EnforceApiV2RiskPolicy::class)->handle($request, function () use (&$executions): JsonResponse {
            $executions++;

            return new JsonResponse(['ok' => true]);
        });

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('risk.plan_stale', $response->getData(true)['meta']['error_code']);
        $this->assertSame(0, $executions);
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
        $servicePlan = app(ActionPlanService::class)->create($serviceContext, $operation, ['expiry' => 'permanent'], ['version' => 7]);
        $critical->headers->set('X-WNCMS-Confirmation', $servicePlan['confirmation']);
        $response = app(EnforceApiV2RiskPolicy::class)->handle($critical, fn () => response()->json(['ok' => true]));
        $this->assertSame(200, $response->getStatusCode());
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

    public function test_confirmation_audit_failure_rolls_back_domain_side_effect_and_plan_consumption(): void
    {
        [$context, $operation] = $this->fixture();
        $this->registerOperation($operation);
        uss('api_high_risk_action_mode', 'planned');
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $request = $this->riskRequest($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, new RiskContext(['name' => 'exact'], ['version' => 7], [], ['user']));
        config(['wncms-api-v2.auth_security.security_event_correlation.keys' => []]);
        $before = $context->actor()->username;

        $response = app(EnforceApiV2RiskPolicy::class)->handle($request, function () use ($context): JsonResponse {
            $context->actor()->newQuery()->whereKey($context->actorId())->update(['username' => 'must-roll-back']);

            return new JsonResponse(['ok' => true]);
        });

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame($before, $context->actor()->fresh()->username);
        $this->assertNull(DB::table('api_action_plans')->where('plan_id', $plan['id'])->value('consumed_at'));
    }

    public function test_external_async_enqueue_without_transactional_outbox_fails_before_side_effect(): void
    {
        [$context, $operation] = $this->fixture();
        $this->registerOperation($operation);
        uss('api_high_risk_action_mode', 'planned');
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $request = $this->riskRequest($context, $operation, ['name' => 'exact'], ['version' => 7], true, false);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        $executions = 0;

        $response = app(EnforceApiV2RiskPolicy::class)->handle($request, function () use (&$executions): JsonResponse {
            $executions++;

            return new JsonResponse(['queued' => true], 202);
        });

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame(0, $executions);
    }

    public function test_cross_connection_domain_target_fails_before_side_effect(): void
    {
        [$context, $operation] = $this->fixture();
        $this->registerOperation($operation);
        uss('api_high_risk_action_mode', 'planned');
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        config(['database.connections.task8_cross_connection' => config('database.connections.sqlite')]);
        config(['wncms.models.task8_cross_connection' => ['class' => Task8CrossConnectionModel::class]]);
        $request = $this->riskRequest($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, new RiskContext(['name' => 'exact'], ['version' => 7], [], ['task8_cross_connection']));
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
        $executions = 0;

        $response = app(EnforceApiV2RiskPolicy::class)->handle($request, function () use (&$executions): JsonResponse {
            $executions++;

            return new JsonResponse(['ok' => true]);
        });

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame(0, $executions);
        $this->assertNull(DB::table('api_action_plans')->where('plan_id', $plan['id'])->value('consumed_at'));
        DB::purge('task8_cross_connection');
    }

    public function test_domain_exception_rolls_back_reservation_and_retry_can_execute(): void
    {
        [$context, $operation] = $this->fixture();
        $this->registerOperation($operation);
        uss('api_high_risk_action_mode', 'planned');
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $request = $this->riskRequest($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);

        $failed = app(EnforceApiV2RiskPolicy::class)->handle($request, static fn () => throw new \RuntimeException('simulated worker crash'));
        $this->assertSame(503, $failed->getStatusCode());
        $this->assertNull(DB::table('api_action_plans')->where('plan_id', $plan['id'])->value('reservation_id'));

        $retry = app(EnforceApiV2RiskPolicy::class)->handle($request, fn () => new JsonResponse(['ok' => true]));
        $this->assertSame(200, $retry->getStatusCode());
        $this->assertNotNull(DB::table('api_action_plans')->where('plan_id', $plan['id'])->value('consumed_at'));
    }

    public function test_outer_commit_dispatches_plan_confirmation_event_exactly_once(): void
    {
        [$context, $operation] = $this->fixture();
        $this->registerOperation($operation);
        uss('api_high_risk_action_mode', 'planned');
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        DB::connection()->commit();
        Event::fake([ApiSecurityEventRecorded::class]);

        try {
            $request = $this->riskRequest($context, $operation, ['name' => 'exact'], ['version' => 7]);
            $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
            $response = app(EnforceApiV2RiskPolicy::class)->handle($request, fn () => new JsonResponse(['ok' => true]));

            $this->assertSame(200, $response->getStatusCode());
            Event::assertDispatchedTimes(ApiSecurityEventRecorded::class, 1);
            $this->assertSame(1, DB::table('api_security_events')->where('event_type', 'risk.plan.confirmed')->where('actor_id', $context->actorId())->count());
        } finally {
            DB::table('api_action_plans')->where('plan_id', $plan['id'])->delete();
            DB::table('api_security_events')->where('actor_id', $context->actorId())->delete();
            User::query()->whereKey($context->actorId())->delete();
            uss('api_high_risk_action_mode', 'direct');
            DB::connection()->beginTransaction();
        }
    }

    public function test_successful_retry_replays_idempotency_result_before_rechecking_confirmation(): void
    {
        [$context, $operation] = $this->fixture();
        $this->registerOperation($operation);
        uss('api_high_risk_action_mode', 'planned');
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $executions = 0;
        $idempotencyKey = 'risk-replay-'.uniqid();
        $run = function () use ($context, $operation, $plan, $idempotencyKey, &$executions): JsonResponse {
            $request = $this->riskRequest($context, $operation, ['name' => 'exact'], ['version' => 7]);
            $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
            $request->headers->set('Idempotency-Key', $idempotencyKey);

            return app(AssignApiV2RequestId::class)->handle($request, function ($request) use (&$executions) {
                return app(EnforceApiV2Idempotency::class)->handle(
                    $request,
                    function ($request) use (&$executions): JsonResponse {
                        return app(EnforceApiV2RiskPolicy::class)->handle($request, function () use (&$executions): JsonResponse {
                            $executions++;

                            return app(\Wncms\Api\V2\ApiResponseFactory::class)->success(['execution' => $executions], 'queued', 202);
                        });
                    },
                );
            });
        };

        $first = $run();
        $second = $run();

        $this->assertSame(202, $first->getStatusCode(), (string) $first->getContent());
        $this->assertSame(202, $second->getStatusCode());
        $this->assertSame('true', $second->headers->get('Idempotency-Replayed'));
        $this->assertSame(1, $executions);
    }

    public function test_reservation_prevents_double_enqueue_until_owner_releases_it(): void
    {
        [$context, $operation] = $this->fixture();
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        $first = app(ActionPlanService::class)->reserve($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]);

        $this->expectPlanCode('risk.confirmation_reused', fn () => app(ActionPlanService::class)->reserve($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]));

        CarbonImmutable::setTestNow('2026-08-14 00:00:31 UTC');
        $this->expectPlanCode('risk.confirmation_reused', fn () => app(ActionPlanService::class)->reserve($context, $operation, $plan['confirmation'], ['name' => 'exact'], ['version' => 7]));
        app(ActionPlanService::class)->releaseReservation($first);
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
            uss('api_high_risk_action_mode', 'direct');
            DB::connection()->beginTransaction();
        }
    }

    public function test_slow_transactional_enqueue_cannot_be_stolen_after_old_lease_window(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Process concurrency primitives are unavailable.');
        }

        [$context, $operation] = $this->fixture();
        $this->registerOperation($operation);
        uss('api_high_risk_action_mode', 'planned');
        $plan = app(ActionPlanService::class)->create($context, $operation, ['name' => 'exact'], ['version' => 7]);
        DB::connection()->commit();
        $markerFile = tempnam(sys_get_temp_dir(), 'wncms-plan-effects-');
        $readyFile = tempnam(sys_get_temp_dir(), 'wncms-plan-ready-');
        $resultFile = tempnam(sys_get_temp_dir(), 'wncms-plan-results-');
        $children = [];

        try {
            for ($index = 0; $index < 2; $index++) {
                $pid = pcntl_fork();
                if ($pid === 0) {
                    DB::disconnect();
                    if ($index === 1) {
                        $deadline = microtime(true) + 10;
                        while (filesize($readyFile) === 0 && microtime(true) < $deadline) {
                            clearstatcache(true, $readyFile);
                            usleep(10_000);
                        }
                        CarbonImmutable::setTestNow('2026-08-14 00:00:31 UTC');
                    }
                    $request = $this->riskRequest($context, $operation, ['name' => 'exact'], ['version' => 7], true);
                    $request->headers->set('X-WNCMS-Confirmation', $plan['confirmation']);
                    $response = app(EnforceApiV2RiskPolicy::class)->handle($request, function () use ($index, $markerFile, $readyFile): JsonResponse {
                        file_put_contents($markerFile, "enqueue-{$index}\n", FILE_APPEND | LOCK_EX);
                        if ($index === 0) {
                            file_put_contents($readyFile, 'ready', LOCK_EX);
                            CarbonImmutable::setTestNow('2026-08-14 00:00:31 UTC');
                            usleep(2_000_000);
                        }

                        return new JsonResponse(['queued' => true], 202);
                    });
                    file_put_contents($resultFile, $response->getStatusCode()."\n", FILE_APPEND | LOCK_EX);
                    exit(0);
                }
                $children[] = $pid;
            }

            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
                $this->assertTrue(pcntl_wifexited($status));
                $this->assertSame(0, pcntl_wexitstatus($status));
            }

            DB::disconnect();
            $markers = array_values(array_filter(explode("\n", (string) file_get_contents($markerFile))));
            $statuses = array_map('intval', array_values(array_filter(explode("\n", (string) file_get_contents($resultFile)))));
            $this->assertCount(1, $markers);
            $this->assertSame(1, count(array_filter($statuses, static fn (int $status): bool => $status >= 200 && $status < 300)));
            $this->assertNotNull(DB::table('api_action_plans')->where('plan_id', $plan['id'])->value('consumed_at'));
            $this->assertSame(1, DB::table('api_security_events')->where('event_type', 'risk.plan.confirmed')->where('actor_id', $context->actorId())->count());
        } finally {
            @unlink($markerFile);
            @unlink($readyFile);
            @unlink($resultFile);
            DB::table('api_action_plans')->where('plan_id', $plan['id'])->delete();
            DB::table('api_security_events')->where('actor_id', $context->actorId())->delete();
            User::query()->whereKey($context->actorId())->delete();
            uss('api_high_risk_action_mode', 'direct');
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
        $actor->givePermissionTo(Permission::findOrCreate('api_token_create', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
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
            securityRisk: 'high', actionPlanEligible: true, domainModelKeys: ['setting'],
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

    private function riskRequest(AuthenticationContext $context, ApiOperationContract $operation, array $input, array $targetState, bool $async = false, bool $transactionalOutbox = true): Request
    {
        $request = Request::create('/api/v2/backend/tokens', 'POST', [
            'input' => $input,
            'target_state' => $targetState,
        ]);
        $route = new Route(['POST'], '/api/v2/backend/tokens', fn () => null);
        $route->defaults('api_operation_id', $operation->id);
        $route->defaults('api_async_enqueue', $async);
        if ($async && $transactionalOutbox) {
            $route->defaults('api_transactional_outbox_model_keys', ['setting']);
        }
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $request->attributes->set(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE, $context);
        $request->attributes->set(ResolveApiV2RiskContext::ATTRIBUTE, new RiskContext($input, $targetState, [], ['setting']));

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

class Task8CrossConnectionModel extends \Wncms\Models\BaseModel
{
    public static $modelKey = 'task8_cross_connection';

    protected $connection = 'task8_cross_connection';

    protected $table = 'settings';
}
