<?php

if (!defined('WNCMS_THEME_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}

/**
 * ----------------------------------------------------------------------------------------------------
 * Starter Theme Config (WNCMS v6+)
 * ----------------------------------------------------------------------------------------------------
 *
 * This file defines:
 *  - Theme metadata:            info
 *  - Theme option groups/tabs:  option_tabs
 *  - Theme default values:      default
 *
 * ----------------------------------------------------------------------------------------------------
 * Array Structure
 * ----------------------------------------------------------------------------------------------------
 *
 * 1) info
 *    Basic theme metadata.
 *
 *    Required keys:
 *      label       string  Theme display name (shown in backend)
 *                  e.g. "文尼 Starter 主題"
 *
 *      name        string  System theme ID, must match theme root folder name
 *                  e.g. "starter"
 *
 *      author      string  Theme author
 *                  e.g. "文尼先生"
 *
 *      description string  Theme description
 *                  e.g. "文尼 CMS 第一款簡潔易用的主題"
 *
 *      version     string  Theme version (semantic, 3-digit)
 *                  e.g. "6.0.0"
 *
 *      created_at  string  Theme first created date (YYYY-MM-DD)
 *                  e.g. "2023-01-01"
 *
 *      updated_at  string  Theme last updated date (YYYY-MM-DD)
 *                  e.g. "2025-01-01"
 *
 * ----------------------------------------------------------------------------------------------------
 *
 * 2) option_tabs
 *    Each key under option_tabs is a "group" (tab or section) of options.
 *
 *    Example:
 *      'general' => [
 *          [
 *              'label' => '通用',
 *              'type'  => 'heading',
 *          ],
 *          [
 *              'label'       => '搜索框文字',
 *              'name'        => 'search_placeholder',
 *              'type'        => 'text',
 *              'description' => '搜索框沒有內容時的替代內容',
 *          ],
 *      ],
 *
 *    Each element inside a group is an "option" array with the following keys:
 *
 *      Common option fields
 *      --------------------
 *      label               string          required  | 顯示用名稱
 *      name                string          required* | 儲存鍵名，Blade 中用 gto('name') 讀取
 *                                                      *heading / sub_heading / display_image 不需要 name
 *
 *      description         string          optional  | 顯示於 label 下方的說明文字 (支援 HTML)
 *      align_items_center  bool            optional  | 垂直置中 label 與輸入框
 *      translate_option    bool            optional  | 是否翻譯 options 值，預設 true，false 則直接顯示原文
 *      sub_items           array           optional  | inline / accordion 等複合欄位的子項目設定
 *      limit               int             optional  | 多選類（checkbox / tagify）可選上限
 *      required            bool            optional  | 是否為必填，建議搭配 default 值
 *      disabled            bool            optional  | 僅顯示現有值，不可修改，建議搭配 default 值
 *
 *      options             array|string    optional  | 用於 select / tagify 等欄位的資料來源
 *          - array  : 用於一般 select / tagify
 *          - string : 特殊來源關鍵字：
 *              'menus'     使用網站菜單列表
 *              'posts'     使用文章列表
 *              'pages'     使用頁面列表
 *              'tags'      使用標籤列表（需搭配 tag_type）
 *              'positions' 使用廣告位列表
 *
 *      tag_type            string          optional  | 搭配 options='tags' 使用，例如 'post_category'
 *      whitelist_tag_only  bool            optional  | tagify 是否只允許既有項目，預設 true
 *
 *      repeat              int             optional  | 重複產生多組同樣欄位（僅適用 inline / accordion）
 *      content             array           optional  | accordion 內容項目，通常為 inline / 一般欄位設定
 *      id                  string          optional  | accordion 的唯一 ID（未設定時會自動產生）
 *
 *      type                string          required  | 欄位類型（下列其一）
 *
 *          基本文字 / 數字
 *          --------------
 *          text            單行文字輸入框
 *          number          數字輸入框
 *
 *          媒體 / 顏色
 *          --------------
 *          image           圖片上傳，綁定到 website 模型並儲存圖片網址
 *          color           顏色選擇器（Pickr）
 *
 *          圖片集（NEW）
 *          --------------
 *          gallery         圖片集（多張圖片上傳）
 *                          - 儲存為 array 或 JSON 字串
 *                          - Blade 使用 gto('gallery_key') 取得陣列
 *                          - 支援 width / height 限制
 *
 *          選擇類
 *          --------------
 *          select          下拉選單，需搭配 options
 *          boolean         開關（switch），實際儲存 0/1
 *
 *          內容編輯
 *          --------------
 *          editor          TinyMCE 富文字編輯器
 *          textarea        多行文字輸入框
 *
 *          結構 / 版面
 *          --------------
 *          heading         區塊大標題，不儲存資料，可搭配 description
 *          sub_heading     區塊小標題（無背景），不儲存資料，可搭配 description
 *          display_image   僅顯示示意圖片，不儲存資料（用於預覽版位 / 示意圖）
 *          hidden          隱藏欄位，直接儲存值，不顯示 UI
 *          inline          同一列顯示多個 sub_items
 *                          - 適合一組資料包含 text/number/image 多種欄位
 *                          - 可搭配 repeat 產生多組
 *
 *          進階 / 陣列類
 *          --------------
 *          tagify          Tagify 多選輸入
 *                          - 需搭配 options
 *                          - 若 options='tags' 則需設定 tag_type
 *
 *          accordion       手風琴（Accordion）容器
 *                          - 使用 content 定義內部欄位
 *                          - 可搭配 repeat 重複產生多個 Accordion 區塊
 *                          - 若 sortable=true，後端會儲存排序 JSON
 *
 * ----------------------------------------------------------------------------------------------------
 *
 * 3) default
 *    default 用來定義主題選項的預設值。
 *
 *      key_name => value
 *
 *    行為說明：
 *      - 網站首次啟用主題時，會套用這些預設值
 *      - 使用者在後台點選「重置主題設定」時，會回復到這些預設值
 *
 * ----------------------------------------------------------------------------------------------------
 * 備註：
 * - 所有 name 對應的值，最終會以 key => value 形式儲存在資料庫中，
 *   並可在前台 Blade 中透過 gto('key_name') 取得。
 * - 若欄位需要多語系，請保留 name 並在後台翻譯功能中維護。
 * ----------------------------------------------------------------------------------------------------
 */

/**
 * ----------------------------------------------------------------------------------------------------
 * 主題名稱: starter
 * 適用系統: WNCMS v6+
 * ----------------------------------------------------------------------------------------------------
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
        'demo_url' => '',
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

        // ! 測試
        'example' => [

            //  標題區塊
            [
                'label' => '主標題',
                'type' => 'heading',
                'description' => '測試主標題的顯示區塊'
            ],
            [
                'label' => '副標題',
                'type' => 'sub_heading',
                'description' => '測試副標題的顯示區塊'
            ],

            // 靜態圖片顯示
            [
                'label' => '圖片顯示1',
                'type' => 'display_image',
                'path' => 'wncms/images/placeholders/upload.png',
                'col' => 4,
            ],

            // 靜態圖片顯示
            [
                'label' => '圖片顯示2',
                'type' => 'display_image',
                'path' => 'wncms/images/placeholders/upload.png',
                'width' => 300,
                'height' => 120,
            ],

            // 隱藏欄位
            [
                'label' => '隱藏欄位測試',
                'name'  => 'hidden_value',
                'type'  => 'hidden',
            ],

            // 基本欄位
            [
                'label' => '文字輸入',
                'name'  => 'test_text',
                'type'  => 'text',
                'placeholder' => '請輸入文字...',
                'required' => true,
            ],
            [
                'label' => '數字輸入',
                'name'  => 'test_number',
                'type'  => 'number',
                'required' => false,
            ],
            [
                'label' => '多行文字',
                'name'  => 'test_textarea',
                'type'  => 'textarea',
            ],
            [
                'label' => '顏色選擇器',
                'name'  => 'test_color',
                'type'  => 'color',
                'placeholder' => '#ffffff'
            ],

            // 圖片上傳
            [
                'label' => '圖片上傳',
                'name'  => 'test_image',
                'type'  => 'image',
                'width' => 500,
                'height' => 300,
            ],

            // 布林開關
            [
                'label' => '布林開關',
                'name'  => 'test_boolean',
                'type'  => 'boolean',
            ],

            // 富文本編輯器
            [
                'label' => '編輯器測試',
                'name'  => 'test_editor',
                'type'  => 'editor',
            ],

            // 選單欄位
            // 1. 選擇頁面
            [
                'label'   => '選擇頁面',
                'name'    => 'select_page',
                'type'    => 'select',
                'options' => 'pages'
            ],

            // 2. 選擇選單
            [
                'label'   => '選擇選單',
                'name'    => 'select_menu',
                'type'    => 'select',
                'options' => 'menus'
            ],

            // 3. 選擇廣告位置
            [
                'label'   => '選擇廣告位置',
                'name'    => 'select_position',
                'type'    => 'select',
                'options' => 'positions',
                'translate_option' => false,
            ],

            // 4. 自訂選項陣列
            [
                'label'   => '自訂選項陣列',
                'name'    => 'select_custom_array',
                'type'    => 'select',
                'translate_option' => false,
                'options' => [
                    'one',
                    'two',
                    'three',
                ],
            ],


            // Tagify（五種模式）
            // TAGIFY：標籤
            [
                'label'   => 'Tagify 標籤',
                'name'    => 'tagify_tags',
                'type'    => 'tagify',
                'options' => 'tags',
                'tag_type' => 'post_category',
                'limit' => 10,
            ],

            // TAGIFY：頁面
            [
                'label'   => 'Tagify 頁面',
                'name'    => 'tagify_pages',
                'type'    => 'tagify',
                'options' => 'pages',
                'limit' => 5,
            ],

            // TAGIFY：文章
            [
                'label'   => 'Tagify 文章',
                'name'    => 'tagify_posts',
                'type'    => 'tagify',
                'options' => 'posts',
                'limit' => 8,
            ],

            // TAGIFY：選單
            [
                'label'   => 'Tagify 選單',
                'name'    => 'tagify_menus',
                'type'    => 'tagify',
                'options' => 'menus',
                'limit' => 6,
            ],

            // TAGIFY：自訂陣列
            [
                'label'   => 'Tagify 自訂內容',
                'name'    => 'tagify_custom_array',
                'type'    => 'tagify',
                'options' => [
                    ['value' => 'a', 'name' => 'A'],
                    ['value' => 'b', 'name' => 'B'],
                    ['value' => 'c', 'name' => 'C'],
                ],
                'limit' => 10,
            ],

            // INLINE（單列）
            [
                'label'     => 'Inline 群組',
                'type'      => 'inline',
                'sub_items' => [
                    [
                        'label' => 'Inline 標題',
                        'name'  => 'inline_title',
                        'type'  => 'text',
                    ],
                    [
                        'label' => 'Inline 數字',
                        'name'  => 'inline_number',
                        'type'  => 'number',
                    ],
                ]
            ],

            // INLINE（多列 repeat）
            [
                'label'     => 'Inline 多組',
                'type'      => 'inline',
                'repeat'    => 3,
                'sub_items' => [
                    [
                        'label' => 'Inline 文字 R',
                        'name'  => 'inline_text',
                        'type'  => 'text',
                    ],
                    [
                        'label' => 'Inline 顏色 R',
                        'name'  => 'inline_color',
                        'type'  => 'color',
                    ],
                ]
            ],

            // 手風琴（單組）
            [
                'label'   => '手風琴（單組）',
                'name'    => 'accordion_single',
                'type'    => 'accordion',
                'repeat'  => 1,
                'content' => [
                    [
                        'label' => '標題',
                        'name'  => 'acc_title',
                        'type'  => 'text',
                    ],
                    [
                        'label' => '圖片',
                        'name'  => 'acc_image',
                        'type'  => 'image',
                    ],
                    [
                        'label'     => '手風琴內的 Inline 群組',
                        'type'      => 'inline',
                        'sub_items' => [
                            [
                                'label' => '子項文字',
                                'name'  => 'sub_t',
                                'type'  => 'text',
                            ],
                            [
                                'label' => '子項數字',
                                'name'  => 'sub_n',
                                'type'  => 'number',
                            ],
                        ]
                    ],
                ]
            ],

            // 手風琴（多組 + 可排序）
            [
                'label'   => '手風琴（多組＋可排序）',
                'name'    => 'accordion_sortable',
                'type'    => 'accordion',
                'repeat'  => 3,
                'sortable' => true,
                'content' => [
                    [
                        'label' => '巢狀文字',
                        'name'  => 'nest_text',
                        'type'  => 'text'
                    ],
                    [
                        'label' => '巢狀 Tagify',
                        'name'  => 'nest_tagify',
                        'type'  => 'tagify',
                        'options' => 'tags'
                    ]
                ]
            ],

            // gallery
            [
                'label' => '圖片集 Gallery',
                'name'  => 'test_gallery',
                'type'  => 'gallery',
                'width' => 800,
                'height' => 600,
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
    'pages' => [],

    /**
     * Dynamic templates (none for starter)
     */
    'templates' => [

        'template1' => [
            'label' => 'Template 1 — 多區塊單頁版型',

            'sections' => [

                'menu' => [
                    'label' => '選單設定',
                    'options' => [
                        [
                            'label' => '選單',
                            'name'  => 'menu_id',
                            'type'  => 'select',
                            'options' => 'menus',
                        ],
                    ],
                ],

                'hero' => [
                    'label' => 'Hero 區塊',
                    'options' => [
                        ['label' => '主標題',  'name' => 'title',     'type' => 'text'],
                        ['label' => '副標題',  'name' => 'subtitle',  'type' => 'text'],
                        ['label' => '背景圖片', 'name' => 'bg_image',  'type' => 'image', 'width' => 500, 'height' => 300],
                        ['label' => '文字顏色', 'name' => 'text_color', 'type' => 'color'],
                    ],
                ],

                'image_text' => [
                    'label' => '圖文區塊',
                    'options' => [
                        ['label' => '標題',  'name' => 'title',   'type' => 'text'],
                        ['label' => '說明',  'name' => 'desc',    'type' => 'textarea'],
                        ['label' => '圖片',  'name' => 'image',   'type' => 'image', 'width' => 600],
                    ],
                ],

                'example' => [
                    'label' => '所有欄位類型示範',
                    'options' => [

                        // 標題類
                        [
                            'label' => '主標題區',
                            'type'  => 'heading',
                            'description' => '測試標題區塊'
                        ],
                        [
                            'label' => '副標題區',
                            'type'  => 'sub_heading',
                            'description' => '測試副標題區塊'
                        ],

                        // 靜態圖片顯示
                        [
                            'label' => '靜態圖片顯示1',
                            'type'  => 'display_image',
                            'path'  => 'wncms/images/placeholders/upload.png',
                            'col'   => 4,
                        ],
                        [
                            'label'  => '靜態圖片顯示2',
                            'type'   => 'display_image',
                            'path'   => 'wncms/images/placeholders/upload.png',
                            'width'  => 300,
                            'height' => 120,
                        ],

                        // 隱藏欄位
                        [
                            'label' => '隱藏欄位',
                            'name'  => 'hidden_value',
                            'type'  => 'hidden',
                        ],

                        // 基本欄位
                        [
                            'label' => '文字輸入',
                            'name'  => 'test_text',
                            'type'  => 'text',
                        ],
                        [
                            'label' => '數字輸入',
                            'name'  => 'test_number',
                            'type'  => 'number',
                        ],
                        [
                            'label' => '多行文字',
                            'name'  => 'test_textarea',
                            'type'  => 'textarea',
                        ],
                        [
                            'label' => '顏色',
                            'name'  => 'test_color',
                            'type'  => 'color',
                        ],

                        // 圖片
                        [
                            'label' => '圖片上傳',
                            'name'  => 'test_image',
                            'type'  => 'image',
                            'width' => 500,
                        ],

                        // Boolean
                        [
                            'label' => '布林開關',
                            'name'  => 'test_boolean',
                            'type'  => 'boolean',
                        ],

                        // Editor
                        [
                            'label' => '富文本編輯器',
                            'name'  => 'test_editor',
                            'type'  => 'editor',
                        ],

                        // Select
                        [
                            'label'   => '選擇頁面',
                            'name'    => 'select_page',
                            'type'    => 'select',
                            'options' => 'pages',
                        ],
                        [
                            'label'   => '選擇選單',
                            'name'    => 'select_menu',
                            'type'    => 'select',
                            'options' => 'menus',
                        ],
                        [
                            'label'   => '選擇廣告位置',
                            'name'    => 'select_position',
                            'type'    => 'select',
                            'options' => 'positions',
                            'translate_option' => false,
                        ],
                        [
                            'label'   => '自訂下拉選項',
                            'name'    => 'select_custom',
                            'type'    => 'select',
                            'options' => ['one', 'two', 'three'],
                        ],

                        // Tagify
                        [
                            'label'   => 'Tagify Tags',
                            'name'    => 'tagify_tags',
                            'type'    => 'tagify',
                            'options' => 'tags',
                            'limit'   => 10,
                        ],
                        [
                            'label'   => 'Tagify Pages',
                            'name'    => 'tagify_pages',
                            'type'    => 'tagify',
                            'options' => 'pages',
                        ],
                        [
                            'label'   => 'Tagify Posts',
                            'name'    => 'tagify_posts',
                            'type'    => 'tagify',
                            'options' => 'posts',
                        ],
                        [
                            'label'   => 'Tagify 自訂陣列',
                            'name'    => 'tagify_custom',
                            'type'    => 'tagify',
                            'options' => [
                                ['value' => 'a', 'name' => 'A'],
                                ['value' => 'b', 'name' => 'B'],
                                ['value' => 'c', 'name' => 'C'],
                            ],
                        ],

                        // Inline 單組
                        [
                            'label' => 'Inline 單組',
                            'type'  => 'inline',
                            'sub_items' => [
                                ['label' => '文字', 'name' => 'inline_text', 'type' => 'text'],
                                ['label' => '數字', 'name' => 'inline_number', 'type' => 'number'],
                            ],
                        ],

                        // Inline 多組
                        [
                            'label' => 'Inline 多組',
                            'type'  => 'inline',
                            'repeat' => 3,
                            'sub_items' => [
                                ['label' => '文字R', 'name' => 'inline_r_text', 'type' => 'text'],
                                ['label' => '顏色R', 'name' => 'inline_r_color', 'type' => 'color'],
                            ],
                        ],

                        // Accordion 單組
                        [
                            'label'   => '手風琴單組',
                            'name'    => 'acc_single',
                            'type'    => 'accordion',
                            'repeat'  => 1,
                            'content' => [
                                ['label' => '標題', 'name' => 'acc_title', 'type' => 'text'],
                                ['label' => '圖片', 'name' => 'acc_image', 'type' => 'image'],
                                [
                                    'label' => '巢狀 Inline',
                                    'type'  => 'inline',
                                    'sub_items' => [
                                        ['label' => '子文字', 'name' => 'sub_t', 'type' => 'text'],
                                        ['label' => '子數字', 'name' => 'sub_n', 'type' => 'number'],
                                    ],
                                ],
                            ],
                        ],

                        // Accordion 多組 + 排序
                        [
                            'label'    => '手風琴多組＋可排序',
                            'name'     => 'acc_sortable',
                            'type'     => 'accordion',
                            'repeat'   => 3,
                            'sortable' => true,
                            'sub_items'  => [
                                ['label' => '巢狀文字', 'name' => 'nest_text', 'type' => 'text'],
                                [
                                    'label'   => '巢狀 Tagify',
                                    'name'    => 'nest_tagify',
                                    'type'    => 'tagify',
                                    'options' => 'tags',
                                ],
                            ],
                        ],

                    ],
                ],

            ],
        ],

    ],

    /**
     * Widgets (not required for basic starter)
     */
    'widgets' => [],
];
