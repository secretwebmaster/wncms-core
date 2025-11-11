<?php


//Models

/**
 * 功能描述: 生成模組字段，支持多語言自動切換
 * @since 1.0.0
 * @version 5.5.6
 * @param string $model_name 模型名稱 (例如 user, users, novel, novels)，名稱需出現在 word.php 中
 * @param string|null $action 操作名稱 (management, create, edit, clone, list, index, select, screenshot, purchase)
 * @return string
 * @example wncms_model_word('user', 'create') → "新增用戶"
 */
if (!function_exists('wncms_model_word')) {
    function wncms_model_word(string $model_name, ?string $action = null): string
    {
        // Detect translation existence for model_name
        $model_translation_key = "wncms::word.{$model_name}";
        $translated_model_name = __($model_translation_key);

        // Fallback to plain text if translation is missing
        if ($translated_model_name === $model_translation_key) {
            $translated_model_name = ucfirst(str_replace('_', ' ', $model_name));
        }

        // If no action provided, just return the model name
        if (empty($action)) {
            return $translated_model_name;
        }

        // Handle the "model_xxx" translation pattern
        $action_key = "wncms::word.model_{$action}";
        $translated_action = __($action_key, ['model_name' => $translated_model_name]);

        // Fallback if action translation missing
        if ($translated_action === $action_key) {
            $translated_action = __("wncms::word.{$action}", ['model_name' => $translated_model_name]);
        }

        // Final fallback
        if ($translated_action === $action_key || empty($translated_action)) {
            return $translated_model_name . ' ' . ucfirst($action);
        }

        return $translated_action;
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

        $translated_model_name = __('wncms::word.' . $model_name);
        $translated_tag_type = __('wncms::word.' . $tag_type);
        
        return $translated_model_name . __('wncms::word.word_separator') . $translated_tag_type;
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
