# Users

后台用户管理在新增与编辑时已强制 `username` 与 `email` 唯一。

## 生效范围

- 后台新增：`POST /panel/users/store`（`users.store`，权限 `user_create`）
- 后台编辑：`PATCH /panel/users/{id}`（`users.update`，权限 `user_edit`）
- 控制器：`src/Http/Controllers/Backend/UserController.php`

## 验证行为

- `username` 必填，且在 `users.username` 中必须唯一。
- `email` 必填、格式必须为有效邮箱，且在 `users.email` 中必须唯一。
- 编辑模式会排除当前用户自身记录，不会误判为重复。

错误信息使用：

- `wncms::word.username_has_been_used`
- `wncms::word.email_has_been_used`

## 实际示例

如果用户 `A` 已存在 `username=alex`，再创建或编辑另一个用户为 `alex` 时会验证失败并返回错误，不会保存重复数据。
