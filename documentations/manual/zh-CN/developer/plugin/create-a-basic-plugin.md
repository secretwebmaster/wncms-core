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
  "dependencies": {
    "wncms-seo-core": "^1.0"
  },
  "priority": 20
}
```

### 依赖与版本相容性

WNCMS 在启用时会先检查 `dependencies`，然后才执行插件生命周期 `activate()`。
只要存在缺失依赖、依赖未启用或版本不相容，插件启用会被阻止。

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

### 新增升级步骤文件

将升级文件放在插件根目录，并在 `$upgrades` 中显式映射。

`public/plugins/wncms-users-telegram-option/upgrade_0_2_0.php`

```php
<?php

return function (array $context, AbstractPlugin $instance, \Wncms\Models\Plugin $plugin) {
    // 从 $context['from_version'] 迁移到 $context['to_version']
    // 失败时抛出异常以中止升级
};
```

升级文件仅通过 `$upgrades` 映射执行（不做自动发现）。

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

### 侧边栏卡片 + 弹窗 + 插件后端路由示例（文章 SEO）

文章编辑侧边栏建议使用复数 target 的标准视图 hook：

- `wncms.view.backend.posts.edit.sidebar`

```php
Event::listen('wncms.view.backend.posts.edit.sidebar', function ($post, $request) {
    return seo_analysis_plugin_view('backend/posts/seo_analysis/sidebar-card.blade.php', [
        'post' => $post,
        'analyze_url' => route('plugins.wncms_seo_analysis.posts.analyze'),
    ]);
});
```

插件后端分析路由建议使用 POST，并沿用后台文章编辑权限：

`routes/web.php` 会由 core 自动套用基础中间件：`web`、`is_installed`、`has_website`。此处只需添加插件自身限制（例如 `auth`、`can:*`）。

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

在注入视图中：

- 显示分数徽章与进度条。
- 提供“查看详情”按钮。
- 打开弹窗后，POST 当前文章表单数据到 `plugins.wncms_seo_analysis.posts.analyze`。
- 将回传 JSON 分析结果渲染在弹窗内。
- 建议将每个检查结果渲染为可展开手风琴，并显示“修复建议 / 当前值 / 目标值”，同时以 1 秒防抖进行实时重算。
- 建议加入更专业的检查项：内链、外部参考链接、主关键词首段出现、关键词密度过高/过低、以及句子重复风险。
- 内置规则建议以结构信号为主。语义/AI 质量检查可通过扩展事件 `wncms.backend.posts.seo_analyze.extend` 由其他插件注入自定义检查结果。

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
- 后台插件列表会在 `依赖插件` 字段显示依赖关系。
- 后台 users create/edit 出现注入字段。
- 前台 profile 出现注入字段。
- 插件 settings 分页可见并保存分组 key。
- 若有其他启用中的插件依赖该插件，停用时会被阻止，并提示先停用依赖方插件。
