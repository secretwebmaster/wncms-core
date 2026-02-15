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
  "dependencies": {
    "wncms-seo-core": "^1.0"
  },
  "priority": 20
}
```

### 依賴與版本相容性

WNCMS 在啟用時會先檢查 `dependencies`，再執行外掛生命週期 `activate()`。
只要出現缺失依賴、依賴未啟用或版本不相容，外掛啟用會被阻擋。

## 3. 新增生命週期入口

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

return new class extends AbstractPlugin {
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
};
```

`Plugin.php` 應直接回傳外掛實例（`return new class extends AbstractPlugin`）。
`plugin.json` 的 `class` 為可選，只有在 `Plugin.php` 不回傳實例時才需要。

### 新增升級步驟檔案

將升級檔案放在外掛 `upgrades/` 目錄，並在 `$upgrades` 中顯式對應。

`public/plugins/wncms-users-telegram-option/upgrades/upgrade_0_2_0.php`

```php
<?php

return function (array $context, AbstractPlugin $instance, \Wncms\Models\Plugin $plugin) {
    // 從 $context['from_version'] 遷移到 $context['to_version']
    // 失敗時拋出例外以中止升級
};
```

升級檔案僅透過 `$upgrades` 對應執行（不做自動發現）。
當 `$upgrades` 的值是 `upgrade_0_2_0.php` 時，執行期會解析為 `upgrades/upgrade_0_2_0.php`。

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

### 側邊欄卡片 + 彈窗 + 外掛後端路由範例（文章 SEO）

文章編輯側邊欄建議使用複數 target 的標準視圖 hook：

- `wncms.view.backend.posts.edit.sidebar`

```php
Event::listen('wncms.view.backend.posts.edit.sidebar', function ($post, $request) {
    return seo_analysis_plugin_view('backend/posts/seo_analysis/sidebar-card.blade.php', [
        'post' => $post,
        'analyze_url' => route('plugins.wncms_seo_analysis.posts.analyze'),
    ]);
});
```

外掛後端分析路由建議使用 POST，並沿用後台文章編輯權限：

`routes/web.php` 會由 core 自動套用基礎中介層：`web`、`is_installed`、`has_website`。此處只需加上外掛自身限制（例如 `auth`、`can:*`）。

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

在注入視圖中：

- 顯示分數徽章與進度條。
- 提供「查看詳情」按鈕。
- 開啟彈窗後，POST 目前文章表單資料到 `plugins.wncms_seo_analysis.posts.analyze`。
- 將回傳 JSON 分析結果渲染在彈窗內。
- 建議將每個檢查結果渲染成可展開手風琴，顯示「修正建議 / 目前值 / 目標值」，並以 1 秒防抖做即時重算。
- 建議加入更專業的檢查項：內部連結、外部參考連結、主關鍵字首段出現、關鍵字密度過高/過低，以及句子重複風險。
- 內建規則建議以結構訊號為主。語意/AI 品質檢查可透過擴充事件 `wncms.backend.posts.seo_analyze.extend` 由其他外掛注入自訂檢查結果。

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
- 後台外掛列表會在 `依賴外掛` 欄位顯示依賴關係。
- 後台 users create/edit 出現注入欄位。
- 前台 profile 出現注入欄位。
- 外掛 settings 分頁可見並保存分組 key。
- 若有其他已啟用外掛依賴該外掛，停用時會被阻擋，並提示先停用依賴方外掛。
