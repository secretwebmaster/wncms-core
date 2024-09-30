<?php


//Models

/**
 * 功能描述: 生成模組字段，支持多語言自動切換
 * @since 1.0.0
 * @version 3.0.0
 * @param string $model_name Eloqent Model table name，例如 user, users，名稱需要出現在 word.php 中
 * @param string|null $action 操作名稱，支持 management, select, screenshot, list, index, create, edit, clone, purchase ，名稱需要出現在 word.php 中
 * @return string
 * @example wncms_model_word('user', 'create')
 * @output 新增用戶
 */
if (!function_exists('wncms_model_word')) {
    function wncms_model_word($model_name, $action = null): string
    {
        return __("word.model_{$action}", ["model_name" => __("word.{$model_name}")]);
    }
}

/**
 * 功能描述: 生成分類法名稱，支持多語言自動切換
 * @since 1.0.0
 * @version 3.0.0
 * @param string $tag_name 包括model name的完整 Tag type，例如 post_tag, video_category
 * @return string
 * @example wncms_tag_word('post_tag')
 * @output 文章標籤
 */
if (!function_exists('wncms_tag_word')) {
    function wncms_tag_word(string $tag_name): string
    {
        $parts = explode('_', $tag_name);
        $tag_type = array_pop($parts); // Get and remove the last part as the tag type
        $model_name = implode('_', $parts); // Join the remaining parts as the model name

        $translated_model_name = __('word.' . $model_name);
        $translated_tag_type = __('word.' . $tag_type);
        
        return $translated_model_name . __('word.word_separator') . $translated_tag_type;
    }
}

/**
 * 功能描述: 從數據庫table中獲取Model名稱
 * @since 1.0.0
 * @version 3.0.0
 * @param string $table_name MySQL中的table名稱
 * @return string
 * @example wncms_get_model_name_from_table_name('posts')
 * @output Post
 */
if (!function_exists('wncms_get_model_name_from_table_name')) {
    function wncms_get_model_name_from_table_name($table_name): string
    {
        // Convert the table name to its singular form
        $singular = str()->singular($table_name);

        // Capitalize the first letter to get the model name
        $modelName = str()->studly($singular);

        return $modelName;
    }
}
