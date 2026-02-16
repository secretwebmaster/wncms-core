# 开发命令总览

本页说明 WNCMS 常用开发脚手架命令。

## `wncms:create-model`

在宿主项目中创建模型脚手架（模型、迁移、后台控制器、starter 视图、权限）。

```bash
php artisan wncms:create-model Novel
```

行为摘要：
- 当不存在时生成 `app/Models/Novel.php`。
- 生成 `novels` 表迁移文件。
- 生成 `app/Http/Controllers/Backend/NovelController.php`。
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

## 故障排查

- `Source view file not found`：
  检查包内 `resources/views/backend/starters` 是否存在 starter blade 文件。
- 命令未创建视图：
  确认 `resources/views/backend/{plural}/` 下目标文件不是已存在状态。
- 路由权限被拒绝：
  重新执行 `wncms:create-model-permission {model}`，并在后台确认角色已分配对应权限。
- 升级项目中 Link 后台路由权限被拒绝：
  升级到 core `6.1.9+` 并执行 `php artisan wncms:update core`，更新流程会自动补齐 Link 权限。
