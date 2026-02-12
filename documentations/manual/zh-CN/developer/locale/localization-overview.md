# Localization 概述

WNCMS 包含强大的 **localization 系统**，让你能够在 **backend dashboard** 与 **frontend website** 中管理多种语言。此系统支援翻译 models、views 与介面文字，非常适合建立多语言网站。

## 核心概念

### Locale

**locale** 代表一种语言或区域设定（例如 `en`、`zh_TW`、`ja`）。WNCMS 支援透过设定或 URL 前缀动态切换 locales。

### Translation Files

在 backend 或 frontend views 中使用的文字字串储存在 **语言档案** 中，位于：

```
resources/lang/{locale}/
```

例如：

```
resources/lang/en/word.php
resources/lang/zh_TW/word.php
```

每个档案回传一个关联阵列的 key-value 对：

```php
return [
    'welcome' => 'Welcome to WNCMS',
    'save' => 'Save',
];
```

## 可翻译的 Models

使用 `HasTranslations` trait 的 models 可以为 `title`、`content` 或 `description` 等栏位储存翻译值。
翻译储存在独立的 `translations` 表中，包含以下栏位：

| 栏位       | 说明                           |
| ---------- | ------------------------------ |
| id         | Primary key                    |
| model_type | Eloquent model class 名称      |
| model_id   | 相关 model 的 ID               |
| locale     | 语言代码（例如 `en`、`zh_TW`） |
| key        | 被翻译的栏位名称               |
| value      | 翻译值                         |

## 语言切换

WNCMS 支援透过 URL 前缀或程式化设定 locale 来切换 frontend 语言：

```php
app()->setLocale('zh_TW');
```

在 backend 中，管理员可以在 **Settings → Language** 选择预设语言并启用其他语言。

## 整合点

- **Frontend Themes**：在 Blade templates 中使用 `@lang('wncms::word.xxx')`。
- **Backend Controllers**：在 PHP 程式码中使用 `__('wncms::word.xxx')`。
- **Models**：透过 `HasTranslations` trait 自动撷取翻译。

## 使用案例范例

1. 使用者在 backend 设定中选择「Japanese」。
2. WNCMS 将全域 locale 更新为 `ja`。
3. 所有 views 自动从以下位置渲染 Japanese 翻译：

   ```
   resources/lang/ja/
   ```

4. Models 会自动回传翻译的 `title` 与 `content`（若有的话）。

## 相关主题

- [Translation Files](./translation-files.md)
- [Add New Language](./add-new-language.md)
