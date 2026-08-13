<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Risk\ActionPlanException;
use Wncms\Api\V2\Risk\ActionPlanService;
use Wncms\Api\V2\Risk\OperationRiskContextResolver;
use Wncms\Api\V2\Risk\RiskContextException;
use Wncms\Api\V2\Risk\TargetOperationAuthorizer;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Services\Security\SecurityEventService;

final class ActionPlanController extends ApiV2Controller
{
    public function __construct(
        private ApiContractRegistry $contracts,
        private ActionPlanService $plans,
        private OperationRiskContextResolver $riskContexts,
        private SecurityEventService $events,
        private TargetOperationAuthorizer $authorizer,
    ) {}

    /**
     * Create one short-lived action plan for a formal eligible operation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operation' => ['required', 'string'],
            'input' => ['present', 'array'],
            'parameters' => ['sometimes', 'array'],
        ]);
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        $operation = $this->contracts->operation($validated['operation']);
        if (! $context instanceof AuthenticationContext || $operation === null) {
            return $this->responseFactory()->failure('risk.plan_invalid', 'Action plan request is not valid', Response::HTTP_CONFLICT);
        }

        try {
            if (! in_array($operation->sideEffectKind, ['database', 'transactional_outbox'], true) || ! $operation->idempotent) {
                return $this->responseFactory()->failure('risk.policy_unavailable', 'Operation does not have a transactional risk boundary', 503);
            }
            if ($operation->domainModelKeys === [] && $operation->transactionalOutboxModelKeys === []) {
                return $this->responseFactory()->failure('risk.policy_unavailable', 'Transactional domain boundary is required', 503);
            }
            $connections = array_values(array_unique(array_merge(
                $this->events->modelConnectionNames(array_merge(
                    ['api_action_plan', 'api_security_event'],
                    $operation->domainModelKeys,
                    $operation->transactionalOutboxModelKeys,
                )),
                $this->riskContexts->authorizationConnectionNames($context),
            )));
            if (count($connections) !== 1) {
                return $this->responseFactory()->failure('risk.policy_unavailable', 'Operation connections do not share one transaction', 503);
            }
            $planRequest = Request::createFrom($request);
            $planRequest->request->replace(['input' => $validated['input']]);
            $parameters = (array) ($validated['parameters'] ?? []);
            $plan = DB::connection($connections[0])->transaction(function () use ($planRequest, $context, $operation, $parameters, $connections): array {
                $riskContext = $this->riskContexts->resolveExecution($planRequest, $operation, $parameters);
                if (array_diff(array_unique($riskContext->connectionNames), $connections) !== []) {
                    throw new \RuntimeException('Target relationship connections must match.');
                }
                $this->authorizer->authorize($context, $operation);

                return $this->plans->createResolved($context, $operation, $riskContext);
            });
        } catch (RiskContextException $exception) {
            return $this->responseFactory()->failure($exception->errorCode, 'Action plan request is not valid', $exception->httpStatus);
        } catch (ActionPlanException $exception) {
            return $this->responseFactory()->failure($exception->errorCode, 'Action plan request is not valid', $exception->httpStatus);
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        return $this->ok($plan, 'action_plan_created', Response::HTTP_CREATED)
            ->header('Cache-Control', 'private, no-store');
    }
}
