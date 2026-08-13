<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Risk\ActionPlanException;
use Wncms\Api\V2\Risk\ActionPlanService;
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

        $input = (array) $request->input('input', $request->all());
        $targetState = (array) $request->input('target_state', []);
        $risk = $this->policy->effective($operation, $input, []);
        if ($risk === 'critical' && $context->credentialType() !== ApiCredential::TYPE_INTERACTIVE_ACCESS) {
            return $this->responses->failure('risk.credential_type_denied', 'Credential type is not allowed', 403);
        }

        $stepReservation = null;
        if ($operation->requiresStepUp) {
            $proof = trim((string) $request->headers->get('X-WNCMS-Step-Up', ''));
            if ($proof === '') {
                return $this->responses->failure('risk.step_up_required', 'Step-up proof is required', 428);
            }

            try {
                $purpose = (string) ($operation->stepUpPurposes[0] ?? '');
                $stepReservation = $this->stepUp->reserve($context, $proof, $purpose);
            } catch (StepUpException $exception) {
                return $this->responses->failure($exception->errorCode, 'Step-up proof is not valid', $exception->httpStatus);
            } catch (\Throwable $exception) {
                report($exception);

                return $this->responses->failure('security.audit_unavailable', 'Security audit is unavailable', 503);
            }
        }

        if (! $this->policy->requiresPlan($operation, $risk, AuthSecurityConfig::fromRuntime()->highRiskMode())) {
            if ($stepReservation === null) {
                return $next($request);
            }

            return $this->executeReserved($request, $next, $context, $operation, $risk, $stepReservation, null);
        }

        $confirmation = trim((string) $request->headers->get('X-WNCMS-Confirmation', ''));
        if ($confirmation === '') {
            if ($stepReservation !== null) {
                $this->stepUp->releaseReservation($stepReservation);
            }

            return $this->responses->failure('risk.plan_required', 'Action plan confirmation is required', 428);
        }

        try {
            $planReservation = $this->plans->reserve($context, $operation, $confirmation, $input, $targetState);
        } catch (ActionPlanException $exception) {
            if ($stepReservation !== null) {
                $this->stepUp->releaseReservation($stepReservation);
            }

            return $this->responses->failure($exception->errorCode, 'Action plan confirmation is not valid', $exception->httpStatus);
        } catch (\Throwable $exception) {
            if ($stepReservation !== null) {
                $this->stepUp->releaseReservation($stepReservation);
            }
            report($exception);

            return $this->responses->failure('security.audit_unavailable', 'Security audit is unavailable', 503);
        }

        return $this->executeReserved($request, $next, $context, $operation, $risk, $stepReservation, $planReservation);
    }

    private function executeReserved(Request $request, Closure $next, AuthenticationContext $context, ApiOperationContract $operation, string $risk, ?string $stepReservation, ?string $planReservation): Response
    {
        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $this->releaseReservations($stepReservation, $planReservation);
            throw $exception;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            try {
                $this->releaseReservations($stepReservation, $planReservation);
            } catch (\Throwable $exception) {
                report($exception);

                return $this->responses->failure('security.audit_unavailable', 'Security audit is unavailable', 503);
            }

            return $response;
        }

        try {
            $modelKeys = ['api_security_event'];
            if ($stepReservation !== null) {
                array_push($modelKeys, 'api_step_up_proof', 'api_session');
            }
            if ($planReservation !== null) {
                $modelKeys[] = 'api_action_plan';
            }
            $connections = $this->events->modelConnectionNames($modelKeys);
            if (count($connections) !== 1) {
                throw new \RuntimeException('Security mutation and event connections must match.');
            }

            DB::connection($connections[0])->transaction(function () use ($stepReservation, $planReservation, $context, $operation, $risk): void {
                if ($planReservation !== null) {
                    $this->plans->confirmReservation($planReservation, $context, $operation, $risk);
                }
                if ($stepReservation !== null) {
                    $this->stepUp->confirmReservation($stepReservation, $context);
                }
            });
        } catch (ActionPlanException|StepUpException $exception) {
            return $this->responses->failure($exception->errorCode, 'Security confirmation is not valid', $exception->httpStatus);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->responses->failure('security.audit_unavailable', 'Security audit is unavailable', 503);
        }

        return $response;
    }

    private function releaseReservations(?string $stepReservation, ?string $planReservation): void
    {
        if ($planReservation !== null) {
            $this->plans->releaseReservation($planReservation);
        }
        if ($stepReservation !== null) {
            $this->stepUp->releaseReservation($stepReservation);
        }
    }
}
