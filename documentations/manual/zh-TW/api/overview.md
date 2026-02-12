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
- **基本驗證**：標準 HTTP 基本驗證（在啟用時）
- **無需驗證**：某些端點可能根據設定公開存取

詳細資訊請參閱[身份驗證](./authentication.md)。

## 速率限制

目前 API 沒有強制的速率限制。但我們建議在客戶端實作您自己的速率限制，以防止過多的請求。

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
