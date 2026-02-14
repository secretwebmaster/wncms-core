# 建立基礎外掛

本指南示範 WNCMS 非 Composer 外掛的最小可用結構。

## 1. 建立外掛目錄

範例外掛 id：`wncms-users-telegram-option`

```bash
mkdir -p public/plugins/wncms-users-telegram-option/{classes,routes,system,views/backend,views/frontend,views/common,lang/en,lang/zh_CN,lang/zh_TW,lang/ja}
```

## 2. 新增 `plugin.json`

`public/plugins/wncms-users-telegram-option/plugin.json`

```json
{
  "id": "wncms-users-telegram-option",
  "class": "WncmsPlugin_wncms_users_telegram_option",
  "name": {
    "en": "WNCMS Users Telegram Option",
    "zh_CN": "WNCMS 用户 Telegram 选项",
    "zh_TW": "WNCMS 使用者 Telegram 選項",
    "ja": "WNCMS ユーザーTelegramオプション"
  },
  "description": {
    "en": "Inject telegram fields into users screens via hooks",
    "zh_TW": "透過 hooks 將 Telegram 欄位注入 users 頁面"
  },
  "author": {
    "en": "local-dev",
    "zh_TW": "本地開發者"
  },
  "version": "0.1.0",
  "priority": 20
}
```

## 3. 新增生命週期類別

`public/plugins/wncms-users-telegram-option/Plugin.php`

```php
<?php

if (!defined('WNCMS_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}

require_once __DIR__ . '/classes/TelegramOptionPlugin.php';

use Illuminate\Support\Facades\Event;
use Wncms\Plugins\AbstractPlugin;

class WncmsPlugin_wncms_users_telegram_option extends AbstractPlugin
{
    public function init(): void
    {
        Event::listen('wncms.backend.settings.tabs.extend', function (&$availableSettings) {
            $pluginId = $this->getId();

            $availableSettings['telegram'] = [
                'tab_name' => 'telegram',
                'tab_label_key' => $pluginId . '::word.telegram_setting',
                'tab_content' => [
                    [
                        'type' => 'switch',
                        'name' => $pluginId . ':enable_telegram_id',
                        'text_key' => $pluginId . '::word.enable_telegram_id',
                    ],
                ],
            ];
        });
    }

    public function activate(): void
    {
        $this->setDefaultSetting('enable_telegram_id', '1');
    }
}
```

## 4. 新增可選外掛類別

額外 class 放在 `classes/`，檔名與 class 名可自由命名。

範例：

- `public/plugins/wncms-users-telegram-option/classes/TelegramOptionPlugin.php`

這些 class 應在根目錄 `Plugin.php` 載入，不要在 `system/functions.php` 內 `require_once`。

## 5. 新增 hooks 與視圖注入

`public/plugins/wncms-users-telegram-option/system/events.php`

```php
<?php

if (!defined('WNCMS_START')) {
    http_response_code(403);
    exit('403 Forbidden');
}

use Illuminate\Support\Facades\Event;

Event::listen('wncms.view.backend.users.edit.fields', function ($user, $request) {
    $plugin = app(\Wncms\Plugins\PluginLoader::class)->load('wncms-users-telegram-option');

    return $plugin->renderView('backend/users/forms/telegram-edit.blade.php', [
        'telegram_username' => old('telegram_username', (string) $user->getOption('telegram_username', '')),
        'telegram_id' => old('telegram_id', (string) $user->getOption('telegram_id', '')),
    ]);
});
```

## 6. 新增翻譯

在預設 locale 都建立 `word.php`：

- `lang/en/word.php`
- `lang/zh_CN/word.php`
- `lang/zh_TW/word.php`
- `lang/ja/word.php`

在外掛視圖中使用命名空間 key：

```blade
@lang('wncms-users-telegram-option::word.telegram_username')
```

## 7. 啟用與驗證

```bash
php artisan wncms:activate-plugin wncms-users-telegram-option
php artisan wncms:verify-plugin-hooks
```

驗證項目：

- 後台外掛列表可見該外掛。
- 後台 users create/edit 出現注入欄位。
- 前台 profile 出現注入欄位。
- 外掛 settings 分頁可見並保存分組 key。
