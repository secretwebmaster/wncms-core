<?php

namespace Wncms\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiV2ResponseFinalizer
{
    /**
     * @var \WeakMap<\Symfony\Component\HttpFoundation\Response, string>
     */
    protected \WeakMap $trustedReplayRequestIds;

    /**
     * Create the API v2 response finalizer.
     *
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(protected ApiResponseFactory $responses)
    {
        $this->trustedReplayRequestIds = new \WeakMap();
    }

    /**
     * Assign a valid request ID from the incoming request or generate one.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    public function assignRequestId(Request $request): string
    {
        $requestId = trim((string) $request->headers->get('X-Request-ID', ''));
        if (! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set('wncms_api_v2_request_id', $requestId);

        return $requestId;
    }

    /**
     * Mark an internally reconstructed replay response with its original request ID.
     *
     * Header values alone never create this trust marker.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $requestId
     *
     * @return void
     */
    public function markTrustedReplay(Response $response, string $requestId): void
    {
        if (! Str::isUuid($requestId)) {
            throw new \UnexpectedValueException('Stored idempotency request ID is invalid');
        }

        $this->trustedReplayRequestIds[$response] = $requestId;
    }

    /**
     * Finalize response request-ID metadata with trusted replay preservation.
     *
     * Ordinary handler headers are overwritten. Only internally marked replay responses retain
     * the original response identity instead of the current retry request identity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function finalize(Request $request, Response $response): Response
    {
        $requestId = $this->trustedReplayRequestIds[$response]
            ?? $this->requestId($request);

        $request->attributes->set('wncms_api_v2_request_id', $requestId);
        $response->headers->set('X-Request-ID', $requestId);

        if ($response instanceof JsonResponse) {
            $payload = $response->getData();
            if (is_object($payload)) {
                $meta = is_object($payload->meta ?? null) ? $payload->meta : (object) [];
                $meta->request_id = $requestId;
                if (($payload->status ?? null) === 'fail' && ! isset($meta->error_code)) {
                    $meta->error_code = $this->responses->errorCodeForStatus($response->getStatusCode());
                }
                $payload->meta = $meta;
                $response->setData($payload);
            }
        }

        return $response;
    }

    /**
     * Resolve the request ID already assigned to the request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    protected function requestId(Request $request): string
    {
        $requestId = $request->attributes->get('wncms_api_v2_request_id');
        if (! is_string($requestId) || ! Str::isUuid($requestId)) {
            return $this->assignRequestId($request);
        }

        return $requestId;
    }
}
