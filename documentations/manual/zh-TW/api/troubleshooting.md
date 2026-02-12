# API 疑難排解

當 API 請求失敗時，請先執行這份檢查清單。

## 快速檢查清單

1. 確認全域 API 開關已啟用：`enable_api_access`。
2. 確認模型層 API 開關已啟用（例如：`enable_api_post`、`enable_api_website`）。
3. 確認端點層開關已啟用（例如：`wncms_api_post_index`）。
4. 確認 `api_token` 存在且屬於有效使用者。
5. 確認請求方法與端點路徑正確。
6. 檢查請求欄位名稱與資料型別。

## 常見狀態

- `401`：缺少或無效 token。
- `403`：API 開關關閉。
- `404`：路徑錯誤或網域錯誤。
- `422`：驗證失敗。
- `500`：伺服器異常。

## 相關頁面

- [錯誤碼](./errors.md)
- [身份驗證](./authentication.md)
- [核心概念](./core-concepts.md)
