# Plugin 開發概述

WNCMS 支援在 `public/plugins` 下建立專案級外掛，不需要 Composer 註冊。
本節說明如何建立含生命週期、hooks、views 與 translations 的基礎外掛。

## 建議結構

建議使用：

```
public/plugins/{plugin_id}/
├── plugin.json
├── Plugin.php
├── classes/（可選）
├── system/events.php
├── system/functions.php
├── routes/web.php
├── views/{backend|frontend|common}/...
└── lang/{en|zh_CN|zh_TW|ja}/word.php
```

## 外掛生命週期

外掛可透過 `Plugin.php` 直接回傳實例（繼承 `Wncms\Plugins\AbstractPlugin`）實作生命週期。

- 額外 class 由根目錄 `Plugin.php` 統一載入。
- `system/events.php` 僅放 listener，`system/functions.php` 僅放 helper 函數。
- 當 `Plugin.php` 已回傳實例時，`plugin.json` 的 `class` 可省略。

- `init()`：註冊執行期 hooks/events。
- `activate()`：執行啟用邏輯（例如寫入預設 setting）。
- `deactivate()`：停用外掛時執行。
- `delete()`：刪除外掛時執行。

## 外掛升級生命週期

已安裝版本儲存在 `plugins.version`。
可用版本讀取自 `public/plugins/{plugin_id}/plugin.json` 的 `version`。

- 若 `plugin.json` 版本高於 `plugins.version`，代表有可用更新。
- 外掛列表僅顯示資料庫欄位。
- 直接修改 `plugin.json` 不會立即覆蓋外掛表的顯示欄位。
- 升級透過顯式操作執行（後台升級按鈕），成功後再同步 manifest 資訊到外掛表。
- `外掛列表` 僅顯示 `plugins` 資料表中已有紀錄的外掛。
- 在 `public/plugins` 存在但沒有匹配 `plugin_id` 紀錄的外掛，會顯示在獨立的 `原始外掛` 表格中。
- 首次啟用建立紀錄後，該外掛會顯示在一般 `外掛列表` 表格中。

### 升級定義（僅 deterministic map）

在外掛生命週期類別中定義：

```php
public array $upgrades = [
    '1.2.0' => 'upgrade_1_2_0.php',
    '1.3.0' => 'upgrade_1_3_0.php',
];
```

執行規則：

- 只執行 `$upgrades` 明確宣告的步驟。
- key 為目標版本。
- 依版本升序執行。
- 執行條件：`installed_version < target_version <= available_version`。
- 升級檔案從 `{plugin_root}/upgrades/` 解析；若使用裸檔名，執行期會自動補上此前綴目錄。
- 若可用版本更高但升級鏈無法到達該版本，升級失敗。
- 任一步驟失敗即停止，已安裝版本維持不變。
- 全部成功後，`plugins.version` 更新為可用版本。

## 啟用相容性檢查

WNCMS 在外掛啟用前會依據 `plugin.json` 驗證依賴與版本相容性。

- 缺少必要依賴外掛時，啟用會失敗。
- 必要依賴存在但未啟用時，啟用會失敗。
- 依賴外掛版本不符合約束時，啟用會失敗。

`plugin.json` 支援以下 `dependencies` 格式：

```json
{
  "dependencies": ["plugin-a", "plugin-b"]
}
```

```json
{
  "dependencies": {
    "plugin-a": "^1.2",
    "plugin-b": ">=2.0 <3.0"
  }
}
```

```json
{
  "dependencies": [
    { "id": "plugin-a", "version": "^1.2" },
    { "id": "plugin-b", "version": "~2.3" }
  ]
}
```

支援的版本約束寫法：

- 精確版本：`1.2.3`
- 比較符：`>=1.2`、`<=2.0`、`!=1.4.0`
- 範圍（空格/逗號分隔）：`>=1.2 <2.0`
- Caret：`^1.2`
- Tilde：`~1.4`

## 停用安全檢查

WNCMS 在停用外掛前會檢查是否有其他已啟用外掛依賴它。

- 若發現已啟用的依賴方外掛，將阻擋停用。
- 錯誤訊息會列出依賴方外掛 id，並提示先停用這些外掛。

## 後台外掛列表顯示

後台外掛列表新增 `依賴外掛` 欄位。

- 顯示依賴外掛 id。
- 若有版本約束，會顯示為 `plugin_id (constraint)`。
- 外掛索引頁統一在 `備註` 欄位提供單一詳情按鈕顯示診斷資訊。
- 點擊後會開啟一個 modal，將 `最近載入錯誤`、`來源檔案` 與原始 `備註` 合併為終端風格區塊顯示。
- 索引頁隱藏 `狀態` 欄位，可透過操作按鈕（`啟用` / `停用`）判斷目前狀態。
- `URL`、`路徑` 與 `依賴外掛` 僅在開啟 `show_detail` 時顯示。

載入失敗備註會使用結構化格式儲存：

```text
[LOAD_ERROR] YYYY-MM-DD HH:MM:SS {error_message} | source_file={absolute_file_path}
```

## 建議閱讀

- [建立基礎外掛](./create-a-basic-plugin.md)
- [開發者 Event 概覽](../event/overview.md)
- [開發者 Command 概覽](../command/overview.md)
- [開發者 Locale Translation Files](../locale/translation-files.md)
