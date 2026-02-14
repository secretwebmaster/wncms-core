# Plugin 开发概述

WNCMS 支援在 `public/plugins` 下建立专案级插件，不需要 Composer 注册。
本节说明如何建立含生命周期、hooks、views 与 translations 的基础插件。

## 建议结构

建议使用：

```
public/plugins/{plugin_id}/
├── plugin.json
├── Plugin.php
├── classes/（可选）
├── system/events.php
├── system/functions.php
├── routes/web.php
├── views/{backend|frontend|common}/...
└── lang/{en|zh_CN|zh_TW|ja}/word.php
```

## 插件生命周期

插件可透过标准化 `Plugin.php`（继承 `Wncms\Plugins\AbstractPlugin`）实现生命周期。

- 额外 class 由根目录 `Plugin.php` 统一载入。
- `system/events.php` 仅放 listener，`system/functions.php` 仅放 helper 函数。

- `init()`：注册运行时 hooks/events。
- `activate()`：执行启用逻辑（例如写入预设 setting）。
- `deactivate()`：停用插件时执行。
- `delete()`：删除插件时执行。

## 建议阅读

- [建立基础插件](./create-a-basic-plugin.md)
- [开发者 Event 概览](../event/overview.md)
- [开发者 Command 概览](../command/overview.md)
- [开发者 Locale Translation Files](../locale/translation-files.md)
