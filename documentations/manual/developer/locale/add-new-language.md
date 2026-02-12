# Add New Language

WNCMS uses **Laravel Localization** to handle multilingual routing and interface text. To add a new language, developers can simply create a configuration file and corresponding language directory—no publish command is needed.

## Step 1: Copy the Localization Config

Create a new file in your project:

```
config/laravellocalization.php
```

You can copy this file directly from the `wncms-core` package:

```
vendor/secretwebmaster/wncms-core/config/laravellocalization.php
```

## Step 2: Enable or Add Your Language

Inside your copied `config/laravellocalization.php`, find the `supportedLocales` array.

Uncomment or add the language you need.
For example, to add **Korean** support:

```php
'supportedLocales' => [
    'zh_TW' => ['name' => 'Chinese (Traditional)', 'script' => 'Hant', 'native' => '繁體中文', 'regional' => 'zh_TW'],
    'en'    => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
    'ja'    => ['name' => 'Japanese', 'script' => 'Jpan', 'native' => '日本語', 'regional' => 'ja_JP'],
    'ko'    => ['name' => 'Korean', 'script' => 'Hang', 'native' => '한국어', 'regional' => 'ko_KR'], // newly added
],
```

## Step 3: Create a Language Directory

After adding your language code (e.g. `ko`), create a corresponding folder in your project’s root `lang` directory:

```
lang/
├── en/
│   └── custom.php
├── zh_TW/
│   └── custom.php
└── ko/
    └── custom.php
```

Each folder can contain your localized translation files such as `custom.php`.

Example:

```php
<?php

$custom_words = [
    'welcome' => '환영합니다',
    'save'    => '저장',
];

return $custom_words;
```

## Step 4: Clear Cache

After modifying your configuration, clear your Laravel cache to make sure the new language is recognized.

```
php artisan config:clear
php artisan cache:clear
```

## Step 5: Verify Middleware

WNCMS automatically registers the required localization middlewares in `WncmsServiceProvider`:

- `localize`
- `localeSessionRedirect`
- `localizationRedirect`
- `localeViewPath`

You don’t need to manually register them.

## Step 6: Test in Browser

Once your locale is added, you can test by visiting:

```
https://yoursite.com/ko/
```

WNCMS will automatically switch to the Korean language and use your translations from `lang/ko/custom.php`.
