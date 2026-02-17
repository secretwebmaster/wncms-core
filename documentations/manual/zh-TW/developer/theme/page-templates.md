# Theme 頁面模板

本頁說明 `resources/themes/{theme_id}/config.php` 中 `templates` 的建議格式，用於後台模板選項與前台模板渲染。

## 模板設定結構

建議使用以下結構：

```php
'templates' => [
    'testing' => [
        'label' => 'Testing Template',

        // 相容目前 PageManager::createDefaultThemeTemplatePages 的鍵
        'slug' => 'testing',
        'title' => 'Testing Template',
        'blade_name' => 'testing',

        'sections' => [
            'switch_test' => [
                'label' => 'Type Switch Test',
                'options' => [
                    ['label' => 'Type Switch Test', 'type' => 'heading'],
                    ['label' => 'Switch Target', 'name' => 'switch_target', 'type' => 'gallery'],
                ],
            ],
        ],
    ],
],
```

## 使用方式

1. 後台頁面編輯（`Backend\\PageController@edit`）讀取 `config("theme.{theme}.templates.{blade_name}.sections")` 來渲染模板選項。
2. 後台頁面更新（`Backend\\PageController@update`）使用同一份欄位映射來標準化並儲存值。
3. 前台頁面顯示（`Frontend\\PageController@show`）渲染：
   - `{$themeId}::pages.templates.{blade_name}`
4. `Page::option('{section}.{field}')` 用於讀取模板選項值。

## 必要 Blade 檔案

每個模板鍵都應有對應 Blade：

- 設定鍵：`templates.testing`
- 頁面 `blade_name`：`testing`
- Blade 路徑：`resources/themes/{theme_id}/views/pages/templates/testing.blade.php`

## Array -> Text 類型切換

當欄位從陣列型（`gallery`、`accordion`）改為標量型（`text`、`textarea`）時：

- 舊 JSON 資料會保留並以字串顯示在編輯表單。
- 編輯後儲存會轉換並以一般字串儲存。
- 此行為由 `Backend\\PageController@edit` 的值標準化邏輯處理。
