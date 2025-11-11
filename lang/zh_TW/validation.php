<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 驗證語言行
    |--------------------------------------------------------------------------
    */

    'required' => ':attribute 為必填欄位。',
    'unique' => ':attribute 已被使用。',
    'numeric' => ':attribute 必須是數字。',
    'min' => [
        'numeric' => ':attribute 必須大於或等於 :min。',
        'string'  => ':attribute 至少需有 :min 個字元。',
    ],
    'max' => [
        'numeric' => ':attribute 不可大於 :max。',
        'string'  => ':attribute 不可超過 :max 個字元。',
    ],
    'between' => [
        'numeric' => ':attribute 必須介於 :min 至 :max 之間。',
        'string'  => ':attribute 字數需介於 :min 至 :max 之間。',
    ],
    'in' => ':attribute 的值無效。',
    'not_in' => '所選的 :attribute 無效。',
    'date' => ':attribute 不是有效的日期。',
    'after_or_equal' => ':attribute 必須在 :date 之後或相同日期。',
    'before_or_equal' => ':attribute 必須在 :date 之前或相同日期。',
    'email' => ':attribute 必須是有效的電子郵件地址。',
    'url' => ':attribute 必須是有效的網址。',
    'boolean' => ':attribute 欄位必須是 true 或 false。',
    'confirmed' => ':attribute 確認不相符。',
    'array' => ':attribute 必須是陣列。',
    'string' => ':attribute 必須是字串。',
    'integer' => ':attribute 必須是整數。',
    'exists' => '所選的 :attribute 不存在。',
    'image' => ':attribute 必須是圖片。',
    'mimes' => ':attribute 的檔案類型必須是：:values。',
    'max.file' => ':attribute 檔案大小不可超過 :max KB。',
    'min.file' => ':attribute 檔案大小至少需 :min KB。',

    /*
    |--------------------------------------------------------------------------
    | 自定義屬性名稱
    |--------------------------------------------------------------------------
    */

    'attributes' => [],

];
