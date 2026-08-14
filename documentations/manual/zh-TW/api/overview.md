# API 總覽

WNCMS 提供全面的 RESTful API，讓您能夠以程式化方式與內容管理系統互動。該 API 讓您能夠建立、讀取、更新和刪除文章、頁面、選單、標籤及其他資源。

## 基礎 URL

所有 API 請求應發送至：

```
https://your-domain.com/api/v1
```

## API 版本

目前 API 版本：**v1**

版本包含在 URL 路徑中，以確保在發布新版本時的向後相容性。

WNCMS 也提供 **v2** 路由群組給新版管理後台：

- `/api/v2/backend/*`：已驗證的後台操作
- `/api/v2/frontend/*`：前台側 v2 端點
- `/api/v2/translations`：依 namespace/group 讀取翻譯字典（例如 `namespace=wncms&group=word`）

## API v2 Contract Kernel

API v2 現在為需要建立獨立 admin application 的 client 提供一套由 registry
驅動的契約：

- [API v2 契約](./contracts.md)說明身份驗證、middleware、六 key envelope、
  query option 與 `If-Match` revision。
- [Runtime Capabilities](./capabilities.md)說明
  `GET /api/v2/capabilities` 與 actor 專屬的 permission/website 過濾。
- [OpenAPI 3.1](./openapi.md)說明 `GET /api/v2/openapi.json` 與五個
  `x-wncms-*` operation extension。
- [非同步 Operations](./operations.md)說明 operation state、TTL、cancellation
  與 idempotent replay。

Legacy v2 operation 會繼續供 client 探索，但分類為 `legacy_resource`、
`legacy_controller`、`legacy_bridge` 的 operation 不滿足最終 v7 domain parity。

## Links Backend API v2 參考資源

`/api/v2/backend/links` 是受保護的 API v2 參考資源，提供網站範圍內的列表、
ID/slug 查看，以及預覽優先的 create、patch、delete、原子 bulk update 與
原子 bulk tag sync。

所有 mutation 都使用已驗證 bearer token 使用者作為 actor，預設以 HTTP `202`
預覽；只有 `force=true` 且 `dry_run` 不為 true 時才會寫入。成功寫入會建立
`surface=api_v2` 的 `mutation_audits`。受保護的 Links bulk delete 尚未提供，
因此 Links API v2 僅因這個明確缺口維持 Partial。

完整路由、權限、篩選、payload 與回應 envelope 請參閱
[Links API v2 端點](./endpoints/links.md)。

## 功能特色

- **文章管理**：建立、更新、刪除和檢索文章，並提供進階篩選功能
- **頁面管理**：管理網站頁面
- **選單管理**：同步和檢索選單結構
- **標籤管理**：建立和管理分類與標籤
- **更新功能**：觸發和監控系統更新
- **彈性身份驗證**：支援多種身份驗證方法
- **統一回應格式**：所有端點返回標準化的 JSON 回應
- **分頁支援**：列表端點內建分頁功能
- **篩選與排序**：資料檢索的進階查詢選項

## 快速開始

1. **取得 API Token**：從管理後台的使用者個人資料中產生 API token
2. **發出第一個請求**：使用 token 來驗證您的 API 呼叫

```bash
curl -X GET "https://your-domain.com/api/v1/posts" \
  -H "Content-Type: application/json" \
  -d '{"api_token": "your-api-token-here"}'
```

3. **處理回應**：所有回應都遵循一致的格式

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched posts",
  "data": [...],
  "extra": {}
}
```

## 身份驗證

WNCMS API 支援多種身份驗證方法：

- **簡易驗證**：在請求主體或查詢參數中使用 `api_token`
- **基本驗證**：使用 `email:password` 的標準 HTTP 基本驗證（在啟用時）
- **無需驗證**：某些端點可能根據設定公開存取
- **白名單閘道**：當 `api_access_whitelist` 不為空時，請求 IP 或 `Origin`/`Referer` 主機也必須符合

詳細資訊請參閱[身份驗證](./authentication.md)。

## 速率限制

API v2 位於 Laravel `api` middleware group 內。Server 設定 limiter 後，超過
限制的請求會回傳標準六 key envelope、HTTP `429`，以及
`meta.error_code: "rate_limit.exceeded"`。Client 應遵守回應中的 retry 與
rate-limit header。

## 回應格式

所有 API 端點都返回具有以下結構的 JSON 回應：

```json
{
  "code": 200,
  "status": "success",
  "message": "Description of the result",
  "data": {},
  "extra": {}
}
```

更多詳情請參閱[核心概念](./core-concepts.md)。

## 可用資源

| 資源     | 說明                 | 端點      |
| -------- | -------------------- | --------- |
| **文章** | 管理部落格文章和文章 | `/posts`  |
| **頁面** | 管理網站頁面         | `/pages`  |
| **選單** | 管理導覽選單         | `/menus`  |
| **標籤** | 管理分類和標籤       | `/tags`   |
| **網站** | 管理網站網域         | `/websites` |
| **更新** | 系統更新操作         | `/update` |

## 下一步

- [入門指南](./getting-started.md) - 學習如何驗證並發出您的第一個 API 呼叫
- [核心概念](./core-concepts.md) - 了解回應格式、分頁和錯誤處理
- [API 參考](./endpoints/posts.md) - 每個端點的詳細文件
- [範例](./examples.md) - 常見用例的程式碼範例
## v7 安全指南

- [Sessions](./sessions.md)
- [Service tokens](./service-tokens.md)
- [安全政策](./security-policy.md)
- [API-only mode](./api-only-mode.md)
- [Legacy authentication migration](./legacy-authentication.md)
