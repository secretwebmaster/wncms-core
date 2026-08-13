<?php

namespace Wncms\Api\V2\Risk;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Auth\Api\V2\WebsiteScopeGuard;
use Wncms\Services\Security\SecurityEventService;

final class ActionPlanService
{
    public function __construct(
        private TokenHasher $hasher,
        private RiskPolicy $policy,
        private SecurityEventService $events,
        private TargetOperationAuthorizer $authorizer,
        private WebsiteScopeGuard $websiteScope,
        private OperationRiskContextResolver $riskContexts,
    ) {}

    /**
     * Resolve, authorize, lock, and create a request-bound plan in one transaction.
     *
     * @param  array<string, mixed>  $parameters
     * @return array{id: string, confirmation: string, operation: string, effective_risk: string, expires_at: string}
     */
    public function createForRequest(AuthenticationContext $context, ApiOperationContract $operation, Request $request, array $parameters = []): array
    {
        if (! $operation->actionPlanEligible) {
            throw new ActionPlanException('risk.plan_invalid', 409);
        }
        $connections = array_values(array_unique(array_merge(
            $this->events->modelConnectionNames(array_merge(
                ['api_action_plan', 'api_security_event'],
                $operation->domainModelKeys,
                $operation->transactionalOutboxModelKeys,
            )),
            $this->authorizer->connectionNames($context, $operation),
            $this->websiteScope->authorizationConnectionNames($context),
        )));
        if (count($connections) !== 1) {
            throw new \RuntimeException('Plan, authorization, domain, and event connections must match.');
        }

        return DB::connection($connections[0])->transaction(function () use ($context, $operation, $request, $parameters, $connections): array {
            $this->authorizer->authorizePreTarget($context, $operation);
            $riskContext = $this->riskContexts->resolveExecution($request, $operation, $parameters);
            if (array_diff(array_unique($riskContext->connectionNames), $connections) !== []) {
                throw new \RuntimeException('Target relationship connections must match.');
            }

            return $this->persistResolved($context, $operation, $riskContext);
        });
    }

    /** @return array{id: string, confirmation: string, operation: string, effective_risk: string, expires_at: string} */
    public function create(AuthenticationContext $context, ApiOperationContract $operation, array $input, array $targetState): array
    {
        return $this->createResolved($context, $operation, new RiskContext($input, $targetState, []));
    }

    /**
     * Create a plan from canonical input and server-owned state.
     */
    public function createResolved(AuthenticationContext $context, ApiOperationContract $operation, RiskContext $riskContext): array
    {
        if (! $operation->actionPlanEligible) {
            throw new ActionPlanException('risk.plan_invalid', 409);
        }

        $connections = array_values(array_unique(array_merge(
            $this->events->modelConnectionNames(array_merge(
                ['api_action_plan', 'api_security_event'],
                $operation->domainModelKeys,
                $operation->transactionalOutboxModelKeys,
            )),
            $this->authorizer->connectionNames($context, $operation),
            $this->websiteScope->authorizationConnectionNames($context),
            $riskContext->connectionNames,
        )));
        if (count($connections) !== 1) {
            throw new \RuntimeException('Plan, authorization, domain, and event connections must match.');
        }

        return DB::connection($connections[0])->transaction(function () use ($context, $operation, $riskContext): array {
            $this->authorizer->authorizePreTarget($context, $operation);
            $this->websiteScope->assertResolvedScope($context, $riskContext, true);

            return $this->persistResolved($context, $operation, $riskContext);
        });
    }

    /**
     * Persist a plan after the public service boundary has authorized its snapshot.
     *
     * @return array{id: string, confirmation: string, operation: string, effective_risk: string, expires_at: string}
     */
    private function persistResolved(AuthenticationContext $context, ApiOperationContract $operation, RiskContext $riskContext): array
    {

        $material = $this->hasher->issue('wncms_cp');
        $expiresAt = CarbonImmutable::now('UTC')->addSeconds(AuthSecurityConfig::fromRuntime()->actionPlanLifetimeSeconds());
        $risk = $this->policy->effective($operation, $riskContext->normalizedInput, $riskContext->environment);
        $bindings = $this->bindings($context, $operation, $riskContext, $risk);
        $planModel = wncms()->getModelClass('api_action_plan');

        $this->events->withinTransaction(function () use ($planModel, $material, $bindings, $expiresAt): void {
            $planModel::create(array_merge($bindings, [
                'plan_id' => $material['public_id'],
                'confirmation_hash' => $material['hash'],
                'expires_at' => $expiresAt,
            ]));
        }, $this->event('risk.plan.created', $context, $operation, $risk), null, $this->events->modelConnectionNames(['api_action_plan']));

        return [
            'id' => $material['public_id'],
            'confirmation' => $material['plain_text'],
            'operation' => $operation->id,
            'effective_risk' => $risk,
            'expires_at' => $expiresAt->toAtomString(),
        ];
    }

    /**
     * Validate and lock a confirmation reference before stale-target resolution.
     *
     * This deliberately validates only immutable pre-target bindings. The complete
     * input, target, environment, scope, and permission snapshot is checked during
     * reservation after fresh target resolution.
     */
    public function assertUsableReference(AuthenticationContext $context, ApiOperationContract $operation, string $confirmation): void
    {
        $publicId = $this->publicId($confirmation);
        if ($publicId === null) {
            throw new ActionPlanException('risk.plan_invalid', 409);
        }
        $planModel = wncms()->getModelClass('api_action_plan');
        $plan = $planModel::query()->where('plan_id', $publicId)->lockForUpdate()->first();
        if ($plan === null || ! $this->hasher->matches($confirmation, (string) $plan->confirmation_hash)) {
            throw new ActionPlanException('risk.plan_invalid', 409);
        }
        if ($plan->consumed_at !== null || $plan->reservation_id !== null) {
            throw new ActionPlanException('risk.confirmation_reused', 409);
        }
        if (! $plan->expires_at->isFuture()) {
            throw new ActionPlanException('risk.plan_expired', 409);
        }
        if (
            (string) $plan->actor_type !== $context->actor()::class
            || (string) $plan->actor_id !== (string) $context->actorId()
            || (string) $plan->credential_type !== $context->credentialType()
            || (string) $plan->credential_id !== (string) ($context->credentialPublicId() ?? '')
            || (string) ($plan->session_id ?? '') !== (string) ($context->sessionPublicId() ?? '')
            || (string) $plan->operation_id !== $operation->id
        ) {
            throw new ActionPlanException('risk.plan_stale', 409);
        }
    }

    public function consume(AuthenticationContext $context, ApiOperationContract $operation, string $confirmation, array $input, array $targetState): void
    {
        $this->consumeResolved($context, $operation, $confirmation, new RiskContext($input, $targetState, []));
    }

    /**
     * Consume one confirmation against canonical server-resolved context.
     */
    public function consumeResolved(AuthenticationContext $context, ApiOperationContract $operation, string $confirmation, RiskContext $riskContext): void
    {
        $publicId = $this->publicId($confirmation);
        if ($publicId === null) {
            throw new ActionPlanException('risk.plan_invalid', 409);
        }

        $planModel = wncms()->getModelClass('api_action_plan');
        try {
            $this->events->withinTransaction(function () use ($planModel, $publicId, $confirmation, $context, $operation, $riskContext): void {
                $plan = $planModel::query()->where('plan_id', $publicId)->lockForUpdate()->first();
                if ($plan === null || ! $this->hasher->matches($confirmation, (string) $plan->confirmation_hash)) {
                    throw new ActionPlanException('risk.plan_invalid', 409);
                }
                if ($plan->consumed_at !== null) {
                    throw new ActionPlanException('risk.confirmation_reused', 409);
                }
                if (! $plan->expires_at->isFuture()) {
                    throw new ActionPlanException('risk.plan_expired', 409);
                }

                $risk = $this->policy->effective($operation, $riskContext->normalizedInput, $riskContext->environment);
                $expected = $this->bindings($context, $operation, $riskContext, $risk);
                foreach ($expected as $key => $value) {
                    $actual = $key === 'website_ids' ? (array) $plan->{$key} : $plan->{$key};
                    if ($actual !== $value) {
                        throw new ActionPlanException('risk.plan_stale', 409);
                    }
                }

                $updated = $planModel::query()->whereKey($plan->getKey())->whereNull('consumed_at')->update([
                    'consumed_at' => CarbonImmutable::now('UTC'),
                    'updated_at' => CarbonImmutable::now('UTC'),
                ]);
                if ($updated !== 1) {
                    throw new ActionPlanException('risk.confirmation_reused', 409);
                }
            }, $this->event('risk.plan.confirmed', $context, $operation, $this->policy->effective($operation, $riskContext->normalizedInput, $riskContext->environment)), null, $this->events->modelConnectionNames(['api_action_plan']));
        } catch (ActionPlanException $exception) {
            if (in_array($exception->errorCode, ['risk.plan_stale', 'risk.confirmation_reused'], true)) {
                $eventType = $exception->errorCode === 'risk.plan_stale' ? 'risk.plan.stale' : 'risk.confirmation.reused';
                $this->events->withinTransaction(
                    static fn (): null => null,
                    $this->event($eventType, $context, $operation, $this->policy->effective($operation, $riskContext->normalizedInput, $riskContext->environment), 'denied'),
                    null,
                    $this->events->modelConnectionNames(['api_action_plan']),
                );
            }

            throw $exception;
        }
    }

    /**
     * Atomically reserve a valid plan before an asynchronous enqueue attempt.
     *
     * The reservation prevents concurrent enqueue attempts without consuming the
     * confirmation until the downstream enqueue reports success.
     */
    public function reserve(AuthenticationContext $context, ApiOperationContract $operation, string $confirmation, array $input, array $targetState): string
    {
        return $this->reserveResolved($context, $operation, $confirmation, new RiskContext($input, $targetState, []));
    }

    /**
     * Atomically reserve one plan against canonical server-resolved context.
     */
    public function reserveResolved(AuthenticationContext $context, ApiOperationContract $operation, string $confirmation, RiskContext $riskContext): string
    {
        $publicId = $this->publicId($confirmation);
        if ($publicId === null) {
            throw new ActionPlanException('risk.plan_invalid', 409);
        }

        $planModel = wncms()->getModelClass('api_action_plan');
        $connections = $this->events->modelConnectionNames(['api_action_plan', 'api_security_event']);
        if (count($connections) !== 1) {
            throw new \RuntimeException('Security mutation and event connections must match.');
        }
        $reservationId = (string) Str::uuid();

        $plan = $planModel::query()->where('plan_id', $publicId)->lockForUpdate()->first();
        try {
            $this->assertUsable($plan, $confirmation, $context, $operation, $riskContext);
        } catch (ActionPlanException $exception) {
            $this->recordDenial($exception, $context, $operation, $riskContext);
            throw $exception;
        }
        $now = CarbonImmutable::now('UTC');
        $updated = $planModel::query()
            ->whereKey($plan->getKey())
            ->whereNull('consumed_at')
            ->whereNull('reservation_id')
            ->update([
                'reservation_id' => $reservationId,
                'reserved_at' => $now,
                'updated_at' => $now,
            ]);
        if ($updated !== 1) {
            $exception = new ActionPlanException('risk.confirmation_reused', 409);
            $this->recordDenial($exception, $context, $operation, $riskContext);
            throw $exception;
        }

        return $reservationId;
    }

    /**
     * Release an exact reservation after an enqueue attempt fails.
     */
    public function releaseReservation(string $reservationId): void
    {
        $planModel = wncms()->getModelClass('api_action_plan');
        $connections = $this->events->modelConnectionNames(['api_action_plan', 'api_security_event']);
        if (count($connections) !== 1) {
            throw new \RuntimeException('Security mutation and event connections must match.');
        }

        $planModel::query()->where('reservation_id', $reservationId)->whereNull('consumed_at')->update([
            'reservation_id' => null,
            'reserved_at' => null,
            'updated_at' => CarbonImmutable::now('UTC'),
        ]);
    }

    /**
     * Consume an exact reservation with its mandatory confirmation event.
     */
    public function confirmReservation(string $reservationId, AuthenticationContext $context, ApiOperationContract $operation, string $effectiveRisk): void
    {
        $planModel = wncms()->getModelClass('api_action_plan');
        $this->events->withinTransaction(function () use ($planModel, $reservationId): void {
            $updated = $planModel::query()
                ->where('reservation_id', $reservationId)
                ->whereNull('consumed_at')
                ->update([
                    'consumed_at' => CarbonImmutable::now('UTC'),
                    'reservation_id' => null,
                    'reserved_at' => null,
                    'updated_at' => CarbonImmutable::now('UTC'),
                ]);
            if ($updated !== 1) {
                throw new ActionPlanException('risk.confirmation_reused', 409);
            }
        }, $this->event('risk.plan.confirmed', $context, $operation, $effectiveRisk), null, $this->events->modelConnectionNames(['api_action_plan']));
    }

    /** @return array<string, mixed> */
    private function bindings(AuthenticationContext $context, ApiOperationContract $operation, RiskContext $riskContext, string $risk): array
    {
        $websiteIds = array_values(array_unique(array_map('intval', $context->websiteIds())));
        sort($websiteIds);

        return [
            'actor_type' => $context->actor()::class,
            'actor_id' => (string) $context->actorId(),
            'credential_type' => $context->credentialType(),
            'credential_id' => (string) ($context->credentialPublicId() ?? ''),
            'session_id' => $context->sessionPublicId(),
            'operation_id' => $operation->id,
            'input_hash' => $this->fingerprint($riskContext->normalizedInput),
            'target_hash' => $this->fingerprint($riskContext->targetState),
            'environment_hash' => $this->fingerprint($riskContext->environment),
            'scope_hash' => $this->fingerprint($websiteIds),
            'website_ids' => $websiteIds,
            'authorization_hash' => $this->fingerprint([
                'ability' => $operation->ability,
                'ability_granted' => $operation->ability === null || $context->hasAbility($operation->ability) || $context->hasAbility('*'),
                'permission' => $operation->permission,
                'permission_granted' => $operation->permission === null
                    || $this->authorizer->permissionGranted($context, $operation->permission),
            ]),
            'effective_risk' => $risk,
        ];
    }

    private function fingerprint(array $value): string
    {
        return hash('sha256', json_encode($this->normalize($value), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function normalize(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->normalize($item);
            }
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function event(string $type, AuthenticationContext $context, ApiOperationContract $operation, string $risk, string $outcome = 'succeeded'): array
    {
        return [
            'type' => $type,
            'severity' => $risk === 'critical' ? 'critical' : 'warning',
            'outcome' => $outcome,
            'context' => [
                'surface' => 'api_v2',
                'actor_type' => $context->actor()::class,
                'actor_id' => $context->actorId(),
                'credential_type' => $context->credentialType(),
                'credential_id' => $context->credentialPublicId(),
                'session_id' => $context->sessionPublicId(),
                'website_ids' => $context->websiteIds(),
                'context' => ['operation' => $operation->id],
            ],
        ];
    }

    private function publicId(string $confirmation): ?string
    {
        return preg_match('/^wncms_cp_([0-9A-HJKMNP-TV-Z]{26})\.[A-Za-z0-9_-]+$/D', $confirmation, $matches) === 1
            ? $matches[1]
            : null;
    }

    private function assertUsable(mixed $plan, string $confirmation, AuthenticationContext $context, ApiOperationContract $operation, RiskContext $riskContext): void
    {
        if ($plan === null || ! $this->hasher->matches($confirmation, (string) $plan->confirmation_hash)) {
            throw new ActionPlanException('risk.plan_invalid', 409);
        }
        if ($plan->consumed_at !== null) {
            throw new ActionPlanException('risk.confirmation_reused', 409);
        }
        if (! $plan->expires_at->isFuture()) {
            throw new ActionPlanException('risk.plan_expired', 409);
        }

        $risk = $this->policy->effective($operation, $riskContext->normalizedInput, $riskContext->environment);
        foreach ($this->bindings($context, $operation, $riskContext, $risk) as $key => $value) {
            $actual = $key === 'website_ids' ? (array) $plan->{$key} : $plan->{$key};
            if ($actual !== $value) {
                throw new ActionPlanException('risk.plan_stale', 409);
            }
        }
    }

    private function recordDenial(ActionPlanException $exception, AuthenticationContext $context, ApiOperationContract $operation, RiskContext $riskContext): void
    {
        if (! in_array($exception->errorCode, ['risk.plan_stale', 'risk.confirmation_reused'], true)) {
            return;
        }

        $eventType = $exception->errorCode === 'risk.plan_stale' ? 'risk.plan.stale' : 'risk.confirmation.reused';
        $this->events->withinTransaction(
            static fn (): null => null,
            $this->event($eventType, $context, $operation, $this->policy->effective($operation, $riskContext->normalizedInput, $riskContext->environment), 'denied'),
            null,
            $this->events->modelConnectionNames(['api_action_plan']),
        );
    }
}
