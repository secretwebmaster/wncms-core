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

## 插件升级生命周期

已安装版本存储于 `plugins.version`。
可用版本读取自 `public/plugins/{plugin_id}/plugin.json` 的 `version`。

- 若 `plugin.json` 版本高于 `plugins.version`，则存在可用更新。
- 插件列表仅显示数据库字段。
- 直接修改 `plugin.json` 不会立即覆盖插件表中的显示字段。
- 升级通过显式操作执行（后台升级按钮），成功后再同步 manifest 信息到插件表。
- `插件列表` 仅显示 `plugins` 表中已有记录的插件。
- 在 `public/plugins` 中存在但没有匹配 `plugin_id` 记录的插件，会显示在独立的 `原始插件` 表格中。
- 首次启用创建记录后，该插件会显示在常规 `插件列表` 表格中。

### 升级定义（仅 deterministic map）

在插件生命周期类中定义：

```php
public array $upgrades = [
    '1.2.0' => 'upgrade_1_2_0.php',
    '1.3.0' => 'upgrade_1_3_0.php',
];
```

执行规则：

- 仅执行 `$upgrades` 显式声明的步骤。
- key 为目标版本。
- 按版本升序执行。
- 运行条件：`installed_version < target_version <= available_version`。
- 若可用版本更高但升级链无法到达该版本，则升级失败。
- 任一步骤失败立即停止，已安装版本保持不变。
- 全部成功后，`plugins.version` 更新为可用版本。

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
- 插件索引页统一在 `备注` 字段提供单一详情按钮显示诊断信息。
- 点击后会打开一个 modal，将 `最近载入错误`、`来源文件` 与原始 `备注` 合并为终端风格区块显示。
- 索引页隐藏 `状态` 字段，可通过操作按钮（`启用` / `停用`）判断当前状态。
- `URL`、`路径` 与 `依赖插件` 仅在开启 `show_detail` 时显示。

载入失败备注会使用结构化格式储存：

```text
[LOAD_ERROR] YYYY-MM-DD HH:MM:SS {error_message} | source_file={absolute_file_path}
```

## 建议阅读

- [建立基础插件](./create-a-basic-plugin.md)
- [开发者 Event 概览](../event/overview.md)
- [开发者 Command 概览](../command/overview.md)
- [开发者 Locale Translation Files](../locale/translation-files.md)
