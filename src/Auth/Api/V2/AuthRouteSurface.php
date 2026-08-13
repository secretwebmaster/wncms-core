<?php

namespace Wncms\Auth\Api\V2;

final class AuthRouteSurface
{
    /**
     * Return representative Laravel request paths for every authentication route.
     *
     * @return array<int, string>
     */
    public static function corsPaths(): array
    {
        return [
            'api/v2/backend/auth/login',
            'api/v2/backend/auth/refresh',
            'api/v2/backend/auth/logout',
            'api/v2/backend/auth/logout-all',
            'api/v2/backend/auth/me',
            'api/v2/backend/auth/sessions',
            'api/v2/backend/auth/sessions/example-session-id',
        ];
    }
}
