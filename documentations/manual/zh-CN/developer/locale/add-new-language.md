# Add New Language

WNCMS 使用 **Laravel Localization** 来处理多语言路由与介面文字。要新增一种新语言，开发者只需建立一个设定档与对应的语言目录—不需要 publish command。

## Step 1: 复制 Localization Config

在你的专案中建立新档案：

```
config/laravellocalization.php
```

你可以直接从 `wncms-core` 套件复制此档案：

```
vendor/secretwebmaster/wncms-core/config/laravellocalization.php
```

## Step 2: 启用或新增你的语言

在你复制的 `config/laravellocalization.php` 中，找到 `supportedLocales` 阵列。

取消注解或新增你需要的语言。
例如，要新增 **Korean** 支援：

```php
'supportedLocales' => [
    'zh_TW' => ['name' => 'Chinese (Traditional)', 'script' => 'Hant', 'native' => '繁体中文', 'regional' => 'zh_TW'],
    'en'    => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
    'ja'    => ['name' => 'Japanese', 'script' => 'Jpan', 'native' => '日本语', 'regional' => 'ja_JP'],
    'ko'    => ['name' => 'Korean', 'script' => 'Hang', 'native' => '한국어', 'regional' => 'ko_KR'], // 新增
],
```

## Step 3: 建立语言目录

新增你的语言代码（例如 `ko`）后，在你专案根目录的 `lang` 目录中建立对应的资料夹：

```
lang/
├── en/
│   └── custom.php
├── zh_TW/
│   └── custom.php
└── ko/
    └── custom.php
```

每个资料夹可以包含你的本地化翻译档案，例如 `custom.php`。

范例：

```php
<?php

$custom_words = [
    'welcome' => '환영합니다',
    'save'    => '저장',
];

return $custom_words;
```

## Step 4: 清除 Cache

修改你的设定后，清除你的 Laravel cache 以确保新语言被识别。

```
php artisan config:clear
php artisan cache:clear
```

## Step 5: 验证 Middleware

WNCMS 在 `WncmsServiceProvider` 中自动注册所需的 localization middlewares：

- `localize`
- `localeSessionRedirect`
- `localizationRedirect`
- `localeViewPath`

你不需要手动注册它们。

## Step 6: 在 Browser 中测试

一旦新增你的 locale，你可以透过访问以下网址测试：

```
https://yoursite.com/ko/
```

WNCMS 会自动切换到 Korean 语言，并使用来自 `lang/ko/custom.php` 的翻译。
