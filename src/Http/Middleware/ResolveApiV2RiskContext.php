<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\Risk\OperationRiskContextResolver;
use Wncms\Api\V2\Risk\RiskContextException;

final class ResolveApiV2RiskContext
{
    public const ATTRIBUTE = 'wncms_api_v2_risk_context';

    /**
     * Create the risk-context middleware.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $contracts
     * @param  \Wncms\Api\V2\Risk\OperationRiskContextResolver  $resolver
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     * @return void
     */
    public function __construct(
        private ApiContractRegistry $contracts,
        private OperationRiskContextResolver $resolver,
        private ApiResponseFactory $responses,
    ) {}

    /**
     * Resolve validated canonical risk context before idempotency and execution.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $operation = $route instanceof Route ? $this->contracts->operation((string) ($route->defaults['api_operation_id'] ?? '')) : null;
        if ($operation === null && $route instanceof Route) {
            $operation = collect($this->contracts->operations())->first(fn ($candidate) => $candidate->routeName === $route->getName());
        }
        if ($operation === null) {
            return $this->responses->failure('risk.policy_unavailable', 'Risk policy is unavailable', 503);
        }

        try {
            $request->attributes->set(self::ATTRIBUTE, $this->resolver->resolveRequest($request, $operation, $route->parameters()));
        } catch (RiskContextException $exception) {
            return $this->responses->failure($exception->errorCode, 'Risk context is not valid', $exception->httpStatus);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->responses->failure('risk.policy_unavailable', 'Risk policy is unavailable', 503);
        }

        return $next($request);
    }
}
