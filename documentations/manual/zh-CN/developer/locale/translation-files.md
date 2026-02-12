# Translation Files

WNCMS 会将你的专案覆写合并到其核心翻译中。核心 `wncms-core` 套件公开 `word.php` 群组，并在执行时将你网站的 `lang/{locale}/custom.php` **合并**到其上。你不需要编辑 vendor 档案。

## 你应该建立什么

为每个你支援的 locale 建立 `custom.php` 档案：

```
lang/
├── en/
│   └── custom.php
├── zh_TW/
│   └── custom.php
└── ja/
    └── custom.php
```

## 最小范例

`lang/en/custom.php`

```php
<?php

$custom_words = [
    'welcome' => 'Welcome to My Site', // 若存在则覆写现有的 key
    'contact' => 'Get in Touch',       // 新增 key
];

return $custom_words;
```

## 合并如何运作

- WNCMS 载入其核心的 `word.php`（来自 `wncms-core` 套件）。
- 然后它从你的 app 载入 `lang/{locale}/custom.php` 并**合并**它。
- `custom.php` 中的 keys **覆写** WNCMS 提供的同名 keys。
- `custom.php` 中的新 keys 在相同 namespace 下变得可用。

## 如何引用 keys

建议（合并的群组，包含你的覆写）：

```blade
@lang('wncms::word.welcome')
@lang('wncms::word.contact')
```

你仍然可以使用传统的 Laravel 群组，如果你想直接指向你的档案：

```blade
@lang('custom.contact')
```

两种方法都可以；`wncms::word.*` 是首选，因为它总是反映合并的结果。

## 每个 locale 的行为

为你实际提供的每个 locale 建立一个 `custom.php`：

- `lang/en/custom.php`
- `lang/zh_TW/custom.php`
- `lang/ja/custom.php`

只有出现在某个 locale 的 `custom.php` 中的 keys 会覆写该 locale 的文字。

## 注意事项

- 将 vendor 预设值保留在套件中；将**网站/theme 特定**的用词保留在你的 `custom.php` 中。
- 在各 locales 之间维持一致的 keys 以避免缺少翻译。
- 在正式环境中变更语言档案后，如有需要请清除快取：

  ```
  php artisan cache:clear
  php artisan config:clear
  ```
