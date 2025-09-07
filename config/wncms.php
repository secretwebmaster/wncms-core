<?php

return [

    'multi_website' => false,

    'models' => [
        'advertisement' => [
            'class' => \Wncms\Models\Advertisement::class,
            'website_mode' => 'single',
        ],
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

    'ecommerce' => [
        'default_currency_symbol' => '$',
        'default_currency_unit' => 'USD',
    ],
];
