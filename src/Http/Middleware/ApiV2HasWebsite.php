<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\IdempotencyService;

class ApiV2HasWebsite
{
    /**
     * Create the API v2 website-context middleware.
     *
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(protected ApiResponseFactory $responses)
    {
    }

    /**
     * Require a current website outside website-management routes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = (string) optional($request->route())->getName();

        if (str_starts_with($routeName, 'api.v2.backend.websites.')) {
            return $next($request);
        }

        $website = wncms()->website()->get();
        if (! $website) {
            return $this->responses->failure(
                'website.context_missing',
                'Website context is not available',
                Response::HTTP_CONFLICT
            );
        }

        $request->attributes->set(IdempotencyService::WEBSITE_CONTEXT_ATTRIBUTE, $website);

        return $next($request);
    }
}
