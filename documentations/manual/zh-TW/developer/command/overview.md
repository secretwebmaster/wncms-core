# 開發命令總覽

本頁說明 WNCMS 常用開發腳手架命令。

## `wncms:create-model`

在宿主專案中建立模型腳手架（模型、遷移、後台控制器、starter 視圖、權限）。

```bash
php artisan wncms:create-model Novel
```

行為摘要：
- 不存在時建立 `app/Models/Novel.php`。
- 建立 `novels` 資料表遷移檔。
- 建立 `app/Http/Controllers/Backend/NovelController.php`。
- 呼叫 `wncms:create-model-view novel`。
- 呼叫 `wncms:create-model-permission novel`。
- 可選擇將路由附加到 `routes/custom_backend.php`。

## `wncms:create-model-view`

使用 starter 模板為模型建立後台 blade 檔案。

```bash
php artisan wncms:create-model-view novel
```

產生檔案：
- `resources/views/backend/novels/index.blade.php`
- `resources/views/backend/novels/create.blade.php`
- `resources/views/backend/novels/edit.blade.php`
- `resources/views/backend/novels/form-items.blade.php`

Starter 模板路徑解析順序：
1. 套件根目錄 `resources/views/backend/starters`
2. 套件根目錄上一層 `../resources/views/backend/starters`
3. 內部備援路徑：`src/../../resources/views/backend/starters`

若找不到有效 starter 路徑，命令會以失敗結束並列出所有已檢查路徑。

## `wncms:create-model-permission`

為模型 key 建立常用後台權限。

```bash
php artisan wncms:create-model-permission novel
```

常見權限後綴包含：
- `_index`
- `_create`
- `_clone`
- `_edit`
- `_delete`
- `_bulk_delete`

## `wncms:activate-plugin`

透過 CLI 啟用插件，行為與後台啟用一致（`status` => `active`）。

```bash
php artisan wncms:activate-plugin wncms-users-hook-test
```

行為摘要：
- 支援插件 `name`、`plugin_id` 或目錄 `path` 作為輸入。
- 會掃描 `public/plugins`，並把尚未入庫的目錄插件同步到 `plugins` 資料表。
- 若插件提供標準化主類，會先執行生命週期 `activate()`。
- 命中後會將插件狀態更新為 `active`。
- 若 `plugins` 資料表不存在或找不到目標插件，命令會回傳失敗。

## `wncms:verify-plugin-hooks`

執行插件與 users hook 硬切遷移的發佈閘門檢查。

```bash
php artisan wncms:verify-plugin-hooks
```

行為摘要：
- 檢查插件根目錄（`public/plugins`）是否存在。
- 檢查每個插件目錄的 `plugin.json` 是否有效（必須包含 `id`、`name`、`version`）。
- 檢查核心使用者控制器中是否仍存在 legacy users hook 名稱。
- 檢查 `plugins` 資料表是否存在，且不存在 `[MANIFEST_ERROR]` / `[LOAD_ERROR]` 記錄。
- 任一閘門失敗即回傳失敗（應阻止發佈）。

## `wncms:install-default-theme`

安裝或重新安裝核心預設主題資源到 `public/themes`。

```bash
php artisan wncms:install-default-theme --force
```

行為摘要：
- 發佈 `wncms-default-assets` 發佈標籤對應的資源。
- 適用於預設主題資源被修改、遺失或損壞後的復原場景。
- 該命令也會被安裝流程（CLI 與瀏覽器安裝精靈）透過共用安裝邏輯呼叫。

## 安裝方式（`wncms:install` + 瀏覽器精靈）

WNCMS 支援兩種安裝入口：

1. CLI 指令：`php artisan wncms:install ...`
2. 瀏覽器精靈：`/install/wizard`

兩種方式現在都使用 `InstallerManager` 的同一套共用安裝流程，因此以下步驟行為一致：
- 資料庫連線檢查
- 寫入 `.env`
- 產生應用程式金鑰
- 資料庫初始化
- 發佈資源
- 初始化自訂語言/路由檔案
- 初始化系統設定
- 寫入安裝標記並清理快取

### 多站點預設行為

- `multi_website` 預設值為 `false`。
- CLI：只有傳入 `--multi_website` 才會啟用多站點。
- 精靈：只有勾選核取方塊才會啟用多站點。

安裝後可用以下方式驗證：

```bash
php artisan tinker
```

```php
gss('multi_website');
```

## 疑難排解

- `Source view file not found`：
  檢查套件中的 `resources/views/backend/starters` 是否有 starter blade 檔案。
- 命令未建立視圖：
  確認 `resources/views/backend/{plural}/` 下目標檔案不是已存在狀態。
- 路由權限被拒絕：
  重新執行 `wncms:create-model-permission {model}`，並在後台確認角色已指派對應權限。
- 升級專案中 Link 後台路由權限被拒絕：
  升級到 core `6.1.9+` 並執行 `php artisan wncms:update core`，更新流程會自動補齊 Link 權限。
