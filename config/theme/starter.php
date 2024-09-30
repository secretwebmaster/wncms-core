<?php

/**
 * ----------------------------------------------------------------------------------------------------
 * ! Sterter Theme Setting
 * ----------------------------------------------------------------------------------------------------
 * @version 3.1.15
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
 *              id                  string          | optional  | If use accordion, must pass id to specify the controling accordion
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
 *                  accordion: Load item into accordion to avoid scroll party. Can use repeat. Must input id
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
 * 適用系統: 文尼CMSv3.1.7+
 * ----------------------------------------------------------------------------------------------------
 */
return [

    /**
     * ----------------------------------------------------------------------------------------------------
     * Theme info
     * ----------------------------------------------------------------------------------------------------
     */
    'info' => [
        'id' => 'starter',
        'type' => 'blog',
        'name' => [
            'zh_TW' => 'Starer主題',
            'zh_CN' => 'Starer主题',
            'en' => 'Start Theme',
        ],
        'description' => [
            'zh_TW' => 'Hello World!',
            'zh_CN' => 'Hello World!',
            'en' => 'Hello World!',
        ],
        'author' => '文尼先生',
        'version' => '3.1.7',
        'created_at' => '2023-01-04',
        'updated_at' => '2023-11-14',
        'demo_url' => 'https://wncms.cc/',
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
            // [
            //     'label'=>'搜索框背景',
            //     'name'=>'search_background',
            //     'type'=>'image',
            //     'description'=>'上傳圖片'
            // ],
            // [
            //     'label'=>'公告內容',
            //     'name'=>'anouncement',
            //     'type'=>'text',
            //     'description'=>'支持html'
            // ],
        ],

        //! 頁首
        'header' => [
            [
                'label'=>'頁首',
                'type'=>'heading',
            ],
            [
                'label'=>'頁首菜單',
                'type'=>'select',
                'name' => 'header_menu',
                'options'=>'menus',
            ],
            // [
            //     'label'=>'桌面版輪播圖寬高比',
            //     'type'=>'select',
            //     'name' => 'desktop_carousel_aspect_ratio',
            //     'translate_option' => false,
            //     'options' => [
            //         '21/9',
            //         '16/10',
            //         '16/9',
            //         '5/1',
            //         '4/3',
            //         '4/1',
            //         '3/1',
            //         '2/1',
            //         '1/1',
            //     ]
            // ],
            // [
            //     'label'=>'手機版輪播圖寬高比',
            //     'type'=>'select',
            //     'name' => 'mobile_carousel_aspect_ratio',
            //     'translate_option' => false,
            //     'options' => [
            //         '21/9',
            //         '16/10',
            //         '16/9',
            //         '5/1',
            //         '4/3',
            //         '4/1',
            //         '3/1',
            //         '2/1',
            //         '1/1',
            //     ]
            // ],
            // [
            //     'label'=>'輪播圖圖片填充方式',
            //     'type'=>'select',
            //     'name' => 'carousel_object_fit',
            //     'options' => [
            //         'fill',
            //         'cover',
            //         'contain',
            //         'auto',
            //     ]
            // ],
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
            [
                'label'=>'關於本站內容',
                'name'=>'about_content',
                'type'=>'editor',
            ],
            [
                'label'=>'頁腳菜單1標題',
                'type'=>'text',
                'name' => 'footer_menu_title_1',
            ],
            [
                'label'=>'頁腳菜單1',
                'type'=>'select',
                'name' => 'footer_menu_1',
                'options'=>'menus',
            ],
            [
                'label'=>'頁腳菜單2標題',
                'type'=>'text',
                'name' => 'footer_menu_title_2',
            ],
            [
                'label'=>'頁腳菜單2',
                'type'=>'select',
                'name' => 'footer_menu_2',
                'options'=>'menus',
            ],
            [
                'label'=>'頁腳菜單3標題',
                'type'=>'text',
                'name' => 'footer_menu_title_3',
            ],
            [
                'label'=>'頁腳菜單3',
                'type'=>'select',
                'name' => 'footer_menu_3',
                'options'=>'menus',
            ],
            [
                'label'=>'頁腳菜單4標題',
                'type'=>'text',
                'name' => 'footer_menu_title_4',
            ],
            [
                'label'=>'頁腳菜單4',
                'type'=>'select',
                'name' => 'footer_menu_4',
                'options'=>'menus',
            ],
        ],

        //! 首頁
        'homepage' => [
            [
                'label'=>'首頁',
                'type'=>'heading',
            ],
            [
                'label' => '首頁顯示分類',
                'name' => 'home_categories',
                'type' => 'tagify',
                'options' => 'tags',
                'tag_type' => 'post_category',

            ],
            // [
            //     'label'=>'演示Inline項目',
            //     'type'=>'sub_heading',
            //     'description'=>'只支持font-awsome v6 icon <a href="https://fontawesome.com/icons" target="_blank">https://fontawesome.com/icons</a>',
            // ],
            // [
            //     'type' => 'inline',
            //     'sub_items' => [
            //         ['label' => '標題1','name' => 'count_up_title_1','type' => 'text', 'align_items_center' => true],
            //         ['label' => '圖標1','name' => 'count_up_icon_1','type' => 'text', 'align_items_center' => true],
            //         ['label' => '數值1','name' => 'count_up_value_1','type' => 'text', 'align_items_center' => true],
            //     ],
            // ],
            // [
            //     'type' => 'inline',
            //     'sub_items' => [
            //         ['label' => '標題2','name' => 'count_up_title_2','type' => 'text', 'align_items_center' => true],
            //         ['label' => '圖標2','name' => 'count_up_icon_2','type' => 'text', 'align_items_center' => true],
            //         ['label' => '數值2','name' => 'count_up_value_2','type' => 'text', 'align_items_center' => true],
            //     ],
            // ],
            // [
            //     'type' => 'inline',
            //     'sub_items' => [
            //         ['label' => '標題3','name' => 'count_up_title_3','type' => 'text', 'align_items_center' => true],
            //         ['label' => '圖標3','name' => 'count_up_icon_3','type' => 'text', 'align_items_center' => true],
            //         ['label' => '數值3','name' => 'count_up_value_3','type' => 'text', 'align_items_center' => true],
            //     ],
            // ],
            // [
            //     'type' => 'inline',
            //     'sub_items' => [
            //         ['label' => '標題4','name' => 'count_up_title_4','type' => 'text', 'align_items_center' => true],
            //         ['label' => '圖標4','name' => 'count_up_icon_4','type' => 'text', 'align_items_center' => true],
            //         ['label' => '數值4','name' => 'count_up_value_4','type' => 'text', 'align_items_center' => true],
            //     ],
            // ],
            // [
            //     'label'=>'底部推薦文章',
            //     'type'=>'sub_heading',
            // ],
            // [
            //     'type' => 'inline',
            //     'label' => '首頁顯示分類',
            //     'name' => 'home_buttom_posts',
            //     'type' => 'tagify',
            //     'options' => 'posts',
            //     'limit' => 4,

            // ],
        ],

        //! 文章
        'posts' => [
            [
                'label'=>'文章',
                'type'=>'heading',
            ],
            [
                'label'=>'文章簡介長度',
                'type'=>'number',
                'name' => 'post_excerpt_length',
            ],
            [
                'label'=>'懶加載圖',
                'name'=>'thumbnail_placeholder',
                'type'=>'image',
                'width' => 200,
            ],
            // [
            //     'label'=>'文章封面寬高比',
            //     'type'=>'select',
            //     'name' => 'post_thumbnail_aspect_ratio',
            //     'translate_option' => false,
            //     'description' => '預設為16/9',
            //     'options' => [
            //         '21/9',
            //         '16/10',
            //         '16/9',
            //         '5/1',
            //         '4/3',
            //         '4/1',
            //         '3/1',
            //         '2/1',
            //         '1/1',
            //     ]
            // ],
            // [
            //     'label'=>'文章封面填充方式',
            //     'type'=>'select',
            //     'name' => 'post_thumbnail_object_fit',
            //     'description' => '預設為填滿',
            //     'options' => [
            //         'fill',
            //         'cover',
            //         'contain',
            //         'auto',
            //     ]
            // ],
            [
                'label'=>'假瀏覽數',
                'type'=>'heading',
            ],
            [
                'label'=>'開啟假瀏覽數',
                'type'=>'boolean',
                'name' => 'fake_views',
                'description' => '生成公式為 (真實瀏覽數 * 總瀏覽系數) + (文章ID * 文章ID系數) + (標題長凌 * 標題長度系數) ，系數愈大，生成的假瀏覽量數值愈大',
            ],
            [
                'type' => 'inline',
                'sub_items' => [
                    ['label' => '總瀏覽系數','name' => 'fake_view_factor_view_total','type' => 'number', 'align_items_center' => true, 'placeholder' => '預設值為 37'],
                    ['label' => '文章ID系數','name' => 'fake_view_factor_id','type' => 'number', 'align_items_center' => true, 'placeholder' => '預設值為 77'],
                    ['label' => '標題長度系數','name' => 'fake_view_factor_title','type' => 'number', 'align_items_center' => true, 'placeholder' => '預設值為 1107'],
                ],
            ],

        ],

        //! 頁面
        'pages' => [
            [
                'label'=>'頁面',
                'type'=>'heading',
            ],
        ],

        //! 資訊
        'site_info' => [
            [
                'label'=>'資訊',
                'type'=>'heading',
            ],
            [
                'label'=>'發佈地址1',
                'name'=>'publish_url_1',
                'description'=>'輸入完整網址，帶http',
                'type'=>'text',
            ],
            [
                'label'=>'發佈地址2',
                'name'=>'publish_url_2',
                'description'=>'輸入完整網址，帶http',
                'type'=>'text',
            ],
            [
                'label'=>'聯絡Email',
                'name'=>'contact_email',
                'type'=>'text',
            ],
            [
                'label'=>'聯絡Tel',
                'name'=>'contact_tel',
                'type'=>'text',
            ],
            [
                'label'=>'聯絡Telegram',
                'name'=>'contact_telegram',
                'type'=>'text',
                'description'=>'不需要http，不需要@，只需輸入用戶名部分',
            ],
            [
                'label'=>'聯絡Line',
                'name'=>'contact_line',
                'type'=>'text',
            ],
            [
                'label'=>'聯絡Whatsapp',
                'name'=>'contact_whatsapp',
                'type'=>'text',
            ],
            [
                'label'=>'聯絡Skype',
                'name'=>'contact_skype',
                'type'=>'text',
            ],
            [
                'label'=>'聯絡Faccbook',
                'name'=>'contact_facebook',
                'type'=>'text',
            ],
            [
                'label'=>'聯絡Twitter',
                'name'=>'contact_twitter',
                'type'=>'text',
            ],
            [
                'label'=>'聯絡YouTube',
                'name'=>'contact_youtube',
                'type'=>'text',
            ],
            [
                'label'=>'聯絡Instagram',
                'name'=>'contact_instagram',
                'type'=>'text',
            ],
        ],

        //! 自訂代碼
        'custom_code' => [
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

        // 'example' => [
            //text
            //textarea
            //accordion
            // [
            //     'label'=>'What is區塊卡片項目',
            //     'type'=>'accordion',
            //     'id' => 'what_is_block_item',
            //     'repeat' => 6,
            //     'content' => [
            //         [
            //             'type' => 'inline',
            //             'sub_items' => [
            //                 ['label' => '顯示此卡片','name' => 'show_what_is_block_item','type' => 'boolean', 'align_items_center' => true],
            //             ],
            //         ],
            //         [
            //             'type' => 'inline',
            //             'sub_items' => [
            //                 ['label' => '強調標題','name' => 'what_is_block_item_title_strong','type' => 'text', 'align_items_center' => true],
            //                 ['label' => '標題','name' => 'what_is_block_item_title','type' => 'text', 'align_items_center' => true],
            //                 ['label' => '副標題','name' => 'what_is_block_item_description','type' => 'text', 'align_items_center' => true],
            //             ],
            //         ],
            //         [
            //             'type' => 'inline',
            //             'sub_items' => [
            //                 ['label' => '子標題A','name' => 'what_is_block_item_sub_title_a','type' => 'text', 'align_items_center' => true],
            //             ],
            //         ],
            //         [
            //             'type' => 'inline',
            //             'sub_items' => [
            //                 ['label' => '子內容A','name' => 'what_is_block_item_sub_content_a','type' => 'editor', 'align_items_center' => true],
            //             ],
            //         ],
            //         [
            //             'type' => 'inline',
            //             'sub_items' => [
            //                 ['label' => '子標題B','name' => 'what_is_block_item_sub_title_b','type' => 'text', 'align_items_center' => true],
            //             ],
            //         ],
            //         [
            //             'type' => 'inline',
            //             'sub_items' => [
            //                 ['label' => '子內容B','name' => 'what_is_block_item_sub_content_b','type' => 'editor', 'align_items_center' => true],
            //             ],
            //         ],
            //         [
            //             'type' => 'inline',
            //             'sub_items' => [
            //                 ['label' => '圖標A圖片','name' => 'what_is_block_item_icon_a_image','type' => 'image', 'width' => 100, 'height' => 100, 'align_items_center' => true],
            //                 ['label' => '圖標A文字','name' => 'what_is_block_item_icon_a_text','type' => 'text', 'align_items_center' => true],
            //                 ['label' => '圖標A連結','name' => 'what_is_block_item_icon_a_url','type' => 'text', 'align_items_center' => true],
            //             ],
            //         ],
            //         [
            //             'type' => 'inline',
            //             'sub_items' => [
            //                 ['label' => '圖標B圖片','name' => 'what_is_block_item_icon_b_image','type' => 'image', 'width' => 100, 'height' => 100, 'align_items_center' => true],
            //                 ['label' => '圖標B文字','name' => 'what_is_block_item_icon_b_text','type' => 'text', 'align_items_center' => true],
            //                 ['label' => '圖標B連結','name' => 'what_is_block_item_icon_b_url','type' => 'text', 'align_items_center' => true],
            //             ],
            //         ],
            //     ]
            // ],
        // ],

    ],

    /**
     * ----------------------------------------------------------------------------------------------------
     * Default values for theme options
     * ----------------------------------------------------------------------------------------------------
     */
    'default' => [
        'search_placeholder' => '輸入關鍵字搜索',
    ],

    /**
     * ----------------------------------------------------------------------------------------------------
     * Static pages (only one page)
     * ----------------------------------------------------------------------------------------------------
     */
    'pages' => [
        // '關於我們' => '/page/about',
        // '影片分類' => '/video/list/category',
    ],

    /**
     * ----------------------------------------------------------------------------------------------------
     * Dynamic pages 
     * User can create multiple pages using the same template and edit values. But cannot add, move or delete blocks
     * ----------------------------------------------------------------------------------------------------
     * theme_template_name (blade name, page slug)= > page title
     * 
     * 'slug' => 'terms',
     * 'title' => 'Terms and Conditions'
     * 'blade_name => 'terms'
     * means create a page with slug 'terms' and title "Terms and Conditions" using blade resource/view/{themeName}/pages/terms.blade.php
     * ----------------------------------------------------------------------------------------------------
     */
    'templates' => [
        // 'about' => [
        //     'slug' => 'about',
        //     'title' => '關於我們',
        //     'blade_name' => 'about',
        //     'widgets' => ['block_1', 'block_2']
        // ],
        // 'technology' => [
        //     'slug' => 'technology',
        //     'title' => '產品技術',
        //     'blade_name' => 'technology',
        //     'widgets' => ['page_menu', 'block_8', 'block_15', 'block_5', 'block_16', 'block_18', 'block_19', 'block_20', 'block_21', 'block_22', 'block_23', 'block_24'],
        // ],
    ],

    /**
     * ----------------------------------------------------------------------------------------------------
     * Widgets
     * To be loaded in page templates
     * ----------------------------------------------------------------------------------------------------
     */
    'widgets' => [
        // 'page_menu' => [
        //     'name' => '頁面菜單',
        //     'key' => 'page_menu',
        //     'icon' => 'fa-solid fa-bar',
        //     'fields' => [
        //         ['label' => '菜單', 'name' => 'page_menu', 'type' => 'select', 'options' => 'menus'],
        //     ],
        // ],
        // 'block_1' => [
        //     'name' => '區塊1',
        //     'key' => 'block_1',
        //     'icon' => 'fa-solid fa-newspaper',
        //     'fields' => [
        //         ['label' => '演示圖', 'type' => 'display_image', 'path' => '/theme/technology/images/template_screenshots/block_1_screenshot.png', 'width' => '800px'],
        //         ['label' => '標題1', 'name' => 'block_1_title_1', 'type' => 'text'],
        //         ['label' => '標題2', 'name' => 'block_1_title_2', 'type' => 'text'],
        //         ['label' => '背景文字', 'name' => 'block_1_background_text', 'type' => 'text'],
        //         ['label' => '描述', 'name' => 'block_1_description', 'type' => 'textarea'],
        //         ['label' => '圖片 (787x553)', 'name' => 'block_1_image', 'type' => 'image', 'width' => 350, 'height' => 246],
        //     ],
        // ],
    ],
    
];
