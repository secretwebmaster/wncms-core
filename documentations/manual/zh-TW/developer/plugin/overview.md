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

外掛可透過標準化 `Plugin.php`（繼承 `Wncms\Plugins\AbstractPlugin`）實作生命週期。

- 額外 class 由根目錄 `Plugin.php` 統一載入。
- `system/events.php` 僅放 listener，`system/functions.php` 僅放 helper 函數。

- `init()`：註冊執行期 hooks/events。
- `activate()`：執行啟用邏輯（例如寫入預設 setting）。
- `deactivate()`：停用外掛時執行。
- `delete()`：刪除外掛時執行。

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

## 建議閱讀

- [建立基礎外掛](./create-a-basic-plugin.md)
- [開發者 Event 概覽](../event/overview.md)
- [開發者 Command 概覽](../command/overview.md)
- [開發者 Locale Translation Files](../locale/translation-files.md)
