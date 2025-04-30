<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!gss('enable_api_access')) {
                return response()->json([
                    'status' => 403,
                    'message' => 'API access is disabled',
                ], 403);
            }

            return $next($request);
        });
    }
}
