<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;

class ApiV2TokenAuth
{
    /**
     * Create the API v2 token authentication middleware.
     *
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(protected ApiResponseFactory $responses)
    {
    }

    /**
     * Authenticate an API v2 request using the session or a personal access token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
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
            return $this->unauthorized('authentication.missing_token', 'Missing bearer token');
        }

        [$tokenId, $plainTextToken] = $this->parseToken($bearer);
        $hashedToken = hash('sha256', $plainTextToken);

        $query = DB::table('personal_access_tokens')->where('token', $hashedToken);
        if (!empty($tokenId)) {
            $query->where('id', (int) $tokenId);
        }

        $tokenRecord = $query->first();
        if (!$tokenRecord) {
            return $this->unauthorized('authentication.invalid_token', 'Invalid bearer token');
        }

        $userModel = wncms()->getModelClass('user');
        $user = $userModel::query()
            ->where('id', $tokenRecord->tokenable_id)
            ->where('id', '>', 0)
            ->first();

        if (!$user) {
            return $this->unauthorized('authentication.invalid_token', 'Token user not found');
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

    /**
     * Build a token authentication failure response.
     *
     * @param  string  $errorCode
     * @param  string  $message
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthorized(string $errorCode, string $message): Response
    {
        return $this->responses->failure(
            $errorCode,
            $message,
            Response::HTTP_UNAUTHORIZED
        );
    }
}
