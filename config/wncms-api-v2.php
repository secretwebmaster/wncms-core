<?php

return [
    'schema_version' => '2.0.0',
    'openapi' => [
        'title' => 'WNCMS API',
        'version' => '2.0.0',
    ],
    'idempotency' => [
        'store' => env('WNCMS_API_V2_IDEMPOTENCY_STORE'),
        'header' => 'Idempotency-Key',
        'ttl_seconds' => 86400,
        'lock_seconds' => 15,
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
            'api.v2.backend.auth.logout',
            'api.v2.backend.auth.me',
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
