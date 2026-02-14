# 建立基础插件

本指南示范 WNCMS 非 Composer 插件的最小可用结构。

## 1. 建立插件目录

范例插件 id：`wncms-users-telegram-option`

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
    "zh_CN": "通过 hooks 将 Telegram 字段注入 users 页面"
  },
  "author": {
    "en": "local-dev",
    "zh_CN": "本地开发者"
  },
  "version": "0.1.0",
  "priority": 20
}
```

## 3. 新增生命周期类

在 `plugin.json` 设定 `class`，让 core 直接解析生命周期类，避免 class name 猜测。

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

## 4. 新增可选插件类

额外 class 放在 `classes/`，档名与 class 名可自由命名。

范例：

- `public/plugins/wncms-users-telegram-option/classes/TelegramOptionPlugin.php`

这些 class 应在根目录 `Plugin.php` 载入，不要在 `system/functions.php` 里 `require_once`。

## 5. 新增 hooks 与视图注入

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

## 6. 新增翻译

在默认 locale 都建立 `word.php`：

- `lang/en/word.php`
- `lang/zh_CN/word.php`
- `lang/zh_TW/word.php`
- `lang/ja/word.php`

在插件视图中使用命名空间 key：

```blade
@lang('wncms-users-telegram-option::word.telegram_username')
```

## 7. 启用与验证

```bash
php artisan wncms:activate-plugin wncms-users-telegram-option
php artisan wncms:verify-plugin-hooks
```

验证项目：

- 后台插件列表可见该插件。
- 后台 users create/edit 出现注入字段。
- 前台 profile 出现注入字段。
- 插件 settings 分页可见并保存分组 key。
