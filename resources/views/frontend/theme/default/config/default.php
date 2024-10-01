<?php

/**
 * ----------------------------------------------------------------------------------------------------
 * ! Sterter Theme Setting
 * ----------------------------------------------------------------------------------------------------
 * @version 3.1.9
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
 * 主題名稱: 預設主題
 * 適用系統: 文尼CMSv3.1.9+
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
        'version' => '3.1.9',
        'created_at' => '2023-01-01',
        'updated_at' => '2023-04-15',
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
        '關於我們' => '/page/about',
        // '影片分類' => '/video/list/category',
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