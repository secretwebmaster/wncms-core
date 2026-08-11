<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\ApiV2ResponseFinalizer;

class AssignApiV2RequestId
{
    /**
     * Create the API v2 request ID middleware.
     *
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     * @param  \Wncms\Api\V2\ApiV2ResponseFinalizer  $finalizer
     */
    public function __construct(
        protected ApiResponseFactory $responses,
        protected ApiV2ResponseFinalizer $finalizer
    ) {
    }

    /**
     * Assign a valid request ID and finalize the downstream API response.
     *
     * Throwable responses are normalized here so failures outside controllers retain the API envelope.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->finalizer->assignRequestId($request);

        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $response = $this->responses->fromThrowable($exception);
        }

        if ($response instanceof JsonResponse && $response->exception instanceof \Throwable) {
            $response = $this->responses->fromReportedThrowable($response->exception);
        }

        return $this->finalizer->finalize($request, $response);
    }
}
