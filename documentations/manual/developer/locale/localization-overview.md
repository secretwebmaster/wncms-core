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
