<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;

class AssignApiV2RequestId
{
    /**
     * Create the API v2 request ID middleware.
     *
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(protected ApiResponseFactory $responses)
    {
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
        $requestId = trim((string) $request->headers->get('X-Request-ID', ''));
        if (! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set('wncms_api_v2_request_id', $requestId);

        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $response = $this->responses->fromThrowable($exception);
        }

        if ($response instanceof JsonResponse && $response->exception instanceof \Throwable) {
            $response = $this->responses->fromReportedThrowable($response->exception);
        }

        $response->headers->set('X-Request-ID', $requestId);

        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);
            if (is_array($payload)) {
                $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
                $meta['request_id'] = $requestId;
                if (($payload['status'] ?? null) === 'fail' && ! isset($meta['error_code'])) {
                    $meta['error_code'] = $this->responses->errorCodeForStatus($response->getStatusCode());
                }
                $payload['meta'] = $meta;
                $response->setData($payload);
            }
        }

        return $response;
    }
}
