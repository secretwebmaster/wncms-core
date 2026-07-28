<?php

return [

    'multi_website' => false,
    'testing_is_installed' => null,

    'models' => [
        // Models default to website_mode=global when not configured.
        // 'advertisement' => [
        //     'class' => \Wncms\Models\Advertisement::class,
        //     'website_mode' => 'single',
        // ],
        // 'tag' => [
        //     'class' => \Wncms\Models\Tag::class,
        //     'website_mode' => 'global', // global | single | multi
        // ],
        // 'user' => [
        //     'class' => \Wncms\Models\User::class,
        //     'website_mode' => 'global',
        // ],
        // 'post' => [
        //     'class' => \Wncms\Models\Post::class,
        //     'website_mode' => 'single',
        // ],
        // 'link' => [
        //     'class' => \Wncms\Models\Link::class,
        //     'website_mode' => 'multi',
        // ],
    ],

    'cache' => [
        // Laravel 13 defaults cache.serializable_classes=false.
        // Tri-state behavior:
        // - false: explicitly disable WNCMS compatibility override
        // - true: explicitly enable WNCMS compatibility override
        // - null: env missing; enable by default for backward compatibility
        'serializable_classes_compat' => env('WNCMS_CACHE_SERIALIZABLE_CLASSES_COMPAT', null),

        // Optional allow-list for cache unserialization.
        // Empty means fallback to boolean true when compat switch is enabled.
        'serializable_classes' => [],
    ],

    'automation' => [
        'system_actor_user_id' => env('WNCMS_AUTOMATION_SYSTEM_ACTOR_USER_ID'),
    ],

    'mcp' => [
        'enabled' => env('WNCMS_MCP_ENABLED', false),
        'server' => 'wncms',
    ],

    // 'ecommerce' => [
    //     'default_currency_symbol' => '$',
    //     'default_currency_unit' => 'USD',
    // ],
];
