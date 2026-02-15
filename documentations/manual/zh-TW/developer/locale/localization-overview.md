# Localization 概述

WNCMS 包含強大的 **localization 系統**，讓你能夠在 **backend dashboard** 與 **frontend website** 中管理多種語言。此系統支援翻譯 models、views 與介面文字，非常適合建立多語言網站。

## 核心概念

### Locale

**locale** 代表一種語言或區域設定（例如 `en`、`zh_TW`、`ja`）。WNCMS 支援透過設定或 URL 前綴動態切換 locales。

### Translation Files

在 backend 或 frontend views 中使用的文字字串儲存在 **語言檔案** 中，位於：

```
resources/lang/{locale}/
```

例如：

```
resources/lang/en/word.php
resources/lang/zh_TW/word.php
```

每個檔案回傳一個關聯陣列的 key-value 對：

```php
return [
    'welcome' => 'Welcome to WNCMS',
    'save' => 'Save',
];
```

## 可翻譯的 Models

使用 `HasTranslations` trait 的 models 可以為 `title`、`content` 或 `description` 等欄位儲存翻譯值。
翻譯儲存在獨立的 `translations` 表中，包含以下欄位：

| 欄位       | 說明                           |
| ---------- | ------------------------------ |
| id         | Primary key                    |
| model_type | Eloquent model class 名稱      |
| model_id   | 相關 model 的 ID               |
| locale     | 語言代碼（例如 `en`、`zh_TW`） |
| key        | 被翻譯的欄位名稱               |
| value      | 翻譯值                         |

## 語言切換

WNCMS 支援透過 URL 前綴或程式化設定 locale 來切換 frontend 語言：

```php
app()->setLocale('zh_TW');
```

在 backend 中，管理員可以在 **Settings → Language** 選擇預設語言並啟用其他語言。

## 系統設定執行期覆寫

WNCMS 可透過 **Settings -> Translation** 在執行期覆寫 LaravelLocalization 設定。

支援鍵值：

- `app_locale`：用於 `app.locale` 與 LaravelLocalization 預設語言。
- `supported_locales`：逗號分隔的語言代碼（例如 `en,zh_TW,zh_CN,ja`）。
- `locales_order`：選填，逗號分隔的語言順序（例如 `zh_TW,zh_CN,en,ja`）。
- `use_accept_language_header`：對應 `laravellocalization.useAcceptLanguageHeader`。
- `hide_default_locale_in_url`：對應 `laravellocalization.hideDefaultLocaleInURL`。
- `use_locales_mapping`：啟用或停用執行期 `localesMapping`。

`WncmsServiceProvider` 中的執行期流程：

```php
config([
    'laravellocalization.supportedLocales' => $resolvedSupportedLocales,
    'laravellocalization.localesOrder' => $resolvedLocalesOrder,
    'laravellocalization.useAcceptLanguageHeader' => gss('use_accept_language_header', false),
    'laravellocalization.hideDefaultLocaleInURL' => gss('hide_default_locale_in_url', false),
]);
```

說明：

- `supported_locales` 只接受 `config/laravellocalization.php` 內已定義的語言代碼。
- 若設定的 `app_locale` 不在最終支援列表中，WNCMS 會回退到第一個可用語言。

## 整合點

- **Frontend Themes**：在 Blade templates 中使用 `@lang('wncms::word.xxx')`。
- **Backend Controllers**：在 PHP 程式碼中使用 `__('wncms::word.xxx')`。
- **Models**：透過 `HasTranslations` trait 自動擷取翻譯。

## 使用案例範例

1. 使用者在 backend 設定中選擇「Japanese」。
2. WNCMS 將全域 locale 更新為 `ja`。
3. 所有 views 自動從以下位置渲染 Japanese 翻譯：

   ```
   resources/lang/ja/
   ```

4. Models 會自動回傳翻譯的 `title` 與 `content`（若有的話）。

## 相關主題

- [Translation Files](./translation-files.md)
- [Add New Language](./add-new-language.md)
