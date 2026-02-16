<?php

namespace Wncms\Services\Core;

use Illuminate\Support\Facades\Route;

trait RouteMethods
{
    public function hasRoute(string $name): bool
    {
        return Route::has($name);
    }

    public function isActiveUrl(string $url, string $activeClass = 'active', ?string $inActiveClass = null): ?string
    {
        if ($url !== '/') {
            $url = trim($url, '/');
        }

        $activeConditions = [
            request()->url(),
            request()->path(),
            url()->current(),
        ];

        return in_array($url, $activeConditions) ? $activeClass : $inActiveClass;
    }

    public function isActiveRoutes(array|string $routes, string $activeClass = 'active', ?string $inActiveClass = null): ?string
    {
        $currentRoute = Route::currentRouteName();

        if (!is_array($routes)) {
            $routes = [$routes];
        }

        return in_array($currentRoute, $routes) ? $activeClass : $inActiveClass;
    }

    public function getRoute(string $name, array $params = [], bool $isFullPath = true, ?string $domain = null): ?string
    {
        if (Route::has($name)) {
            if (!empty($domain)) {
                return wncms()->addHttps($domain) . route($name, $params, false);
            }

            return route($name, $params, $isFullPath);
        }

        return null;
    }
}
