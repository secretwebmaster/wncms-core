<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\Risk\ActionPlanException;
use Wncms\Api\V2\Risk\ActionPlanService;
use Wncms\Api\V2\Risk\OperationRiskContextResolver;
use Wncms\Api\V2\Risk\RiskContext;
use Wncms\Api\V2\Risk\RiskPolicy;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\StepUpException;
use Wncms\Auth\Api\V2\StepUpService;
use Wncms\Services\Security\SecurityEventService;

final class EnforceApiV2RiskPolicy
{
    public function __construct(
        private ApiContractRegistry $contracts,
        private RiskPolicy $policy,
        private ActionPlanService $plans,
        private StepUpService $stepUp,
        private ApiResponseFactory $responses,
        private SecurityEventService $events,
        private OperationRiskContextResolver $riskContexts,
    ) {}

    /**
     * Enforce credential type, step-up, and direct/planned risk policy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        $route = $request->route();
        $operationId = $route instanceof Route ? (string) ($route->defaults['api_operation_id'] ?? '') : '';
        $operation = $this->contracts->operation($operationId);
        if ($operation === null && $route instanceof Route && $route->getName() !== null) {
            foreach ($this->contracts->operations() as $candidate) {
                if ($candidate->routeName === $route->getName()) {
                    $operation = $candidate;
                    break;
                }
            }
        }

        if (! $context instanceof AuthenticationContext || $operation === null) {
            return $this->responses->failure('risk.policy_unavailable', 'Risk policy is unavailable', 503);
        }
        if (! in_array($context->credentialType(), $operation->acceptedCredentialTypes, true)) {
            return $this->responses->failure('risk.credential_type_denied', 'Credential type is not allowed', 403);
        }

        $riskContext = $request->attributes->get(ResolveApiV2RiskContext::ATTRIBUTE);
        if (! $riskContext instanceof RiskContext) {
            return $this->responses->failure('risk.policy_unavailable', 'Risk policy is unavailable', 503);
        }
        $risk = $this->policy->effective($operation, $riskContext->normalizedInput, $riskContext->environment);
        if ($risk === 'critical' && $context->credentialType() === ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN) {
            return $this->responses->failure('risk.credential_type_denied', 'Credential type is not allowed', 403);
        }

        $requiresPlan = $this->policy->requiresPlan($operation, $risk, AuthSecurityConfig::fromRuntime()->highRiskMode());
        if (
            in_array($risk, ['high', 'critical'], true)
            && ($operation->canonicalizer !== 'schema' || $operation->targetResolver !== 'none')
            && ! in_array($operation->sideEffectKind, ['database', 'transactional_outbox'], true)
        ) {
            return $this->responses->failure('risk.policy_unavailable', 'Operation does not have a transactional risk boundary', 503);
        }
        $proof = trim((string) $request->headers->get('X-WNCMS-Step-Up', ''));
        if ($operation->requiresStepUp && $proof === '') {
            return $this->responses->failure('risk.step_up_required', 'Step-up proof is required', 428);
        }
        $confirmation = trim((string) $request->headers->get('X-WNCMS-Confirmation', ''));
        if ($requiresPlan && $confirmation === '') {
            return $this->responses->failure('risk.plan_required', 'Action plan confirmation is required', 428);
        }

        if (! $operation->requiresStepUp && ! $requiresPlan) {
            return $next($request);
        }

        $route = $request->route();
        $async = $route instanceof Route && (bool) ($route->defaults['api_async_enqueue'] ?? false);
        $formalDescriptor = $operation->canonicalizer !== 'schema'
            || $operation->targetResolver !== 'none'
            || $this->riskContexts->hasResolver($operation->id);
        $outboxModelKeys = $formalDescriptor
            ? $operation->transactionalOutboxModelKeys
            : ($route instanceof Route ? (array) ($route->defaults['api_transactional_outbox_model_keys'] ?? []) : []);
        $domainModelKeys = $formalDescriptor ? $operation->domainModelKeys : $riskContext->modelKeys;
        if ($async && $outboxModelKeys === []) {
            return $this->responses->failure('risk.policy_unavailable', 'Transactional outbox is required', 503);
        }
        if ($requiresPlan && $domainModelKeys === [] && $outboxModelKeys === []) {
            return $this->responses->failure('risk.policy_unavailable', 'Transactional domain boundary is required', 503);
        }
        $modelKeys = array_values(array_unique(array_merge(
            ['api_security_event'],
            $domainModelKeys,
            $outboxModelKeys,
            $operation->requiresStepUp ? ['api_step_up_proof', 'api_session'] : [],
            $requiresPlan ? ['api_action_plan'] : [],
        )));

        try {
            $connections = $this->events->modelConnectionNames($modelKeys);
            if (count($connections) !== 1) {
                throw new \RuntimeException('Domain, outbox, security mutation, and event connections must match.');
            }

            return DB::connection($connections[0])->transaction(function () use ($request, $next, $context, $operation, $requiresPlan, $proof, $confirmation, $formalDescriptor, $riskContext): Response {
                $route = $request->route();
                $parameters = $route instanceof Route ? $route->parameters() : [];
                $riskContext = $formalDescriptor
                    ? $this->riskContexts->resolveExecution($request, $operation, $parameters)
                    : $riskContext;
                $risk = $this->policy->effective($operation, $riskContext->normalizedInput, $riskContext->environment);
                $stepReservation = null;
                if ($operation->requiresStepUp) {
                    $selectedPurpose = count($operation->stepUpPurposes) === 1
                        ? (string) $operation->stepUpPurposes[0]
                        : trim((string) $request->headers->get('X-WNCMS-Step-Up-Purpose', ''));
                    $stepReservation = $this->stepUp->reserveAny($context, $proof, $operation->stepUpPurposes, $selectedPurpose);
                }
                $planReservation = $requiresPlan
                    ? $this->plans->reserveResolved($context, $operation, $confirmation, $riskContext)
                    : null;

                $response = $next($request);
                if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                    throw new RiskExecutionRollback($response);
                }
                if ($planReservation !== null) {
                    $this->plans->confirmReservation($planReservation, $context, $operation, $risk);
                }
                if ($stepReservation !== null) {
                    $this->stepUp->confirmReservation($stepReservation, $context);
                }

                return $response;
            });
        } catch (RiskExecutionRollback $rollback) {
            return $rollback->response;
        } catch (ActionPlanException|StepUpException $exception) {
            return $this->responses->failure($exception->errorCode, 'Security confirmation is not valid', $exception->httpStatus);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->responses->failure('security.audit_unavailable', 'Security audit is unavailable', 503);
        }
    }
}

final class RiskExecutionRollback extends \RuntimeException
{
    public function __construct(public readonly Response $response)
    {
        parent::__construct('Risk execution rolled back.');
    }
}
