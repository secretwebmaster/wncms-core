<?php

return [

    'multi_website' => true,
    
    'default_post_model' => \App\Models\Post::class,
    
    // 'default_user_model' => \App\Models\User::class,

    'ecommerce' => [
        'default_currency_symbol' => '$',
        'default_currency_unit' => 'USD',
    ]
];