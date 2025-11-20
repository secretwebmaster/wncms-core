<?php

if (!defined('WNCMS_THEME_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}

/**
 * ----------------------------------------------------------------------------------------------------
 * ! Sterter Theme Setting
 * ----------------------------------------------------------------------------------------------------
 * 
 * Available array key names: 
 *      info
 *          label
 *          name
 *          author
 *          description
 *          version
 *          created_at
 *          updated_at
 * 
 *      option_tabs
 *          {key_names} => {key_valus}
 *          label
 *          align_items_center
 *          description
 *          translate_option
 *          sub_items
 *          limit
 *          required
 *          disabled
 *          options
 *          tag_type
 *          whitelist_tag_only
 *          repeat
 *          type
 *              text
 *              number
 *              image
 *              select
 *              boolean
 *              editor
 *              textarea
 *              color
 *              checkbox
 *              radio
 *              heading
 *              sub_heading
 *              inline
 *              tagify
 *              accordion
 * 
 *      default
 *          {key_name} => {key_value}
 * 
 * 
 * 
 * ----------------------------------------------------------------------------------------------------
 *  ! Description and format of each key
 * ----------------------------------------------------------------------------------------------------
 *  info
 *      label Theme name display.               | 文尼Starter主題
 *      name Theme name for sytem use.          | starter                       | Should be the same as all theme path root name
 *      author Theme author.                    | 文尼先生
 *      description Theme description.          | 文尼主題最壹款簡潔易用的主題
 *      version Theme version with 3 numbers.   | 3.0.1
 *      created_at Date of theme first created  | 2023-01-01
 *      updated_at Date of theme last updateed  | 2023-12-31
 * 
 *  option_tabs
 *      key_names => nested options array
 *          Example:
 *          'general' => [
 *              [
 *                  'label'=>'通用',
 *                  'type'=>'heading',
 *              ],
 *              [
 *                  'label'=>'搜索框文字',
 *                  'name'=>'search_placeholder',
 *                  'type'=>'text',
 *                  'description'=>'搜索框沒有內容時的替代內容',
 *              ],
 *          ],
 * 
 *          option array
 *              label               string          | required  |
 *              name                string          | required  | key which store in database and called in blade file with gto('name')
 *              align_items_center  boolean         | optional  | To display input fields and label vertically centerd
 *              description         string          | optional  | Description of the option item below label
 *              translate_option    boolean         | optional  | Set to false and disable translation. Default is true
 *              sub_items           array           | optional  |
 *              limit               integer         | optional  | Limit for multi-selection options such as checkboxes and tagify
 *              required            boolean         | optional  | Set to required to force user input before submit. Suggest to have a default value
 *              disabled            boolean         | optional  | Disable input but show the existing value. Suggest to have a default value
 *              options             array|string    | optional  | Supported value: menus,posts,pages,tags,positions
 *                  Only required is type = 'select' or 'tagify
 *                  array use for type = 'select'
 *                  string use for type = 'tagify'. List of model will be shown in dropdown. Max 100 items will be loaded.
 *              tag_type            string          | optional  | Use to specify type of Tag model. E.g. post_category. Use when option = 'tags'
 *              whitelist_tag_only  string          | optional  | Restrict user to input custom tags. Default is true.
 *              repeat              interger        | optional  | To repeat self option with auto increased index from 1. Only applicable to accordion and inline
 *              content             array           | optional  | Required for accordion. Sub-items Should use inline 
 *              type                string          | required  | Specify the input type of the theme options
 * 
 *                  text: Simple text input
 *                  number: Number only
 *                  image: Upload image, attach to website model and store the generated image url 
 *                  select: Select input. Require to have field 'options'
 *                  boolean: 
 *                  editor: Load TinyMCE editor
 *                  textarea: 
 *                  color: 
 *                  checkbox: (Not availabel yet)
 *                  radio: (Not availabel yet)
 *                  heading: Display a text title. No name field is required. Will not store in database. Can have a description
 *                  sub_heading: Display a text title without background. No name field is required. Will not store in database. Can have a description
 *                  inline: Display multiple items in same row. Useful when there is data set of text, num, image for the same item, can use repeat
 *                  tagify: Load tagify. Require to have field 'options'. Required to have 'tag_type' if options is 'tags'
 *                  accordion: Load item into accordion to avoid scroll party. Can use repeat
 * 
 * 
 *  default
 *      key_names => value
 *      Will apply theem default values if a website first time install the theme
 *      Will reset to these default values if user resets a website
 * ----------------------------------------------------------------------------------------------------
 */





 /** 
 * ----------------------------------------------------------------------------------------------------
 * 主題名稱: starter
 * 適用系統: 文尼CMS v6.0.0+
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
