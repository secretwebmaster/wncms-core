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

## 启用相容性检查

WNCMS 在插件启用前会根据 `plugin.json` 验证依赖与版本相容性。

- 缺少必需依赖插件时，启用会失败。
- 必需依赖存在但未启用时，启用会失败。
- 依赖插件版本不满足约束时，启用会失败。

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

支援的版本约束写法：

- 精确版本：`1.2.3`
- 比较符：`>=1.2`、`<=2.0`、`!=1.4.0`
- 范围（空格/逗号分隔）：`>=1.2 <2.0`
- Caret：`^1.2`
- Tilde：`~1.4`

## 停用安全检查

WNCMS 在停用插件前会检查是否有其他启用中的插件依赖它。

- 若发现启用中的依赖方插件，则阻止停用。
- 错误信息会列出依赖方插件 id，并提示先停用这些插件。

## 后台插件列表显示

后台插件列表新增 `依赖插件` 字段。

- 显示依赖插件 id。
- 若有版本约束，会显示为 `plugin_id (constraint)`。

## 建议阅读

- [建立基础插件](./create-a-basic-plugin.md)
- [开发者 Event 概览](../event/overview.md)
- [开发者 Command 概览](../command/overview.md)
- [开发者 Locale Translation Files](../locale/translation-files.md)
