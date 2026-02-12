# 錯誤參考

WNCMS API 錯誤代碼的完整指南以及如何處理它們。

## HTTP 狀態碼

| 代碼 | 狀態                  | 說明                       |
| ---- | --------------------- | -------------------------- |
| 200  | OK                    | 請求成功                   |
| 400  | Bad Request           | 無效的請求參數             |
| 401  | Unauthorized          | 需要身份驗證或身份驗證失敗 |
| 403  | Forbidden             | API 存取已停用或權限不足   |
| 404  | Not Found             | 資源未找到                 |
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
