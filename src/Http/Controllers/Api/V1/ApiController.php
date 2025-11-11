<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

    /**
     * Check whether a specific API feature is enabled.
     */
    protected function checkApiEnabled(string $key): ?JsonResponse
    {
        if (!gss($key)) {
            return response()->json([
                'status'  => 403,
                'message' => "API feature '{$key}' is disabled",
            ], 403);
        }
        return null;
    }

    /**
     * Authenticate via api_token and return the user model.
     * If invalid, it returns a JSON response immediately.
     */
    protected function authenticateByApiToken(Request $request): JsonResponse|\Illuminate\Contracts\Auth\Authenticatable
    {
        $token = $request->input('api_token');

        if (empty($token)) {
            return response()->json([
                'status'  => 'fail',
                'message' => 'Missing api_token',
            ], 401);
        }

        $userModel = wncms()->getModelClass('user');
        $user = $userModel::where('api_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status'  => 'fail',
                'message' => 'Invalid token',
            ], 401);
        }

        auth()->login($user);
        return $user;
    }
}
