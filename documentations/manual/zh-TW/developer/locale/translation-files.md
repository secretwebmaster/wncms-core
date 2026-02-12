# Translation Files

WNCMS 會將你的專案覆寫合併到其核心翻譯中。核心 `wncms-core` 套件公開 `word.php` 群組，並在執行時將你網站的 `lang/{locale}/custom.php` **合併**到其上。你不需要編輯 vendor 檔案。

## 你應該建立什麼

為每個你支援的 locale 建立 `custom.php` 檔案：

```
lang/
├── en/
│   └── custom.php
├── zh_TW/
│   └── custom.php
└── ja/
    └── custom.php
```

## 最小範例

`lang/en/custom.php`

```php
<?php

$custom_words = [
    'welcome' => 'Welcome to My Site', // 若存在則覆寫現有的 key
    'contact' => 'Get in Touch',       // 新增 key
];

return $custom_words;
```

## 合併如何運作

- WNCMS 載入其核心的 `word.php`（來自 `wncms-core` 套件）。
- 然後它從你的 app 載入 `lang/{locale}/custom.php` 並**合併**它。
- `custom.php` 中的 keys **覆寫** WNCMS 提供的同名 keys。
- `custom.php` 中的新 keys 在相同 namespace 下變得可用。

## 如何引用 keys

建議（合併的群組，包含你的覆寫）：

```blade
@lang('wncms::word.welcome')
@lang('wncms::word.contact')
```

你仍然可以使用傳統的 Laravel 群組，如果你想直接指向你的檔案：

```blade
@lang('custom.contact')
```

兩種方法都可以；`wncms::word.*` 是首選，因為它總是反映合併的結果。

## 每個 locale 的行為

為你實際提供的每個 locale 建立一個 `custom.php`：

- `lang/en/custom.php`
- `lang/zh_TW/custom.php`
- `lang/ja/custom.php`

只有出現在某個 locale 的 `custom.php` 中的 keys 會覆寫該 locale 的文字。

## 注意事項

- 將 vendor 預設值保留在套件中；將**網站/theme 特定**的用詞保留在你的 `custom.php` 中。
- 在各 locales 之間維持一致的 keys 以避免缺少翻譯。
- 在正式環境中變更語言檔案後，如有需要請清除快取：

  ```
  php artisan cache:clear
  php artisan config:clear
  ```
