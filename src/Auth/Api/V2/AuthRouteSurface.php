<?php

namespace Wncms\Auth\Api\V2;

final class AuthRouteSurface
{
    /**
     * Return exact and parameterized descriptors for every authentication route.
     *
     * @return array<int, array{pattern: string, parameterized: bool}>
     */
    public static function corsRouteDescriptors(): array
    {
        return [
            ['pattern' => 'api/v2/backend/auth/login', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/refresh', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/logout', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/logout-all', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/me', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/sessions', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/sessions/*', 'parameterized' => true],
        ];
    }
}
