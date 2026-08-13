<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AuthenticationContext;

final class RequireApiV2Ability
{
    /**
     * Create the API v2 ability guard.
     *
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(private ApiResponseFactory $responses)
    {
    }

    /**
     * Require the credential ability ceiling before permission or scope evaluation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $ability
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        if (! $context instanceof AuthenticationContext) {
            return $this->responses->failure(
                'authentication.invalid_token',
                'Authentication context is unavailable',
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (! $context->hasAbility($ability) && ! in_array('*', $context->abilities(), true)) {
            return $this->responses->failure(
                'authorization.ability_denied',
                'Credential ability denied',
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
