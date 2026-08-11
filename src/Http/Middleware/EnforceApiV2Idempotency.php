<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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
        return $this->idempotency->handle($request, $next);
    }
}
