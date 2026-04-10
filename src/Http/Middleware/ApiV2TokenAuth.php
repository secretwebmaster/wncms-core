<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApiV2TokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            return $next($request);
        }

        $bearer = trim((string) $request->bearerToken());
        if ($bearer === '') {
            $bearer = trim((string) $request->input('api_token', ''));
        }

        if ($bearer === '') {
            return $this->unauthorized('Missing bearer token');
        }

        [$tokenId, $plainTextToken] = $this->parseToken($bearer);
        $hashedToken = hash('sha256', $plainTextToken);

        $query = DB::table('personal_access_tokens')->where('token', $hashedToken);
        if (!empty($tokenId)) {
            $query->where('id', (int) $tokenId);
        }

        $tokenRecord = $query->first();
        if (!$tokenRecord) {
            return $this->unauthorized('Invalid bearer token');
        }

        $userModel = wncms()->getModelClass('user');
        $user = $userModel::query()
            ->where('id', $tokenRecord->tokenable_id)
            ->where('id', '>', 0)
            ->first();

        if (!$user) {
            return $this->unauthorized('Token user not found');
        }

        auth()->setUser($user);
        $request->attributes->set('api_v2_token_id', (int) $tokenRecord->id);

        return $next($request);
    }

    protected function parseToken(string $token): array
    {
        if (str_contains($token, '|')) {
            [$id, $plain] = explode('|', $token, 2);
            return [is_numeric($id) ? (int) $id : null, $plain];
        }

        return [null, $token];
    }

    protected function unauthorized(string $message): Response
    {
        return response()->json([
            'code' => Response::HTTP_UNAUTHORIZED,
            'status' => 'fail',
            'message' => $message,
            'data' => null,
            'meta' => [],
            'errors' => [],
        ], Response::HTTP_UNAUTHORIZED);
    }
}

