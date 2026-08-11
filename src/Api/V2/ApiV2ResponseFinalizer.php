<?php

namespace Wncms\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiV2ResponseFinalizer
{
    public const REQUEST_ID_ATTRIBUTE = 'wncms_api_v2_request_id';

    /**
     * Create the API v2 response finalizer.
     *
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     * @param  \Wncms\Api\V2\ReplayResponseTrust  $replayTrust
     */
    public function __construct(
        protected ApiResponseFactory $responses,
        private ReplayResponseTrust $replayTrust
    ) {
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

        $request->attributes->set(self::REQUEST_ID_ATTRIBUTE, $requestId);

        return $requestId;
    }

    /**
     * Finalize response request-ID metadata with trusted replay preservation.
     *
     * Ordinary handler headers are overwritten with the pre-handler request ID. A reconstructed
     * replay response consumes its private original identity exactly once.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $requestId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function finalize(Response $response, string $requestId): Response
    {
        $requestId = $this->replayTrust->consume($response) ?? $requestId;
        if (! Str::isUuid($requestId)) {
            throw new \UnexpectedValueException('API v2 request ID is invalid');
        }

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
}
