# 开发命令总览

本页说明 WNCMS 常用开发脚手架命令。

## `wncms:create-model`

在宿主项目中创建模型脚手架（模型、迁移、后台控制器、starter 视图、权限）。

```bash
php artisan wncms:create-model Novel
```

行为摘要：
- 当不存在时生成 `app/Models/Novel.php`。
- 生成的模型扩展 `Wncms\Models\BaseModel`，并包含 `modelKey` 兜底逻辑（留空时按类名自动推导）。
- 生成 `novels` 表迁移文件。
- 生成 `app/Http/Controllers/Backend/NovelController.php`。
- 生成的后端控制器方法签名与 `BackendController` 兼容（`create($id)`、`edit($id)`、`update(Request, $id)`、`destroy($id)`）。
- 调用 `wncms:create-model-view novel`。
- 调用 `wncms:create-model-permission novel`。
- 可选地将路由追加到 `routes/custom_backend.php`。

## `wncms:create-model-view`

基于 starter 模板为模型创建后台 blade 文件。

```bash
php artisan wncms:create-model-view novel
```

生成文件：
- `resources/views/backend/novels/index.blade.php`
- `resources/views/backend/novels/create.blade.php`
- `resources/views/backend/novels/edit.blade.php`
- `resources/views/backend/novels/form-items.blade.php`

Starter 模板路径解析顺序：
1. 包根目录下的 `resources/views/backend/starters`
2. 包根目录上一层的 `../resources/views/backend/starters`
3. 内部回退路径：`src/../../resources/views/backend/starters`

若未找到有效 starter 路径，命令会失败退出并输出所有已检查路径。

## `wncms:create-model-permission`

为模型 key 创建常用后台权限。

```bash
php artisan wncms:create-model-permission novel
```

常见权限后缀包括：
- `_index`
- `_create`
- `_clone`
- `_edit`
- `_delete`
- `_bulk_delete`

## `wncms:create-permission`

直接创建一个或多个权限，并可选择指派给角色。

```bash
php artisan wncms:create-permission article_publish
```

示例：

```bash
# 创建单个权限
php artisan wncms:create-permission article_publish

# 创建单个权限并指派给一个角色
php artisan wncms:create-permission article_publish editor

# 一次创建多个权限
php artisan wncms:create-permission article_publish,article_archive

# 创建多个权限并指派给多个角色
php artisan wncms:create-permission article_publish,article_archive editor,admin
```

行为摘要：
- `{permission_name}` 支持逗号分隔的权限名称。
- `{role}` 为可选参数，也支持逗号分隔的角色名称。
- 使用 `firstOrCreate` 创建缺失的权限。
- 使用 `firstOrCreate` 创建缺失的角色。
- 将所有提供的权限指派给所有提供的角色。

## `wncms:activate-plugin`

通过 CLI 启用插件，行为与后台启用一致（`status` => `active`）。

```bash
php artisan wncms:activate-plugin wncms-users-hook-test
```

行为摘要：
- 支援插件 `name`、`plugin_id` 或目录 `path` 作为输入。
- 会扫描 `public/plugins`，并将未入库的目录插件同步到 `plugins` 资料表。
- 若插件提供标准化主类，会先执行生命周期 `activate()`。
- 命中后将插件状态更新为 `active`。
- 若 `plugins` 资料表不存在或找不到目标插件，命令返回失败。

## `wncms:verify-plugin-hooks`

执行插件与 users hook 硬切迁移的发布闸门检查。

```bash
php artisan wncms:verify-plugin-hooks
```

行为摘要：
- 检查插件根目录（`public/plugins`）是否存在。
- 检查每个插件目录的 `plugin.json` 是否有效（必须含 `id`、`name`、`version`）。
- 检查核心用户控制器中是否仍存在 legacy users hook 名称。
- 检查 `plugins` 资料表是否存在，且不存在 `[MANIFEST_ERROR]` / `[LOAD_ERROR]` 记录。
- 任一闸门失败即返回失败（应阻止发布）。

## `wncms:hook-list`

用于插件开发的 hook/extension 注册表巡检命令。

```bash
php artisan wncms:hook-list
```

常见用法：

```bash
# 显示每个 hook 的 listener 详情
php artisan wncms:hook-list --listeners

# 只显示当前已有 listener 的 hook
php artisan wncms:hook-list --only-listened

# 输出 JSON 供自动化脚本使用
php artisan wncms:hook-list --json
```

行为摘要：
- 扫描 WNCMS 核心 `src`（以及宿主项目 `app`）中的 hook 派发点（`Event::dispatch(...)` / `event(...)`）。
- 列出每个 hook 的派发点数量与当前运行时 listener 数量。
- `--listeners` 可输出每个 hook 对应的 listener 标识。
- 同时输出 `macroable-models` 中已注册扩展（按模型分组的查询宏）。

预期输出格式（节选）：

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

## `wncms:install-default-theme`

安装或重新安装核心默认主题资源到 `public/themes`。

```bash
php artisan wncms:install-default-theme --force
```

行为摘要：
- 发布 `wncms-default-assets` 发布标签对应的资源。
- 适用于默认主题资源被修改、缺失或损坏后的恢复场景。
- 该命令也会被安装流程（CLI 与浏览器安装向导）通过共用安装逻辑调用。

## `wncms:install-agent-files`

将 WNCMS agent 文件安装到宿主项目根目录。

```bash
php artisan wncms:install-agent-files
```

常见用法：

```bash
# 不询问，直接覆盖所有已存在目标
php artisan wncms:install-agent-files --force

# 仅预览，不写入文件
php artisan wncms:install-agent-files --dry-run
```

行为摘要：
- 从包内 `resources/agent-files` 作为发布来源。
- 安装 `AGENTS.md` 与 `.github/skills` 到宿主项目根目录。
- 默认模式对已存在目标采用交互确认：
  - 询问是否覆盖 `AGENTS.md`
  - 询问是否覆盖 `.github/skills`
- `--force` 会直接覆盖已存在目标。
- `--dry-run` 仅输出计划动作，不会修改任何文件。

## `wncms:update-website`

通过 CLI 更新网站一笔栏位。

```bash
php artisan wncms:update-website {key} {value}
```

常见用法：

```bash
# 切换网站主题
php artisan wncms:update-website theme default

# 更新网站名称
php artisan wncms:update-website site_name "My Website"
```

行为摘要：
- 在 CLI 场景下更新当前网站；若无法按网域解析，则回退到第一笔网站记录。
- 会验证 `{key}` 是否为 `websites` 资料表真实栏位。
- 更新 `theme` 时，会自动补齐新主题缺失的预设 theme options。
- 更新后会清除 `websites` 快取标签。

## `wncms:update`

执行核心更新脚本。

```bash
# 常规更新流程（远端版本列表 + 递增执行）
php artisan wncms:update core

# 重新执行一个指定的本地更新文件
php artisan wncms:update --rerun-version=6.1.6
php artisan wncms:update --rerun-version=v6.1.6
```

行为摘要：
- `--rerun-version=` 会重新执行一个指定版本的本地更新脚本：
  - `updates/update_core_{version}.php`
- 支持 `v` 前缀（例如 `v6.1.6` 与 `6.1.6` 等价）。
- 若 `--rerun-version` 为空或在 `updates/` 中找不到对应文件，命令会返回失败。

## 安装方式（`wncms:install` + 浏览器向导）

WNCMS 支持两种安装入口：

1. CLI 命令：`php artisan wncms:install ...`
2. 浏览器向导：`/install/wizard`

两种方式现在都使用 `InstallerManager` 的同一套共用安装流程，因此以下步骤行为一致：
- 数据库连接检查
- 写入 `.env`
- 生成应用密钥
- 数据库初始化
- 发布资源（`wncms-core-assets`、`wncms-stubs`、`wncms-default-assets`）
- 初始化自定义语言/路由文件
- 初始化系统设置
- 写入安装标记并清理缓存

CLI 语言行为：
- `--app_locale=` 会控制安装器终端输出的语言。
- 示例：`--app_locale=zh_CN` 会以简体中文显示安装进度信息。
- 若 locale 为空或不受支持，安装器会回退到应用配置语言/默认支持语言。

### 多站点默认行为

- `multi_website` 默认值为 `false`。
- CLI：仅在传入 `--multi_website` 时启用多站点。
- 向导：仅在勾选复选框时启用多站点。

安装后可执行以下验证：

```bash
php artisan tinker
```

```php
gss('multi_website');
```

## 故障排查

- `Source view file not found`：
  检查包内 `resources/views/backend/starters` 是否存在 starter blade 文件。
- 命令未创建视图：
  确认 `resources/views/backend/{plural}/` 下目标文件不是已存在状态。
- 路由权限被拒绝：
  重新执行 `wncms:create-model-permission {model}`，并在后台确认角色已分配对应权限。
- 升级项目中 Link 后台路由权限被拒绝：
  升级到 core `6.1.9+` 并执行 `php artisan wncms:update core`，更新流程会自动补齐 Link 权限。
