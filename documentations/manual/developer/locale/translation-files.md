# Translation Files

WNCMS merges your project’s overrides into its core translations. The core `wncms-core` package exposes a `word.php` group, and at runtime it will **merge** your site’s `lang/{locale}/custom.php` on top of it. You do not need to edit vendor files.

## What you should create

Create a `custom.php` file for each locale you support:

```
lang/
├── en/
│   └── custom.php
├── zh_CN/
│   └── custom.php
├── zh_TW/
│   └── custom.php
└── ja/
    └── custom.php
```

## Minimal example

`lang/en/custom.php`

```php
<?php

$custom_words = [
    'welcome' => 'Welcome to My Site', // override existing key if present
    'contact' => 'Get in Touch',       // add new key
];

return $custom_words;
```

## How the merge works

- WNCMS loads its core `word.php` (from the `wncms-core` package).
- Then it loads `lang/{locale}/custom.php` from your app and **merges** it.
- Keys in `custom.php` **override** same-named keys provided by WNCMS.
- New keys in `custom.php` become available under the same namespace.

## How to reference keys

Recommended (merged group, includes your overrides):

```blade
@lang('wncms::word.welcome')
@lang('wncms::word.contact')
```

You can still use the traditional Laravel group if you want to point directly at your file:

```blade
@lang('custom.contact')
```

Both methods work; `wncms::word.*` is preferred because it always reflects the merged result.

## Per-locale behavior

Create one `custom.php` per locale you actually serve:

- `lang/en/custom.php`
- `lang/zh_CN/custom.php`
- `lang/zh_TW/custom.php`
- `lang/ja/custom.php`

Only keys present in a locale’s `custom.php` will override that locale’s text.

## Notes

- Keep vendor defaults in the package; keep **site/theme–specific** wording in your `custom.php`.
- Maintain consistent keys across locales to avoid missing translations.
- After changing language files in production, clear caches if needed:

  ```
  php artisan cache:clear
  php artisan config:clear
  ```
