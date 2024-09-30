<?php

return [
    [
        'tab_name' => 'basic',
        'tab_content' => [
            ['type' => 'text', 'name' => 'version', 'disabled' => 'true'],
            ['type' => 'switch', 'name' => 'disable_core_update'],
            ['type' => 'switch', 'name' => 'check_beta_functions', 'badge'=>'Beta'],
            ['type' => 'switch', 'name' => 'show_developer_hints', 'badge'=>'Dev'],
            ['type' => 'switch', 'name' => 'force_https'],
            ['type' => 'text', 'name' => 'system_logo'],
            ['type' => 'text', 'name' => 'system_name'],
            ['type' => 'text', 'name' => 'system_description'],
            ['type' => 'text', 'name' => 'system_keyword'],
            ['type' => 'textarea', 'name' => 'system_meta'],
            ['type' => 'custom', 'name' => 'display_model'],
        ]
    ],
    [
        'tab_name' => 'auth',
        'tab_content' => [
            ['type' => 'switch', 'name' => 'show_auth_page_side_image',],
            ['type' => 'switch', 'name' => 'allow_google_login'],
        ]
    ],
    [
        'tab_name' => 'smtp',
        'tab_content' => [
            ['type' => 'switch', 'name' => 'enable_smtp'],
            ['type' => 'text', 'name' => 'superadmin_email'],
            ['type' => 'select', 'name' => 'mail_driver', 'options' => ['smtp']],
            ['type' => 'text', 'name' => 'smtp_host'],
            ['type' => 'text', 'name' => 'smtp_port'],
            ['type' => 'text', 'name' => 'smtp_mode'],
            ['type' => 'text', 'name' => 'smtp_username'],
            ['type' => 'text', 'name' => 'smtp_password'],
            ['type' => 'text', 'name' => 'smtp_from_name'],
        ]
    ],
    [
        'tab_name' => 'api',
        'tab_content' => [
            ['type' => 'text', 'name' => 'request_user_agent'],
            ['type' => 'text', 'name' => 'cloudflare_email'],
            ['type' => 'text', 'name' => 'cloudflare_api_token'],
            ['type' => 'text', 'name' => 'cloudflare_account_id'],
        ]
    ],
    [
        'tab_name' => 'cache',
        'tab_content' => [
            ['type' => 'switch', 'name' => 'enable_cache'],
            ['type' => 'number', 'name' => 'data_cache_time'],
            ['type' => 'number', 'name' => 'live_data_cache_time'],
            ['type' => 'switch', 'name' => 'cache_view_count'],
        ]
    ],
    [
        'tab_name' => 'social_login',
        'tab_content' => [
            ['type' => 'heading', 'name' => 'Google'],
            ['type' => 'text', 'name' => 'google_client_id'],
            ['type' => 'text', 'name' => 'google_client_secret'],
            ['type' => 'text', 'name' => 'google_redirect'],
        ]
    ],
    [
        'tab_name' => 'page',
        'tab_content' => [
            ['type' => 'switch', 'name' => 'hide_empty_page_title'],
        ]
    ],
    [
        'tab_name' => 'collect',
        'tab_content' => [
            ['type' => 'switch', 'name' => 'auto_tag_by_keywords'],
            ['type' => 'switch', 'name' => 'localize_post_image'],
        ]
    ],
    [
        'tab_name' => 'content',
        'tab_content' => [
            ['type' => 'switch', 'name' => 'restore_trashed_content_to_published'],
            ['type' => 'switch', 'name' => 'convert_thumbnail_to_webp'],
        ]
    ],
    [
        'tab_name' => 'user',
        'tab_content' => [
            ['type' => 'switch', 'name' => 'disable_registration'],
            ['type' => 'switch', 'name' => 'send_user_welcom_email'],
            ['type' => 'switch', 'name' => 'allow_merge_account'],
            ['type' => 'switch', 'name' => 'use_custom_user_dashbaord'],
        ]
    ],
    [
        'tab_name' => 'admin',
        'tab_content' => [
            ['type' => 'switch', 'name' => 'use_custom_admin_dashbaord'],
            ['type' => 'switch', 'name' => 'hide_default_admin_dashboard_items'],
            ['type' => 'switch', 'name' => 'hide_system_update_log'],
        ]
    ],
    [
        'tab_name' => 'analytics',
        'tab_content' => [
            ['type' => 'number', 'name' => 'click_count_cooldown'],
            ['type' => 'number', 'name' => 'view_count_cooldown'],
            ['type' => 'number', 'name' => 'other_count_cooldown'],
        ]
    ],
];