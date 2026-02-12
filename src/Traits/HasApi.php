<?php

namespace Wncms\Traits;

trait HasApi
{
    public static function hasApi(): bool
    {
        return static::$hasApi ?? false;
    }

    public static function getApiRoutes(): array
    {
        $routes = static::$apiRoutes ?? [];
        $fallbackPackageId = static::resolveApiPackageId();

        foreach ($routes as $index => $route) {
            if (!is_array($route)) {
                continue;
            }

            $routes[$index]['package_id'] = $route['package_id'] ?? $fallbackPackageId;
        }

        return $routes;
    }

    public static function addApiRoute(array $route): void
    {
        if (!isset(static::$apiRoutes)) {
            static::$apiRoutes = [];
        }

        $route['package_id'] = $route['package_id'] ?? static::resolveApiPackageId();

        static::$apiRoutes[] = $route;
    }

    public static function removeApiRoute(string $key): void
    {
        if (empty(static::$apiRoutes)) {
            return;
        }

        static::$apiRoutes = array_values(
            array_filter(
                static::$apiRoutes,
                fn($route) => ($route['key'] ?? null) !== $key
            )
        );
    }

    /**
     * Get translated label for an API permission route.
     *
     * Priority:
     * 1. $route['package_id'] if set
     * 2. static::$packageId if set
     * 3. default to wncms-core
     *
     * If translation is missing, fall back to key itself.
     */
    public static function getApiLabel(array $route, ?string $packageId = null): string
    {
        $key = $route['key'] ?? null;
        if (!$key) {
            return '';
        }

        // Determine package_id
        $packageId = $packageId ?? $route['package_id'] ?? static::resolveApiPackageId();

        // Try translation
        $text = __($packageId . '::word.' . $key);

        // If no translation, fallback to raw key
        if ($text === ($packageId . '::word.' . $key)) {
            return $key;
        }

        return $text;
    }

    protected static function resolveApiPackageId(): string
    {
        if (method_exists(static::class, 'getPackageId')) {
            $resolved = static::getPackageId();
            if (is_string($resolved) && $resolved !== '') {
                return $resolved;
            }
        }

        return static::$packageId ?? 'wncms';
    }
}
