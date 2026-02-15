# Create a Basic Plugin

This guide shows a minimal non-composer plugin scaffold for WNCMS.

## 1. Create plugin folder

Example plugin id: `wncms-users-telegram-option`

```bash
mkdir -p public/plugins/wncms-users-telegram-option/{classes,routes,system,views/backend,views/frontend,views/common,lang/en,lang/zh_CN,lang/zh_TW,lang/ja}
```

## 2. Add `plugin.json`

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
    "zh_TW": "本地開發者"
  },
  "version": "0.1.0",
  "dependencies": {
    "wncms-seo-core": "^1.0"
  },
  "priority": 20
}
```

### Dependency and version compatibility

During activation, WNCMS checks `dependencies` before running plugin lifecycle `activate()`.
If any dependency is missing, inactive, or version-incompatible, activation is blocked.

## 3. Add lifecycle class

Set `class` in `plugin.json` so core can resolve the lifecycle class without class-name guessing.

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
    public array $upgrades = [
        '0.2.0' => 'upgrade_0_2_0.php',
        '0.3.0' => 'upgrade_0_3_0.php',
    ];

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

### Add upgrade step files

Place upgrade files at plugin root and map them in `$upgrades`.

`public/plugins/wncms-users-telegram-option/upgrade_0_2_0.php`

```php
<?php

return function (array $context, AbstractPlugin $instance, \Wncms\Models\Plugin $plugin) {
    // migrate data from $context['from_version'] to $context['to_version']
    // throw exception to stop upgrade on failure
};
```

Upgrade files run only through `$upgrades` mapping (no auto-discovery).

## 4. Add optional plugin classes

Place additional classes under `classes/` with any meaningful file/class name.

Example:

- `public/plugins/wncms-users-telegram-option/classes/TelegramOptionPlugin.php`

Load these class files from root `Plugin.php` (not from `system/functions.php`).

## 5. Add hooks and view injection

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

### Sidebar card + modal + plugin backend route example (posts SEO)

Use canonical view hook naming with plural target names for post edit sidebar:

- `wncms.view.backend.posts.edit.sidebar`

```php
Event::listen('wncms.view.backend.posts.edit.sidebar', function ($post, $request) {
    return seo_analysis_plugin_view('backend/posts/seo_analysis/sidebar-card.blade.php', [
        'post' => $post,
        'analyze_url' => route('plugins.wncms_seo_analysis.posts.analyze'),
    ]);
});
```

Add a plugin backend POST route and keep access aligned with backend post edit permission:

`routes/web.php` files are loaded by core with base middleware: `web`, `is_installed`, `has_website`. Add only plugin-specific guards here (for example `auth`, `can:*`).

```php
Route::prefix('panel/plugins/wncms-seo-analysis')->middleware(['auth', 'can:post_edit'])->group(function () {
    Route::post('/posts/analyze', function (Request $request) {
        $payload = $request->validate([
            'title' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
        ]);

        Event::dispatch('wncms.backend.posts.seo_analyze.before', [&$payload, $request]);
        $result = SeoAnalysisEngine::analyze($payload);
        Event::dispatch('wncms.backend.posts.seo_analyze.after', [&$result, $payload, $request]);

        return response()->json(['success' => true, 'data' => $result]);
    })->name('plugins.wncms_seo_analysis.posts.analyze');
});
```

In the injected sidebar view:

- Show a score badge + progress bar.
- Provide a `View details` button.
- Open a modal and `POST` current post form data to `plugins.wncms_seo_analysis.posts.analyze`.
- Render returned JSON checks and suggestions inside the modal.
- Render checks as accordion rows with expandable fix guidance (`How to fix`, `Current`, `Target`) and run live re-check with a 1-second debounce.
- Recommended pro checks include: internal links, external references, primary keyword in first paragraph, keyword density overuse/underuse, and sentence duplication risk.
- Keep built-in rules structure-first. For semantic/AI quality checks, use extension hook `wncms.backend.posts.seo_analyze.extend` to append custom checks/result fields from another plugin.

## 6. Add translations

Create `word.php` in all default locales:

- `lang/en/word.php`
- `lang/zh_CN/word.php`
- `lang/zh_TW/word.php`
- `lang/ja/word.php`

Use namespaced keys in plugin views:

```blade
@lang('wncms-users-telegram-option::word.telegram_username')
```

## 7. Activate and verify

```bash
php artisan wncms:activate-plugin wncms-users-telegram-option
php artisan wncms:verify-plugin-hooks
```

Verify:

- Plugin appears on backend plugin list.
- Backend plugin list shows dependency field in `Required Plugins` column.
- Backend users create/edit shows injected fields.
- Frontend profile shows injected rows.
- Plugin settings tab appears and grouped key is saved.
- If another active plugin depends on this plugin, deactivation is blocked until dependent plugins are deactivated first.
