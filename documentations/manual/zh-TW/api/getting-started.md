# 入門指南

本指南將引導您完成對 WNCMS 的第一個 API 請求。

## 先決條件

在開始之前，請確保您擁有：

- 已啟用 API 存取的 WNCMS 安裝
- 用於產生 API token 的管理員存取權限
- 用於發送 HTTP 請求的工具（curl、Postman 或您的程式語言的 HTTP 客戶端）

## 步驟 1：產生 API Token

1. 登入您的 WNCMS 管理後台
2. 導覽至您的使用者個人資料設定
3. 找到「API Token」區塊
4. 如果您還沒有，請點擊「產生 Token」
5. 複製您的 API token - 您將需要它進行身份驗證

:::warning 安全注意事項
請妥善保管您的 API token。切勿將其提交至版本控制或在客戶端程式碼中公開。
:::

## 步驟 2：測試 API 連線

發送一個簡單的 GET 請求以驗證 API 是否可存取：

```bash
curl -X GET "https://your-domain.com/api/v1/posts" \
  -H "Content-Type: application/json" \
  -d '{"api_token": "your-api-token-here"}'
```

您應該會收到一個包含文章列表的 JSON 回應（如果沒有文章則為空陣列）。

## 步驟 3：了解回應

成功的回應將如下所示：

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched posts",
  "data": {
    "data": [
      {
        "id": 1,
        "title": "Sample Post",
        "slug": "sample-post",
        "content": "Post content here...",
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "pagination": {
      "total": 1,
      "count": 1,
      "page_size": 15,
      "current_page": 1,
      "last_page": 1,
      "has_more": false
    }
  },
  "extra": {}
}
```

重要欄位：

- `code`：HTTP 狀態碼
- `status`：「success」或「fail」
- `message`：人類可讀的訊息
- `data`：實際的回應資料
- `extra`：額外的中繼資料（可選）

## 步驟 4：建立您的第一篇文章

現在讓我們使用 API 建立一篇新文章：

```bash
curl -X POST "https://your-domain.com/api/v1/posts/store" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "title": "My First API Post",
    "content": "This post was created via the WNCMS API!"
  }'
```

成功回應：

```json
{
  "code": 200,
  "status": "success",
  "message": "Post #123 created successfully",
  "data": {
    "id": 123,
    "title": "My First API Post",
    "slug": "my-first-api-post",
    "content": "This post was created via the WNCMS API!",
    "created_at": "2024-01-15T10:30:00.000000Z"
  },
  "extra": {}
}
```

## 步驟 5：檢索特定文章

獲取您剛建立的文章：

```bash
curl -X POST "https://your-domain.com/api/v1/posts/my-first-api-post" \
  -H "Content-Type: application/json" \
  -d '{"api_token": "your-api-token-here"}'
```

## 常見模式

### 身份驗證

大多數端點需要身份驗證。在請求主體中包含您的 API token：

```json
{
  "api_token": "your-api-token-here",
  "other_param": "value"
}
```

### 分頁

列表端點支援分頁參數：

```json
{
  "api_token": "your-api-token-here",
  "page_size": 20,
  "page": 2
}
```

### 篩選

使用查詢參數來篩選結果：

```json
{
  "api_token": "your-api-token-here",
  "keywords": "search term",
  "tags": [1, 2, 3]
}
```

## 程式碼範例

### JavaScript (Fetch API)

```javascript
const response = await fetch('https://your-domain.com/api/v1/posts', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    api_token: 'your-api-token-here',
  }),
})

const result = await response.json()
console.log(result.data)
```

### PHP

```php
$ch = curl_init('https://your-domain.com/api/v1/posts');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'api_token' => 'your-api-token-here'
]));

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

print_r($result['data']);
```

### Python (requests)

```python
import requests

response = requests.get(
    'https://your-domain.com/api/v1/posts',
    headers={'Content-Type': 'application/json'},
    json={'api_token': 'your-api-token-here'}
)

result = response.json()
print(result['data'])
```

## 下一步

- 了解[核心概念](./core-concepts.md)，如分頁和錯誤處理
- 探索[文章 API](./endpoints/posts.md)的進階功能
- 查看[範例](./examples.md)了解常見用例
- 查閱[身份驗證](./authentication.md)了解安全性最佳實務

## 疑難排解

**API 返回 403「API access is disabled」**

- 檢查 WNCMS 設定中是否已啟用 API
- 驗證特定端點是否已啟用（例如 `wncms_api_posts_index`）

**API 返回「Invalid token」**

- 驗證您的 API token 是否正確
- 確保您在請求主體中包含了 token
- 檢查您的使用者帳戶是否仍然有效

**收到 404 錯誤**

- 驗證 API 基礎 URL 是否正確
- 確保您使用正確的 HTTP 方法（GET/POST）
- 檢查端點是否存在於您的 WNCMS 版本中

更多疑難排解提示，請參閱[疑難排解指南](./troubleshooting.md)。
