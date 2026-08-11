<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiV2ResponseFinalizer;
use Wncms\Api\V2\IdempotencyService;

class EnforceApiV2Idempotency
{
    /**
     * Create the API v2 idempotency middleware.
     *
     * @param  \Wncms\Api\V2\IdempotencyService  $idempotency
     */
    public function __construct(protected IdempotencyService $idempotency)
    {
    }

    /**
     * Enforce replay-safe execution for an opted-in API v2 mutation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->attributes->get(ApiV2ResponseFinalizer::REQUEST_ID_ATTRIBUTE);
        if (! is_string($requestId) || ! Str::isUuid($requestId)) {
            throw new \UnexpectedValueException('API v2 request ID is unavailable');
        }

        return $this->idempotency->handle($request, $next, $requestId);
    }
}
