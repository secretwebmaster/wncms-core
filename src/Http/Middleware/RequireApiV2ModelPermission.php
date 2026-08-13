<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\ModelPermissionResolver;
use Wncms\Auth\Api\V2\AuthenticationContext;

final class RequireApiV2ModelPermission
{
    /**
     * Create the target-specific model permission guard.
     *
     * @param  \Wncms\Api\V2\ModelPermissionResolver  $resolver
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(
        private ModelPermissionResolver $resolver,
        private ApiResponseFactory $responses,
    ) {
    }

    /**
     * Validate the model selector and require its concrete permission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $suffix
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $suffix): Response
    {
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        if (! $context instanceof AuthenticationContext) {
            return $this->responses->failure(
                'authentication.invalid_token',
                'Authentication context is unavailable',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $requirement = $this->resolver->resolve($request->input('model'), $suffix);
        $actor = $context->actor();
        if (
            $requirement === null
            || ! method_exists($actor, 'checkPermissionTo')
            || ! $actor->checkPermissionTo($requirement['permission'])
        ) {
            return $this->responses->failure(
                'authorization.permission_denied',
                'Permission denied',
                Response::HTTP_FORBIDDEN
            );
        }

        $request->attributes->set('wncms_api_v2_model_key', $requirement['model_key']);
        $request->attributes->set('wncms_api_v2_model_permission', $requirement['permission']);

        return $next($request);
    }
}
