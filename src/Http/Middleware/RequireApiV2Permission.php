<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AuthenticationContext;

final class RequireApiV2Permission
{
    /**
     * Create the API v2 permission guard.
     *
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(private ApiResponseFactory $responses)
    {
    }

    /**
     * Require the actor's current WNCMS permission after ability evaluation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        if (! $context instanceof AuthenticationContext) {
            return $this->responses->failure(
                'authentication.invalid_token',
                'Authentication context is unavailable',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $actor = $context->actor();
        if (! method_exists($actor, 'checkPermissionTo') || ! $actor->checkPermissionTo($permission)) {
            return $this->responses->failure(
                'authorization.permission_denied',
                'Permission denied',
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
