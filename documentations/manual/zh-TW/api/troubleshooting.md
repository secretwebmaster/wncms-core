# API 疑難排解

當 API 請求失敗時，請先執行這份檢查清單。

## 快速檢查清單

1. 確認全域 API 開關已啟用：`enable_api_access`。
2. 確認模型層 API 開關已啟用（例如：`enable_api_post`、`enable_api_website`）。
3. 確認端點層開關已啟用（例如：`wncms_api_post_index`）。
4. 確認端點驗證模式（`none`、`API Token`、`Basic`）與您的請求一致。
5. 如果 `api_access_whitelist` 不為空，確認請求 IP 或 `Origin`/`Referer` 主機已列入白名單。
6. 對於 `API Token` 模式，確認 `api_token` 存在且屬於有效使用者。
7. 對於 `Basic` 模式，確認 `Authorization: Basic ...` 標頭包含有效的 `email:password`。
8. 確認請求方法與端點路徑正確。
9. 檢查請求欄位名稱與資料型別。

## 常見狀態

- `401`：缺少或無效 API 憑證。
- `403`：API 開關關閉或白名單檢查失敗。
- `404`：路徑錯誤或網域錯誤。
- `422`：驗證失敗。
- `500`：伺服器異常。

## 相關頁面

- [錯誤碼](./errors.md)
- [身份驗證](./authentication.md)
- [核心概念](./core-concepts.md)
