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

## 建議閱讀

- [建立基礎外掛](./create-a-basic-plugin.md)
- [開發者 Event 概覽](../event/overview.md)
- [開發者 Command 概覽](../command/overview.md)
- [開發者 Locale Translation Files](../locale/translation-files.md)
