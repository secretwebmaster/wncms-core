<?php

namespace Wncms\Api\V2;

use InvalidArgumentException;

final class LegacyOperationSecurity
{
    /**
     * Build the ordered middleware catalog entry for one resource operation.
     *
     * @param  string  $resource
     * @param  string  $action
     * @param  array<string, mixed>  $resourceConfig
     * @return array<int, string>
     */
    public static function resourceMiddleware(string $resource, string $action, array $resourceConfig): array
    {
        $permission = trim((string) ($resourceConfig['permissions'][$action] ?? ''));
        if ($permission === '') {
            throw new InvalidArgumentException("Backend API resource [{$resource}.{$action}] must declare a permission.");
        }

        return self::middleware(self::resourceAbility($resource, $action), $permission);
    }

    /**
     * Build the ordered middleware catalog entry for one bridge operation.
     *
     * @param  array<string, mixed>  $action
     * @return array<int, string>
     */
    public static function actionMiddleware(array $action): array
    {
        $name = trim((string) ($action['name'] ?? ''));
        $permission = trim((string) ($action['permission'] ?? ''));
        if ($name === '' || $permission === '') {
            throw new InvalidArgumentException('Backend API bridge operations must declare a name and permission.');
        }

        return self::middleware(self::actionAbility($name, (string) ($action['method'] ?? 'post')), $permission);
    }

    /**
     * Return the stable read/write ability for one resource operation.
     *
     * @param  string  $resource
     * @param  string  $action
     * @return string
     */
    public static function resourceAbility(string $resource, string $action): string
    {
        return $resource.'.'.(in_array($action, ['index', 'show'], true) ? 'read' : 'write');
    }

    /**
     * Return the stable read/write ability for one configured bridge operation.
     *
     * @param  string  $name
     * @param  string  $method
     * @return string
     */
    public static function actionAbility(string $name, string $method): string
    {
        $domain = explode('.', $name, 2)[0];

        return $domain.'.'.(strtoupper($method) === 'GET' ? 'read' : 'write');
    }

    /**
     * Return the mandatory ordered authorization middleware chain.
     *
     * @param  string  $ability
     * @param  string  $permission
     * @return array<int, string>
     */
    private static function middleware(string $ability, string $permission): array
    {
        return [
            'api_v2_ability:'.$ability,
            'api_v2_permission:'.$permission,
            'api_v2_website_scope',
        ];
    }
}
