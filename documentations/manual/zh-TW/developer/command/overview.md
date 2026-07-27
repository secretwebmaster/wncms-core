# 開發命令總覽

本頁說明 WNCMS 常用開發腳手架命令。

## `wncms:create-model`

在宿主專案中建立模型腳手架（模型、遷移、後台控制器、starter 視圖、權限）。

```bash
php artisan wncms:create-model Novel
```

行為摘要：
- 不存在時建立 `app/Models/Novel.php`。
- 產生的模型擴展 `Wncms\Models\BaseModel`，並包含 `modelKey` 保底邏輯（留空時按類名自動推導）。
- 建立 `novels` 資料表遷移檔。
- 建立 `app/Http/Controllers/Backend/NovelController.php`。
- 產生的後台控制器方法簽名與 `BackendController` 相容（`create($id)`、`edit($id)`、`update(Request, $id)`、`destroy($id)`）。
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

## `wncms:create-permission`

直接建立一個或多個權限，並可選擇指派給角色。

```bash
php artisan wncms:create-permission article_publish
```

範例：

```bash
# 建立單一權限
php artisan wncms:create-permission article_publish

# 建立單一權限並指派給一個角色
php artisan wncms:create-permission article_publish editor

# 一次建立多個權限
php artisan wncms:create-permission article_publish,article_archive

# 建立多個權限並指派給多個角色
php artisan wncms:create-permission article_publish,article_archive editor,admin
```

行為摘要：
- `{permission_name}` 支援逗號分隔的權限名稱。
- `{role}` 為可選參數，也支援逗號分隔的角色名稱。
- 使用 `firstOrCreate` 建立缺少的權限。
- 使用 `firstOrCreate` 建立缺少的角色。
- 將所有提供的權限指派給所有提供的角色。

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

## `wncms:hook-list`

用於插件開發的 hook/extension 註冊表巡檢命令。

```bash
php artisan wncms:hook-list
```

常見用法：

```bash
# 顯示每個 hook 的 listener 詳細資料
php artisan wncms:hook-list --listeners

# 只顯示目前已有 listener 的 hook
php artisan wncms:hook-list --only-listened

# 輸出 JSON 給自動化腳本使用
php artisan wncms:hook-list --json
```

行為摘要：
- 掃描 WNCMS 核心 `src`（以及宿主專案 `app`）中的 hook 派發點（`Event::dispatch(...)` / `event(...)`）。
- 列出每個 hook 的派發點數量與目前執行期 listener 數量。
- `--listeners` 可輸出每個 hook 對應的 listener 識別資訊。
- 同時輸出 `macroable-models` 中已註冊的擴充（依模型分組的查詢巨集）。

預期輸出格式（節錄）：

```text
WNCMS Hook / Extension Registry
Hooks: 40, Macros: 2

+---------------------------------------------+-----------------+-----------+
| Hook                                        | Dispatch Points | Listeners |
+---------------------------------------------+-----------------+-----------+
| wncms.frontend.users.login.before           | 1               | 0         |
| wncms.frontend.users.register.after         | 1               | 1         |
+---------------------------------------------+-----------------+-----------+

Registered Macros (Extension Registry)
+----------------+------------------------+-------------+
| Macro          | Models                 | Model Count |
+----------------+------------------------+-------------+
| wherePublished | Wncms\Models\Post      | 1           |
+----------------+------------------------+-------------+
```

## `wncms:links:list`

透過 CLI 列出 links。

```bash
php artisan wncms:links:list
```

常見用法：

```bash
# 以 JSON 列出 active links
php artisan wncms:links:list --json

# 包含所有 status
php artisan wncms:links:list --status=all --json

# 依 keyword 與 website scope 篩選
php artisan wncms:links:list --keyword=partner --website=1 --per-page=20 --json
```

行為摘要：
- 使用 `LinkAutomationService` 與 `LinkManager` 進行 read-only list access。
- 預設為 `--status=active`；使用 `--status=all` 可停用 status filtering。
- 支援 `--keyword=`、`--website=`、`--page=`、`--per-page=`、`--sort=`、`--direction=`。
- 預設輸出 operator table，使用 `--json` 時輸出對齊 API v2 的 envelope。
- 不會 mutate data 或 flush cache。

## `wncms:links:inspect`

透過 CLI 依 ID 或 slug 檢視單一 link。

```bash
php artisan wncms:links:inspect 123
php artisan wncms:links:inspect my-link-slug --json
```

行為摘要：
- `{identifier}` 可使用 numeric ID 或 slug。
- 支援 `--website=`，可在 Link website mode 需要時限定 lookup scope。
- 預設輸出 key-value table，使用 `--json` 時輸出對齊 API v2 的 envelope。
- 找不到 link 時會以 `code: 404` 回傳 failure。
- 不會 mutate data 或 flush cache。

## `wncms:links:create`

透過 guarded automation path 建立 link。

```bash
# 預設是 dry-run，不會寫入資料
php artisan wncms:links:create --name="Partner" --url=https://example.com --website=1 --json

# 寫入模式需要 --force，並指定有 link_create 權限的 actor user
php artisan wncms:links:create --name="Partner" --url=https://example.com --website=1 --actor-user=1 --force --json
```

行為摘要：
- 使用 `LinkAutomationService`，並回傳與 read-only Link commands 相同的 automation result envelope。
- 預設 dry-run；未提供 `--force` 不會寫入，`--dry-run` 永遠會阻止寫入。
- 寫入模式需要來自 `--actor-user=` 或 `wncms.automation.system_actor_user_id` 的 actor。
- Actor 必須通過 `link_create` permission 與 requested website scope checks。
- 成功寫入時會建立 Link，並在 Link 使用 scoped website mode 時綁定 requested websites、同步 requested link categories/tags、flush `links` cache、dispatch 既有 Link store hooks，並寫入 `mutation_audits` record。
- 支援 `--name=`、`--url=`、`--status=`、`--slug=`、`--tracking-code=`、`--website=`、`--description=`、`--slogan=`、`--external-thumbnail=`、`--remark=`、`--sort=`、`--color=`、`--background=`、`--is-pinned`、`--is-recommended`、`--expired-at=`、`--hit-at=`、`--clicks=`、`--contact=`、`--link-categories=`、`--link-tags=`。

## `wncms:links:update`

透過 guarded automation path 更新指定的 Link 欄位。

```bash
# 預設只預覽 patch，不會寫入資料
php artisan wncms:links:update partner-link --name="Partner Plus" --json

# 寫入模式需要有 link_edit 權限的 actor
php artisan wncms:links:update partner-link --name="Partner Plus" --is-pinned=false --actor-user=1 --force --json
```

行為摘要：
- 只會更新提供的 patch 欄位；未提供的欄位會保留。
- 預設 dry-run；未提供 `--force` 不會寫入，`--dry-run` 永遠會阻止寫入。
- 寫入模式需要來自 `--actor-user=` 或 `wncms.automation.system_actor_user_id` 的 actor，且必須擁有 `link_edit` permission。
- `--website=` 會限制 target lookup。Guard 總是檢查 target Link 現有的 website IDs，因此省略該選項也不能繞過跨站保護。
- 未知 website ID 回傳 `422`；缺少 scoped target 回傳 `404`；缺少 actor 回傳 `401`；permission 或 website scope 失敗回傳 `403`。
- 無變更的 patch 會成功回傳 `200`，且不會 flush cache 或寫入 audit。成功寫入會 dispatch 既有 Link update hooks、在 transaction 後 flush `links` cache，並寫入 `mutation_audits` record。
- 支援 `--status=`、`--tracking-code=`、`--slug=`、`--name=`、`--url=`、`--slogan=`、`--description=`、`--external-thumbnail=`、`--remark=`、`--sort=`、`--color=`、`--background=`、`--is-pinned=true|false`、`--is-recommended=true|false`、`--expired-at=`、`--hit-at=`、`--clicks=`、`--contact=`、`--website=`、`--actor-user=`、`--dry-run`、`--force` 與 `--json`。
- 明確傳入空值會清除 nullable patch 欄位；明確傳入空的 `status`、`slug`、`name` 或 `url` 會回傳 `422`。Boolean 欄位只接受 `true`、`false`、`1`、`0`、`yes`、`no`、`on` 或 `off`。
- Dry-run 不會執行 `wncms.backend.links.update.attributes.before`，因為 hook 可能有 side effects。Dry-run 的 changes 是 hook 前的結果；成功寫入的 response 與 audit attributes 會反映 hook 修改後的值與 changes。

## `wncms:links:delete`

透過 guarded automation path 刪除一個 Link。

```bash
# 預設：僅預覽
php artisan wncms:links:delete partner-link --json

# 寫入模式需要擁有 link_delete 的 actor
php artisan wncms:links:delete partner-link --actor-user=1 --force --json
```

行為摘要：

- 預設 dry-run；刪除需要 `--force`，`--dry-run` 一律阻止寫入。
- 需要來自 `--actor-user=` 或已配置 system actor 的 actor，且必須擁有 `link_delete` permission。
- `--website=` 會限制 target lookup；即使省略此參數，guard 也會檢查 target Link 現有 website IDs。
- 未知 website ID 回傳 `422`；缺少 actor 回傳 `401`；permission 或 website scope 失敗回傳 `403`；找不到或不在 scope 內的 target 回傳 `404`。
- 成功刪除會在 transaction 中執行，回傳 deleted target 與 audit ID，在 `mutation_audits` 保存 target snapshot，之後 flush `links` cache。Link 沒有 delete hooks，因此不會派發 hooks。
- 支援 `{identifier}`、`--website=`、`--actor-user=`、`--dry-run`、`--force` 與 `--json`。

## `wncms:links:bulk-update`

以原子方式批量更新最多 100 個 Link 的 `url` 和/或 `sort` 欄位。

```bash
# 預設只驗證並預覽，不寫入資料
php artisan wncms:links:bulk-update --items='[{"identifier":"partner-link","url":"https://example.com/partner"},{"identifier":42,"sort":10}]' --json

# 寫入模式需要具備權限的 actor
php artisan wncms:links:bulk-update --items='[{"identifier":42,"sort":10}]' --website=1 --actor-user=1 --force --json
```

`--items=` 必須是包含 1-100 個項目的 JSON 陣列。每項都需要 `identifier`（Link ID 或 slug），只能包含 `url` 與 `sort`，至少提供一個更新欄位；提供 `url` 時不得為空。
提供 `sort` 時必須是 JSON 整數或整數形式字串，例如 `10` 或 `"-10"`；`null`、布林值、浮點數與浮點形式字串都會被拒絕。

行為摘要：

- 命令具原子性：JSON 錯誤、重複的已解析目標、目標不存在或超出網站範圍、無效欄位及 guard 失敗都會阻止全部寫入。
- 預設 dry-run；`--force` 才進入受保護寫入模式，`--dry-run` 一律優先並回傳不寫入的 `202`。
- 寫入需要 `--actor-user=` 或已設定系統 actor，且必須擁有 `link_edit`。`--website=` 會限制全部查詢，並一律檢查每個目標現有的網站範圍。
- 成功回傳 `200`；輸入錯誤為 `422`；缺少 actor 為 `401`；權限或網站範圍拒絕為 `403`；目標不存在或超出範圍為 `404`；取消或過期批次為 `409`。
- 每個實際變更的 Link 都會寫入一筆共用 run ID 的 `mutation_audits`；無變更項目不寫審計。只有已提交且有變更的批次才會一次性刷新 `links` 快取，且不會派發 bulk-update hooks。

## `wncms:install-default-theme`

安裝或重新安裝核心預設主題資源到 `public/themes`。

```bash
php artisan wncms:install-default-theme --force
```

行為摘要：
- 發佈 `wncms-default-assets` 發佈標籤對應的資源。
- 適用於預設主題資源被修改、遺失或損壞後的復原場景。
- 該命令也會被安裝流程（CLI 與瀏覽器安裝精靈）透過共用安裝邏輯呼叫。
- 若因檔案系統權限導致資源複製失敗，後端工具會回傳可翻譯的引導訊息，提示先執行 `修復權限`。

## `wncms:install-agent-files`

將 WNCMS agent 檔案安裝到宿主專案根目錄。

```bash
php artisan wncms:install-agent-files
```

常見用法：

```bash
# 不詢問，直接覆蓋所有已存在目標
php artisan wncms:install-agent-files --force

# 只預覽，不寫入檔案
php artisan wncms:install-agent-files --dry-run
```

行為摘要：
- 從套件內 `resources/agent-files` 作為發佈來源。
- 安裝 `AGENTS.md` 與 `.github/skills` 到宿主專案根目錄。
- 預設模式對已存在目標採互動確認：
  - 詢問是否覆蓋 `AGENTS.md`
  - 詢問是否覆蓋 `.github/skills`
- `--force` 會直接覆蓋已存在目標。
- `--dry-run` 只輸出預計動作，不會修改任何檔案。

## `wncms:update-website`

透過 CLI 更新網站單一欄位。

```bash
php artisan wncms:update-website {key} {value}
```

常見用法：

```bash
# 切換網站主題
php artisan wncms:update-website theme default

# 更新網站名稱
php artisan wncms:update-website site_name "My Website"
```

行為摘要：
- 在 CLI 情境下更新目前網站；若無法依網域解析，則回退到第一筆網站資料。
- 會驗證 `{key}` 是否為 `websites` 資料表真實欄位。
- 更新 `theme` 時，會自動補齊新主題缺少的預設 theme options。
- 更新後會清除 `websites` 快取標籤。

## `wncms:update`

執行核心更新腳本。

```bash
# 一般更新流程（遠端版本清單 + 遞增執行）
php artisan wncms:update core

# 重新執行一個指定的本地更新檔案
php artisan wncms:update --rerun-version=6.1.6
php artisan wncms:update --rerun-version=v6.1.6
```

行為摘要：
- `--rerun-version=` 會重新執行一個指定版本的本地更新腳本：
  - `updates/update_core_{version}.php`
- 支援 `v` 前綴（例如 `v6.1.6` 與 `6.1.6` 等價）。
- 若 `--rerun-version` 為空或在 `updates/` 中找不到對應檔案，命令會回傳失敗。

## 安裝方式（`wncms:install` + 瀏覽器精靈）

WNCMS 支援兩種安裝入口：

1. CLI 指令：`php artisan wncms:install ...`
2. 瀏覽器精靈：`/install/wizard`

兩種方式現在都使用 `InstallerManager` 的同一套共用安裝流程，因此以下步驟行為一致：
- 資料庫連線檢查
- 寫入 `.env`
- 產生應用程式金鑰
- 資料庫初始化
- 發佈資源（`wncms-core-assets`、`wncms-stubs`、`wncms-default-assets`）
- 初始化自訂語言/路由檔案
- 初始化系統設定
- 寫入安裝標記並清理快取

CLI 語系行為：
- `--app_locale=` 會控制安裝器終端輸出的語言。
- 範例：`--app_locale=zh_CN` 會以簡體中文顯示安裝進度訊息。
- 若 locale 為空或不受支援，安裝器會回退到應用程式設定語系/預設支援語系。

CLI agent 檔案行為：
- `--agent`（或 `--agent=1`）會在安裝流程中發佈 `wncms-agent-files` 標籤。
- 等效發佈命令：`php artisan vendor:publish --tag=wncms-agent-files`。

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
