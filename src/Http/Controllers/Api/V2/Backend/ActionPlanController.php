<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Risk\ActionPlanException;
use Wncms\Api\V2\Risk\ActionPlanService;
use Wncms\Api\V2\Risk\OperationRiskContextResolver;
use Wncms\Api\V2\Risk\RiskContextException;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Http\Middleware\ApiV2TokenAuth;

final class ActionPlanController extends ApiV2Controller
{
    public function __construct(
        private ApiContractRegistry $contracts,
        private ActionPlanService $plans,
        private OperationRiskContextResolver $riskContexts,
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
            $planRequest = Request::createFrom($request);
            $planRequest->request->replace(['input' => $validated['input']]);
            $riskContext = $this->riskContexts->resolveRequest($planRequest, $operation, (array) ($validated['parameters'] ?? []));
            if ($operation->domainModelKeys === [] && $operation->transactionalOutboxModelKeys === []) {
                return $this->responseFactory()->failure('risk.policy_unavailable', 'Transactional domain boundary is required', 503);
            }
            $plan = $this->plans->createResolved($context, $operation, $riskContext);
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
