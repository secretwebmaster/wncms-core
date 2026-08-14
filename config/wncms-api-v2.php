<?php

return [
    'schema_version' => '2.0.0',
    'openapi' => [
        'title' => 'WNCMS API',
        'version' => '2.0.0',
    ],
    'auth_security' => [
        'access_token_lifetime_minutes' => 15,
        'refresh_token_lifetime_days' => 30,
        'refresh_transport' => 'json',
        'permanent_remember_enabled' => false,
        'refresh_cookie_domain' => null,
        'refresh_cookie_same_site' => 'lax',
        'refresh_cookie_allowed_origins' => '',
        'refresh_cookie_referer_fallback' => false,
        'login_account_attempts' => 5,
        'login_ip_attempts' => 30,
        'login_window_minutes' => 15,
        'login_progressive_delay_seconds' => '1,2,4,8,16,30',
        'high_risk_action_mode' => 'direct',
        'action_plan_lifetime_seconds' => 300,
        'step_up_lifetime_seconds' => 300,
        'blade_enabled' => true,
        'legacy_personal_tokens_enabled' => false,
        'legacy_personal_tokens_cutoff_at' => null,
        'security_event_retention_days' => 90,
        'client_callback_url' => env('WNCMS_API_AUTH_CLIENT_CALLBACK_URL'),
        'security_event_correlation' => [
            'active_key_version' => env('WNCMS_API_SECURITY_EVENT_CORRELATION_KEY_VERSION'),
            'keys' => [
                'v1' => [
                    'ip' => env('WNCMS_API_SECURITY_EVENT_IP_HMAC_KEY'),
                    'login_identifier' => env('WNCMS_API_SECURITY_EVENT_LOGIN_IDENTIFIER_HMAC_KEY'),
                    'user_agent' => env('WNCMS_API_SECURITY_EVENT_USER_AGENT_HMAC_KEY'),
                ],
            ],
        ],
    ],
    'idempotency' => [
        'store' => env('WNCMS_API_V2_IDEMPOTENCY_STORE'),
        'header' => 'Idempotency-Key',
        'ttl_seconds' => 86400,
        'lock_seconds' => 15,
        // FileStore requires exact opt-in and a shared volume used by every API process.
        // DynamoDB is always rejected because its eventually consistent reads can replay mutations.
        'allowed_shared_store_classes' => [
            \Illuminate\Cache\RedisStore::class,
            \Illuminate\Cache\MemcachedStore::class,
            \Illuminate\Cache\DatabaseStore::class,
        ],
    ],
    'operations' => [
        'store' => env('WNCMS_API_V2_OPERATION_STORE'),
        'ttl_seconds' => 86400,
        'lock_seconds' => 10,
        // FileStore requires exact opt-in and a shared volume used by every API and queue worker.
        // DynamoDB is always rejected because Laravel operation reads are eventually consistent.
        'allowed_shared_store_classes' => [
            \Illuminate\Cache\RedisStore::class,
            \Illuminate\Cache\MemcachedStore::class,
            \Illuminate\Cache\DatabaseStore::class,
        ],
    ],
    'contract' => [
        // These routes deliver/authenticate the contract itself or support the legacy admin shell.
        // Every other named api/v2 route must have a registry operation and OpenAPI entry.
        'excluded_route_names' => [
            'api.v2.openapi',
            'api.v2.capabilities',
            'api.v2.backend.auth.login',
            'api.v2.backend.auth.refresh',
            'api.v2.backend.auth.logout',
            'api.v2.backend.auth.logout_all',
            'api.v2.backend.auth.me',
            'api.v2.backend.auth.sessions.index',
            'api.v2.backend.auth.sessions.destroy',
            'api.v2.backend.auth.reauthenticate',
            'api.v2.backend.action_plans.store',
            'api.v2.backend.i18n.ui',
            'api.v2.backend.translations',
        ],
    ],
    'providers' => [
        \Wncms\Api\V2\Providers\CoreFrontendContractProvider::class,
        \Wncms\Api\V2\Providers\CoreBackendContractProvider::class,
        \Wncms\Api\V2\Providers\LegacyBackendContractProvider::class,
    ],
];
