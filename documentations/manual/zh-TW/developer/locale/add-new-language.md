# Add New Language

WNCMS 使用 **Laravel Localization** 來處理多語言路由與介面文字。要新增一種新語言，開發者只需建立一個設定檔與對應的語言目錄—不需要 publish command。

## Step 1: 複製 Localization Config

在你的專案中建立新檔案：

```
config/laravellocalization.php
```

你可以直接從 `wncms-core` 套件複製此檔案：

```
vendor/secretwebmaster/wncms-core/config/laravellocalization.php
```

## Step 2: 啟用或新增你的語言

在你複製的 `config/laravellocalization.php` 中，找到 `supportedLocales` 陣列。

取消註解或新增你需要的語言。
例如，要新增 **Korean** 支援：

```php
'supportedLocales' => [
    'zh_TW' => ['name' => 'Chinese (Traditional)', 'script' => 'Hant', 'native' => '繁體中文', 'regional' => 'zh_TW'],
    'en'    => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
    'ja'    => ['name' => 'Japanese', 'script' => 'Jpan', 'native' => '日本語', 'regional' => 'ja_JP'],
    'ko'    => ['name' => 'Korean', 'script' => 'Hang', 'native' => '한국어', 'regional' => 'ko_KR'], // 新增
],
```

## Step 3: 建立語言目錄

新增你的語言代碼（例如 `ko`）後，在你專案根目錄的 `lang` 目錄中建立對應的資料夾：

```
lang/
├── en/
│   └── custom.php
├── zh_TW/
│   └── custom.php
└── ko/
    └── custom.php
```

每個資料夾可以包含你的本地化翻譯檔案，例如 `custom.php`。

範例：

```php
<?php

$custom_words = [
    'welcome' => '환영합니다',
    'save'    => '저장',
];

return $custom_words;
```

## Step 4: 清除 Cache

修改你的設定後，清除你的 Laravel cache 以確保新語言被識別。

```
php artisan config:clear
php artisan cache:clear
```

## Step 5: 驗證 Middleware

WNCMS 在 `WncmsServiceProvider` 中自動註冊所需的 localization middlewares：

- `localize`
- `localeSessionRedirect`
- `localizationRedirect`
- `localeViewPath`

你不需要手動註冊它們。

## Step 6: 在 Browser 中測試

一旦新增你的 locale，你可以透過訪問以下網址測試：

```
https://yoursite.com/ko/
```

WNCMS 會自動切換到 Korean 語言，並使用來自 `lang/ko/custom.php` 的翻譯。
