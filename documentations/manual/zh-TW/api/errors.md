# 錯誤參考

WNCMS API 錯誤代碼的完整指南以及如何處理它們。

## API v2 錯誤契約

所有使用 envelope 的 API v2 失敗回應都具有相同六個頂層 key，並在
`meta.error_code` 提供穩定錯誤碼。`meta.request_id` 永遠與回應 header
`X-Request-ID` 相同，client 應把它寫入 log。

```json
{
  "code": 409,
  "status": "fail",
  "message": "The resource has changed since it was loaded.",
  "data": null,
  "meta": {
    "request_id": "123e4567-e89b-42d3-a456-426614174000",
    "error_code": "request.conflict"
  },
  "errors": []
}
```

| 錯誤碼 | HTTP | 含義 |
| --- | --- | --- |
| `authentication.missing_token` | `401` | 沒有已驗證 session 或 bearer token |
| `authentication.invalid_token` | `401` | Token 無效或 token user 不存在 |
| `authentication.access_token_expired` | `401` | Access token 或有明確期限的 service token 已過期 |
| `authentication.token_revoked` | `401` | Token 或 interactive session 已撤銷 |
| `authorization.ability_denied` | `403` | Credential 未授予路由所需 ability |
| `authorization.permission_denied` | `403` | Actor 目前沒有 WNCMS permission |
| `authorization.denied` | `403` | Legacy API gate 或 policy 拒絕請求 |
| `website.scope_missing` | `403` | 穩定的網站 selector 缺少或格式錯誤 |
| `website.scope_denied` | `403` | 明確指定的網站超出 token 或 actor scope |
| `resource.not_found` | `404` | Resource 不存在或刻意對該 actor 隱藏 |
| `website.context_missing` | `409` | Website-scoped operation 沒有目前 website |
| `request.conflict` | `409` | Stale revision、無效狀態轉換或並行變更 |
| `validation.failed` | `422` | Field 或 query 驗證失敗；細節位於 `errors` |
| `idempotency.key_missing` | `400` | 缺少必要的 `Idempotency-Key` |
| `idempotency.key_invalid` | `400` | Key 不是有效 UTF-8，或不在 `8..255` bytes 範圍內 |
| `idempotency.payload_invalid` | `400` | 無法安全產生 request input fingerprint |
| `idempotency.key_conflict` | `409` | 相同 key 搭配不同輸入使用 |
| `idempotency.in_progress` | `409` | 相同 scope 的 mutation 正在執行 |
| `idempotency.operation_missing` | `500` | Idempotent route 缺少已註冊 operation identity |
| `rate_limit.exceeded` | `429` | 超過已設定的 Laravel API rate limit |
| `server.unexpected_error` | `5xx` | 非預期 server failure；非 debug 模式會隱藏細節 |

Idempotent exact replay 會保留第一次 response body 與 request ID，並加入
`Idempotency-Replayed: true`。HTTP `5xx` 不會被 cache，可使用相同 key 重試。
另請參閱[非同步 Operations](./operations.md)。

## HTTP 狀態碼

| 代碼 | 狀態                  | 說明                       |
| ---- | --------------------- | -------------------------- |
| 200  | OK                    | 請求成功                   |
| 400  | Bad Request           | 無效的請求參數             |
| 401  | Unauthorized          | 需要身份驗證或身份驗證失敗 |
| 403  | Forbidden             | API 存取已停用或權限不足   |
| 404  | Not Found             | 資源未找到                 |
| 409  | Conflict              | 狀態、revision 或 idempotency 衝突 |
| 429  | Too Many Requests     | 超過已設定的 API rate limit |
| 422  | Unprocessable Entity  | 驗證失敗                   |
| 500  | Internal Server Error | 發生伺服器端錯誤           |

## 常見錯誤訊息

### 身份驗證錯誤

#### 無效 Token

```json
{
  "status": "fail",
  "message": "Invalid token"
}
```

**原因：**

- API token 不正確
- API token 已被撤銷
- 使用者帳戶已停用

**解決方案：**

1. 驗證您的 API token 是否正確
2. 從管理後台重新產生 token
3. 檢查使用者帳戶狀態

---

#### API 存取已停用

```json
{
  "status": 403,
  "message": "API access is disabled"
}
```

**原因：**

- 全域 API 已停用
- 特定端點透過功能開關停用

**解決方案：**

1. 在 WNCMS 設定中啟用 API
2. 檢查端點特定設定（例如 `wncms_api_posts_index`）
3. 聯繫系統管理員

---

#### 需要管理員存取權限

```json
{
  "status": "fail",
  "message": "Admin access required"
}
```

**原因：**

- 端點需要管理員角色
- 目前使用者不是管理員

**解決方案：**

1. 使用管理員使用者的 API token
2. 請求管理員權限
3. 使用替代的非管理員端點

---

### 驗證錯誤

#### 缺少必填欄位

```json
{
  "code": 422,
  "status": "fail",
  "message": "Validation failed",
  "data": {
    "errors": {
      "title": ["The title field is required."],
      "content": ["The content field is required."]
    }
  }
}
```

**原因：**

- 未提供必填欄位
- 必填欄位為空值

**解決方案：**

1. 檢查 API 文件以了解必填欄位
2. 確保所有必填欄位都有值
3. 在發送前驗證資料

## 錯誤處理模式

### 基本錯誤處理程式

```javascript
async function apiCall(url, payload) {
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    const result = await response.json()

    if (result.status === 'success') {
      return result.data
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('API Error:', error.message)
    throw error
  }
}
```

## 相關文件

- [核心概念](./core-concepts.md) - 回應格式和錯誤處理
- [疑難排解](./troubleshooting.md) - 常見問題和解決方案
- [範例](./examples.md) - 錯誤處理的程式碼範例
