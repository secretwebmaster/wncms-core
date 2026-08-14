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
            ['pattern' => 'api/v2/backend/auth/email-verification/send', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/email-verification/verify', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/email/change', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/email/change/confirm', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/login', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/refresh', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/logout', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/logout-all', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/me', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/password', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/password/forgot', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/password/reset', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/profile', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/reauthenticate', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/service-token-options', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/service-tokens', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/service-tokens/*', 'parameterized' => true],
            ['pattern' => 'api/v2/backend/auth/service-tokens/*/rotate', 'parameterized' => true],
            ['pattern' => 'api/v2/backend/auth/sessions', 'parameterized' => false],
            ['pattern' => 'api/v2/backend/auth/sessions/*', 'parameterized' => true],
        ];
    }
}
