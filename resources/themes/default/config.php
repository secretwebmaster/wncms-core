<?php

if (!defined('WNCMS_THEME_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}

/** 
 * ----------------------------------------------------------------------------------------------------
 * 主題名稱: 預設主題
 * 適用系統: 文尼CMS v6+
 * ----------------------------------------------------------------------------------------------------
 */
return [
    /**
     * ----------------------------------------------------------------------------------------------------
     * Theme info
     * ----------------------------------------------------------------------------------------------------
     */
    'info' => [
        'id' => 'default',
        'type' => 'listing',
        'name' => [
            'zh_TW' => '預設主題',
            'zh_CN' => '预设主题',
            'en' => 'Default Theme',
        ],
        'description' => [
            'zh_TW' => 'Hello World!',
            'zh_CN' => 'Hello World!',
            'en' => 'Hello World!',
        ],
        'author' => '文尼先生',
        'version' => '6.0.0',
        'created_at' => '2023-01-01',
        'updated_at' => '2026-01-29',
        'demo_url' => 'https://wncms.cc',
    ],


    /**
     * ----------------------------------------------------------------------------------------------------
     * Theme options
     * ----------------------------------------------------------------------------------------------------
     */
    'option_tabs' => [

        //! 通用
        'general' => [
            [
                'label'=>'通用',
                'type'=>'heading',
            ],
            [
                'label'=>'搜索框文字',
                'name'=>'search_placeholder',
                'type'=>'text',
                'description'=>'搜索框沒有內容時的替代內容'
            ],
            [
                'label' => '選擇文章',
                'name' => 'featured_posts',
                'type' => 'tagify',
                'options' => 'posts',
                'description' => '選擇一篇或多篇要在頁面上顯示的文章',
            ],
            [
                'label' => '選擇頁面',
                'name' => 'featured_pages',
                'type' => 'tagify',
                'options' => 'pages',
                'description' => '選擇一個或多個要在頁面',
            ],
        ],

        //! 頁首
        'header' => [
            [
                'label'=>'頁首',
                'type'=>'heading',
            ],
            [
                'label'=>'選擇分類',
                'name'=>'post_category',
                'type'=>'tagify',
                'options'=>'tags',
                'tag_type'=>'post_category',
                'description'=>'選擇一個文章分類來顯示在頁首',
            ],
        ],

        //! 頁腳
        'footer' => [
            [
                'label'=>'頁腳',
                'type'=>'heading',
            ],
            [
                'label'=>'頁腳文字1',
                'name'=>'footer_text_1',
                'type'=>'text',
            ],
            [
                'label'=>'頁腳文字2',
                'name'=>'footer_text_2',
                'type'=>'text',
            ],
        ],

        //! 廣告位置
        'ads' => [
            [
                'label'=>'廣告位置',
                'type'=>'heading',
            ],
            [
                'label'=>'桌面版頁首廣告 (728x90)',
                'name'=>'header_ads_01',
                'type'=>'textarea',
                'description'=>'需連同<script></script>標籤貼上，可以埋疊多個廣告',
            ],
        ],

        'custom_codes' => [
            //! 自訂代碼
            [
                'label'=>'自訂代碼',
                'type'=>'heading',
            ],
            [
                'label'=>'自訂頭部css',
                'name'=>'head_css',
                'type'=>'textarea',
                'description'=>'不需加上<style>標籤，會出現在head標籤內',
            ],
            [
                'label'=>'自訂css',
                'name'=>'custom_css',
                'type'=>'textarea',
                'description'=>'不需加上<style>標籤，出現在頁面最下方',
            ],
        ],
       
        'site_info' => [
            //! 聯絡資訊
            [
                'label'=>'聯絡資訊',
                'type'=>'heading',
            ],
            [
                'label'=>'聯絡Email',
                'name'=>'contact_email',
                'type'=>'text',
            ],
            [
                'label'=>'聯絡Telegram',
                'name'=>'contact_telegram',
                'type'=>'text',
                'description'=>'不需要http，不需要@，只需輸入用戶名部分',
            ],
            [
                'label'=>'聯絡QQ',
                'name'=>'contact_qq',
                'type'=>'text',
            ],
        ],

    ],
    

    /**
     * ----------------------------------------------------------------------------------------------------
     * Default values for theme options
     * ----------------------------------------------------------------------------------------------------
     */
    'default' => [
        'search_placeholder' => '輸入關鍵字',
        'footer_text_1' => '免責聲明：本站資源來自互聯網收集,僅供用於學習和交流, 請遵循相關法律法規,本站壹切資源不代表本站立場, 如有侵權、後門、不妥請聯系本站刪除',
        'footer_text_2' => '© 2023 文尼CMS All Rights Reserved.',
        'contact_email' => 'earnbyshare2016@gmail.com',
        'contact_telegram' => 'secretwebmaster',
        'contact_qq' => '123456789',
    ],


    /**
     * ----------------------------------------------------------------------------------------------------
     * Static pages (only one page)
     * ----------------------------------------------------------------------------------------------------
     */
    'pages' => [
        'about' => [
            'label' => '關於我們',
            'key' => 'about',
            'route' => 'frontend.pages.show',
            'route_params' => ['slug' => 'about'],
            'blade_name' => 'about',
        ]
    ],

    'templates' => [
        'about' => [
            'slug' => 'about',
            'title' => '關於我們',
            'blade_name' => 'about',
            'widgets' => ['block_1']
        ],
    ],

    /**
     * ----------------------------------------------------------------------------------------------------
     * Widgets
     * To be loaded in page templates
     * ----------------------------------------------------------------------------------------------------
     */
    'widgets' => [
        'block_1' => [
            'name' => '區塊1',
            'key' => 'block_1',
            'icon' => 'fa-solid fa-newspaper',
            'fields' => [
                ['label' => '演示圖', 'type' => 'display_image', 'path' => '/wncms/images/placeholders/placeholder_4_1_12.webp', 'width' => '800px'],
                ['label' => '標題', 'name' => 'block_1_title', 'type' => 'text'],
                ['label' => '描述', 'name' => 'block_1_description', 'type' => 'textarea'],
                ['label' => '圖片 (787x553)', 'name' => 'block_1_image', 'type' => 'image', 'width' => 350, 'height' => 246],
            ],
        ],
    ],

    
];