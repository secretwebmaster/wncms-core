<?php

if (!defined('WNCMS_THEME_START')) {
    exit('No direct script access allowed');
}

/**
 * Demo Theme Config
 * A simple WordPress-like blog theme for WNCMS
 */

return [

    /**
     * Theme info
     */
    'info' => [
        'id' => 'starter',
        'type' => 'blog',
        'name' => [
            'zh_TW' => '示範主題',
            'zh_CN' => '示范主题',
            'en' => 'Demo Theme',
        ],
        'description' => [
            'zh_TW' => '簡潔的部落格示範主題',
            'zh_CN' => '简洁的博客示范主题',
            'en' => 'A simple starter blog theme',
        ],
        'author' => 'WNCMS',
        'version' => '1.0.0',
        'created_at' => '2025-01-01',
        'updated_at' => '2025-01-01',
        'starter_url' => '',
    ],

    /**
     * Theme option tabs
     */
    'option_tabs' => [

        // General
        'general' => [
            [
                'label' => '通用',
                'type' => 'heading',
            ],
            [
                'label' => '網站副標題',
                'name' => 'site_subtitle',
                'type' => 'text',
                'description' => '顯示在網站標題下方的短句',
            ],
            [
                'label' => '主色調',
                'name' => 'primary_color',
                'type' => 'color',
            ],
        ],

        // Header
        'header' => [
            [
                'label' => '頁首',
                'type' => 'heading',
            ],
            [
                'label' => '頁首選單',
                'name' => 'header_menu',
                'type' => 'select',
                'options' => 'menus',
            ],
            [
                'label' => 'Logo 圖片',
                'name' => 'logo_image',
                'type' => 'image',
                'width' => 200,
            ],
        ],

        // Homepage
        'homepage' => [
            [
                'label' => '首頁',
                'type' => 'heading',
            ],
            [
                'label' => '首頁主分類',
                'name' => 'home_categories',
                'type' => 'tagify',
                'options' => 'tags',
                'tag_type' => 'post_category',
                'description' => '選擇要在首頁顯示的主分類 (可多選)',
            ],
            [
                'label' => '首頁每分類顯示文章數',
                'name' => 'home_posts_per_category',
                'type' => 'number',
            ],
        ],

        // Posts
        'posts' => [
            [
                'label' => '文章設定',
                'type' => 'heading',
            ],
            [
                'label' => '文章簡介長度',
                'name' => 'excerpt_length',
                'type' => 'number',
            ],
            [
                'label' => '預設縮圖占位圖',
                'name' => 'thumbnail_placeholder',
                'type' => 'image',
                'width' => 300,
            ],
        ],

        // Footer
        'footer' => [
            [
                'label' => '頁腳',
                'type' => 'heading',
            ],
            [
                'label' => '頁腳文字',
                'name' => 'footer_text',
                'type' => 'text',
            ],
            [
                'label' => '頁腳選單',
                'name' => 'footer_menu',
                'type' => 'select',
                'options' => 'menus',
            ],
        ],

        // Custom Code
        'custom_code' => [
            [
                'label' => '自訂代碼',
                'type' => 'heading',
            ],
            [
                'label' => '自訂 CSS (head)',
                'name' => 'head_css',
                'type' => 'textarea',
                'description' => '不需加上<style>標籤，會插入head內',
            ],
            [
                'label' => '自訂 CSS (body 底部)',
                'name' => 'custom_css',
                'type' => 'textarea',
            ],
        ],
    ],

    /**
     * Theme default values
     */
    'default' => [
        'site_subtitle' => 'Just another WNCMS blog',
        'primary_color' => '#000000',
        'home_posts_per_category' => 4,
        'excerpt_length' => 120,
        'footer_text' => 'Powered by WNCMS',
    ],

    /**
     * Static pages (none for starter)
     */
    'pages' => [
    ],

    /**
     * Dynamic templates (none for starter)
     */
    'templates' => [
    ],

    /**
     * Widgets (not required for basic starter)
     */
    'widgets' => [
    ],
];
