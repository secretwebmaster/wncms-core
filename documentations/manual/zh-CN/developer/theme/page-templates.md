# Theme 页面模板

本页说明 `resources/themes/{theme_id}/config.php` 中 `templates` 的建议格式，用于后台模板选项与前台模板渲染。

## 模板配置结构

建议使用以下结构：

```php
'templates' => [
    'testing' => [
        'label' => 'Testing Template',

        // 兼容当前 PageManager::createDefaultThemeTemplatePages 的键
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

1. 后台页面编辑（`Backend\\PageController@edit`）读取 `config("theme.{theme}.templates.{blade_name}.sections")` 来渲染模板选项。
2. 后台页面更新（`Backend\\PageController@update`）使用同一份字段映射来标准化并保存值。
3. 前台页面显示（`Frontend\\PageController@show`）渲染：
   - `{$themeId}::pages.templates.{blade_name}`
4. `Page::option('{section}.{field}')` 用于读取模板选项值。

## 必要 Blade 文件

每个模板键都应有对应 Blade：

- 配置键：`templates.testing`
- 页面 `blade_name`：`testing`
- Blade 路径：`resources/themes/{theme_id}/views/pages/templates/testing.blade.php`

## Array -> Text 类型切换

当字段从数组型（`gallery`、`accordion`）改为标量型（`text`、`textarea`）时：

- 旧 JSON 数据会保留并以字符串显示在编辑表单中。
- 编辑后保存会转换并以普通字符串保存。
- 该行为由 `Backend\\PageController@edit` 的值标准化逻辑处理。
