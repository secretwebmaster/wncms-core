<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiV2HasWebsite
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = (string) optional($request->route())->getName();

        if (str_starts_with($routeName, 'api.v2.backend.websites.')) {
            return $next($request);
        }

        if (!wncms()->website()->get()) {
            return response()->json([
                'code' => Response::HTTP_CONFLICT,
                'status' => 'fail',
                'message' => 'Website context is not available',
                'data' => null,
                'meta' => [],
                'errors' => [],
            ], Response::HTTP_CONFLICT);
        }

        return $next($request);
    }
}

