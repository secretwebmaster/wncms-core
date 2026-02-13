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

## 前台注册邮箱格式校验

前台注册现在会阻止将非邮箱格式内容写入 `email` 字段。

## 生效范围

- 前台注册提交：`POST /user/register/submit`（`frontend.users.register.submit`）
- 控制器：`src/Http/Controllers/Frontend/UserController.php`

## 验证行为

- 当提供 `email` 时，必须通过 Laravel `email` 格式校验。
- 当未提供 `email` 时，系统会使用净化后的 `username` 与 `request()->getHost()`（不含端口）生成回退邮箱。
- 创建用户前，重复检查会基于最终计算出的 `username` 与 `email` 执行。

## 实际示例

- 输入 `username=john`、`email=abc` 会校验失败，并返回 `wncms::word.please_enter_a_valid_email`。
- 输入 `username=john`、留空 `email` 会生成回退邮箱，如 `john@example.com`。
