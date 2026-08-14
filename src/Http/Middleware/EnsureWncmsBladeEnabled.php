<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Services\Security\BladeAvailabilityService;

final class EnsureWncmsBladeEnabled
{
    public function __construct(private BladeAvailabilityService $availability) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->availability->state()->enabled) {
            return response('Not Found', Response::HTTP_NOT_FOUND, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return $next($request);
    }
}
