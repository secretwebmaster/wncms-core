# Localization Overview

WNCMS includes a powerful **localization system** that allows you to manage multiple languages across both the **backend dashboard** and the **frontend website**. This system supports translating models, views, and interface texts, making it ideal for building multilingual websites.

## Core Concepts

### Locale

A **locale** represents a language or regional setting (e.g., `en`, `zh_TW`, `ja`). WNCMS supports switching locales dynamically through settings or URL prefixes.

### Translation Files

Text strings used in the backend or frontend views are stored in **language files** located under:

```

resources/lang/{locale}/

```

For example:

```

resources/lang/en/word.php
resources/lang/zh_TW/word.php

```

Each file returns an associative array of key-value pairs:

```php
return [
    'welcome' => 'Welcome to WNCMS',
    'save' => 'Save',
];
```

## Translatable Models

Models that use the `HasTranslations` trait can store translated values for fields like `title`, `content`, or `description`.
Translations are stored in a separate `translations` table with columns like:

| Column     | Description                        |
| ---------- | ---------------------------------- |
| id         | Primary key                        |
| model_type | Eloquent model class name          |
| model_id   | Related model’s ID                 |
| locale     | Language code (e.g. `en`, `zh_TW`) |
| key        | Field name being translated        |
| value      | Translated value                   |

## Language Switching

WNCMS supports switching frontend languages through URL prefixes or by setting the locale programmatically:

```php
app()->setLocale('zh_TW');
```

In the backend, the admin can select the default language and enable additional languages in **Settings → Language**.

## System Settings Runtime Overrides

WNCMS can override LaravelLocalization config at runtime from **Settings -> Translation**.

Supported keys:

- `app_locale`: default locale used for `app.locale` and LaravelLocalization default locale.
- `supported_locales`: comma-separated locale keys (for example `en,zh_TW,zh_CN,ja`).
- `locales_order`: optional comma-separated locale order (for example `zh_TW,zh_CN,en,ja`).
- `use_accept_language_header`: maps to `laravellocalization.useAcceptLanguageHeader`.
- `hide_default_locale_in_url`: maps to `laravellocalization.hideDefaultLocaleInURL`.
- `use_locales_mapping`: enables or disables runtime `localesMapping`.

Runtime flow in `WncmsServiceProvider`:

```php
config([
    'laravellocalization.supportedLocales' => $resolvedSupportedLocales,
    'laravellocalization.localesOrder' => $resolvedLocalesOrder,
    'laravellocalization.useAcceptLanguageHeader' => gss('use_accept_language_header', false),
    'laravellocalization.hideDefaultLocaleInURL' => gss('hide_default_locale_in_url', false),
]);
```

Notes:

- `supported_locales` only accepts locale keys that already exist in `config/laravellocalization.php`.
- If configured `app_locale` is not in the resolved supported list, WNCMS falls back to the first supported locale.

## Integration Points

- **Frontend Themes**: Use `@lang('wncms::word.xxx')` in Blade templates.
- **Backend Controllers**: Use `__('wncms::word.xxx')` in PHP code.
- **Models**: Retrieve translations automatically via `HasTranslations` trait.

## Example Use Case

1. User selects “Japanese” in the backend settings.
2. WNCMS updates the global locale to `ja`.
3. All views automatically render Japanese translations from:

   ```
   resources/lang/ja/
   ```

4. Models automatically return the translated `title` and `content` if available.

## Related Topics

- [Translation Files](./translation-files.md)
- [Add New Language](./add-new-language.md)
