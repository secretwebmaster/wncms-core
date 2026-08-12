<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Wncms\Tests\TestCase;

class ApiRouteRegistrationTest extends TestCase
{
    /**
     * Verify package v1/v2 routes load before custom overrides and catch-all routes.
     *
     * @return void
     */
    public function test_custom_api_routes_load_last_without_changing_package_middleware_contracts(): void
    {
        $router = app('router');
        $originalRoutes = $router->getRoutes();
        $originalBasePath = base_path();

        try {
            $router->setRoutes(new RouteCollection);
            app()->setBasePath(__DIR__.'/../../../Fixtures');

            Route::prefix('api')->group(dirname(__DIR__, 4).'/routes/api.php');

            $routes = $router->getRoutes();
            $getRoutes = array_values($routes->getRoutesByMethod()['GET']);
            $uris = array_map(static fn ($route): string => $route->uri(), $getRoutes);

            $this->assertLessThan(
                array_search('api/v2/capabilities', $uris, true),
                array_search('api/v1/posts', $uris, true)
            );
            $this->assertLessThan(
                array_search('api/custom/health', $uris, true),
                array_search('api/v2/capabilities', $uris, true)
            );
            $this->assertSame('api/{path}', end($uris));

            $matchedOverride = $routes->match(Request::create('/api/v2/openapi.json', 'GET'));
            $matchedPackage = $routes->match(Request::create('/api/v2/capabilities', 'GET'));

            $this->assertSame('custom.openapi.override', $matchedOverride->getName());
            $this->assertSame(['api'], $matchedOverride->gatherMiddleware());
            $this->assertSame('api.v2.capabilities', $matchedPackage->getName());
            $this->assertSame(
                ['api_v2_request_id', 'api', 'api_v2_whitelist', 'api_v2_token_auth'],
                $matchedPackage->gatherMiddleware()
            );
            $this->assertSame(
                ['api'],
                $routes->match(Request::create('/api/v1/posts', 'GET'))->gatherMiddleware()
            );

            foreach (['api.v1.posts.index', 'api.v2.capabilities', 'custom.health', 'custom.fallback'] as $name) {
                $this->assertCount(
                    1,
                    array_filter(
                        $routes->getRoutes(),
                        static fn ($route): bool => $route->getName() === $name
                    ),
                    "Route {$name} should be registered exactly once."
                );
            }
        } finally {
            app()->setBasePath($originalBasePath);
            $router->setRoutes($originalRoutes);
        }
    }
}
