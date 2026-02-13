# Users

後台使用者管理在新增與編輯時已強制 `username` 與 `email` 唯一。

## 生效範圍

- 後台新增：`POST /panel/users/store`（`users.store`，權限 `user_create`）
- 後台編輯：`PATCH /panel/users/{id}`（`users.update`，權限 `user_edit`）
- 控制器：`src/Http/Controllers/Backend/UserController.php`

## 驗證行為

- `username` 必填，且在 `users.username` 中必須唯一。
- `email` 必填、格式必須為有效信箱，且在 `users.email` 中必須唯一。
- 編輯模式會排除目前使用者自身資料，不會誤判為重複。

錯誤訊息使用：

- `wncms::word.username_has_been_used`
- `wncms::word.email_has_been_used`

## 實際範例

如果使用者 `A` 已存在 `username=alex`，再建立或編輯另一個使用者為 `alex` 時會驗證失敗並回傳錯誤，不會儲存重複資料。
