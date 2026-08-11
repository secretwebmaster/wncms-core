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
        'allowed_shared_store_classes' => [
            \Illuminate\Cache\RedisStore::class,
            \Illuminate\Cache\MemcachedStore::class,
            \Illuminate\Cache\DatabaseStore::class,
            \Illuminate\Cache\DynamoDbStore::class,
            \Illuminate\Cache\FileStore::class,
        ],
    ],
    'providers' => [
        \Wncms\Api\V2\Providers\CoreFrontendContractProvider::class,
        \Wncms\Api\V2\Providers\LegacyBackendContractProvider::class,
    ],
];
