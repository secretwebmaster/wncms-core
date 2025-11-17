<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * Unified success response
     */
    protected function success($data = [], string $message = 'success', int $code = 200, array $extra = [])
    {
        return response()->json([
            'code' => $code,
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'extra' => $extra,
        ]);
    }

    /**
     * Unified fail response
     */
    protected function fail(string $message = 'fail', int $code = 400, array $extra = [], $data = [])
    {
        return response()->json([
            'code' => $code,
            'status' => 'fail',
            'message' => $message,
            'data' => $data,
            'extra' => $extra,
        ]);
    }

    /**
     * Check if API feature is enabled
     */
    protected function checkEnabled(string $key)
    {
        if (!gss($key)) {
            return $this->fail("API feature '{$key}' is disabled", 403);
        }
        return null;
    }

    /**
     * Auth handler
     */
    protected function checkAuthSetting(string $baseKey, Request $request)
    {
        $mode = gss($baseKey . '_should_auth'); // '' | simple | basic

        if (empty($mode)) {
            return ['user' => null];
        }

        if ($mode === 'simple') {
            $token = $request->input('api_token');

            if (empty($token)) {
                return ['error' => $this->fail('Missing api_token', 401)];
            }

            $userModel = wncms()->getModelClass('user');
            $user = $userModel::where('api_token', $token)->first();

            if (!$user) {
                return ['error' => $this->fail('Invalid api_token', 401)];
            }

            auth()->login($user);
            return ['user' => $user];
        }

        return ['error' => $this->fail("Unsupported auth mode: {$mode}", 403)];
    }
}
