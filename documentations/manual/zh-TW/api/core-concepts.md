# 核心概念

了解這些核心概念將幫助您有效地使用 WNCMS API。

## 回應格式

所有 API 端點都返回一致的 JSON 結構：

```json
{
  "code": 200,
  "status": "success",
  "message": "Description of the operation",
  "data": {},
  "extra": {}
}
```

### 回應欄位

| 欄位      | 類型    | 說明                                 |
| --------- | ------- | ------------------------------------ |
| `code`    | integer | HTTP 狀態碼（200、400、403、500 等） |
| `status`  | string  | 操作狀態：「success」或「fail」      |
| `message` | string  | 描述結果的人類可讀訊息               |
| `data`    | mixed   | 實際的回應資料（物件、陣列或 null）  |
| `extra`   | object  | 額外的中繼資料（可選）               |

### 成功回應範例

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched posts",
  "data": {
    "data": [...],
    "pagination": {...}
  },
  "extra": {}
}
```

### 錯誤回應範例

```json
{
  "code": 403,
  "status": "fail",
  "message": "API access is disabled",
  "data": null,
  "extra": {}
}
```

## 身份驗證

WNCMS API 支援每個端點的可配置身份驗證。

### 簡易驗證 (api_token)

最常見的方法。在請求主體中包含您的 API token：

```json
{
  "api_token": "your-api-token-here",
  "other_params": "..."
}
```

### Token 產生

1. 登入 WNCMS 管理後台
2. 導覽至您的使用者個人資料
3. 找到 API Token 區塊
4. 產生或複製現有的 token

### Token 安全性

:::warning 安全性最佳實務

- 切勿在客戶端程式碼中公開 API token
- 使用環境變數來儲存 token
- 定期輪換 token
- 對所有 API 請求使用 HTTPS
- 在應用程式中實作速率限制
  :::

### 檢查身份驗證要求

每個端點可能有不同的身份驗證設定。如果身份驗證失敗：

```json
{
  "status": "fail",
  "message": "Invalid token"
}
```

## 分頁

返回多個項目的列表端點包含分頁資料。

### 分頁參數

在請求中包含這些參數以控制分頁：

| 參數        | 類型    | 預設值 | 說明       |
| ----------- | ------- | ------ | ---------- |
| `page_size` | integer | 15     | 每頁項目數 |
| `page`      | integer | 1      | 目前頁碼   |

請求範例：

```json
{
  "api_token": "your-api-token-here",
  "page_size": 20,
  "page": 2
}
```

### 分頁中繼資料

回應包含全面的分頁資訊：

```json
{
  "code": 200,
  "status": "success",
  "data": {
    "data": [...],
    "pagination": {
      "total": 150,
      "count": 20,
      "page_size": 20,
      "current_page": 2,
      "last_page": 8,
      "has_more": true,
      "next": "/api/v1/posts?page=3",
      "previous": "/api/v1/posts?page=1"
    }
  }
}
```

### 分頁欄位說明

| 欄位           | 說明                              |
| -------------- | --------------------------------- |
| `total`        | 所有頁面的總項目數                |
| `count`        | 目前頁面的項目數                  |
| `page_size`    | 每頁最大項目數                    |
| `current_page` | 目前頁碼                          |
| `last_page`    | 最後可用的頁碼                    |
| `has_more`     | 指示是否有更多頁面的布林值        |
| `next`         | 下一頁的 URL（如果沒有則為 null） |
| `previous`     | 上一頁的 URL（如果沒有則為 null） |

## 篩選與排序

許多端點支援篩選和排序選項。

### 常見篩選參數

不同端點支援不同的篩選器。常見的包括：

```json
{
  "api_token": "your-api-token-here",
  "keywords": "search term",
  "tags": [1, 2, 3],
  "tag_type": "post_category",
  "excluded_post_ids": [5, 10, 15]
}
```

### 排序

控制結果的順序：

```json
{
  "api_token": "your-api-token-here",
  "sort": "created_at",
  "direction": "desc"
}
```

常見排序欄位：

- `created_at` - 建立日期
- `updated_at` - 最後修改日期
- `title` - 按標題字母順序
- `sort` - 手動排序順序

常見方向：

- `desc` - 降序（最新/最高優先）
- `asc` - 升序（最舊/最低優先）

### 隨機結果

某些端點支援隨機排序：

```json
{
  "api_token": "your-api-token-here",
  "is_random": true,
  "page_size": 5
}
```

## 錯誤處理

### HTTP 狀態碼

| 代碼 | 狀態           | 意義                     |
| ---- | -------------- | ------------------------ |
| 200  | 成功           | 請求成功完成             |
| 400  | 錯誤請求       | 無效的請求參數           |
| 401  | 未授權         | 需要身份驗證             |
| 403  | 禁止           | API 存取已停用或權限不足 |
| 404  | 未找到         | 資源未找到               |
| 422  | 無法處理的實體 | 驗證失敗                 |
| 500  | 伺服器錯誤     | 內部伺服器錯誤           |

### 錯誤回應格式

錯誤遵循相同的回應結構：

```json
{
  "code": 500,
  "status": "fail",
  "message": "Server Error: Database connection failed",
  "data": null,
  "extra": {}
}
```

### 驗證錯誤

當驗證失敗時（422），回應包含詳細資訊：

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

### 在程式碼中處理錯誤

#### JavaScript 範例

```javascript
try {
  const response = await fetch('https://your-domain.com/api/v1/posts', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ api_token: 'your-token' }),
  })

  const result = await response.json()

  if (result.status === 'fail') {
    console.error('API Error:', result.message)
    // 處理錯誤
  } else {
    console.log('Success:', result.data)
    // 處理成功
  }
} catch (error) {
  console.error('Network Error:', error)
}
```

#### PHP 範例

```php
try {
    $response = // ... 發送 API 請求
    $result = json_decode($response, true);

    if ($result['status'] === 'fail') {
        throw new Exception($result['message']);
    }

    return $result['data'];
} catch (Exception $e) {
    logger()->error('API Error: ' . $e->getMessage());
    return null;
}
```

## 功能開關

API 端點可以透過 WNCMS 設定啟用/停用。每個端點檢查自己的設定：

- `wncms_api_posts_index` - 控制文章列表
- `wncms_api_posts_store` - 控制文章建立
- `wncms_api_tag_store` - 控制標籤建立
- 等等

### System Settings API 分頁中的套件感知標籤

在 **System Settings -> API** 中，每個端點標籤現在會依路由所屬套件解析翻譯：

- 模型 `$apiRoutes` 可選擇帶入 `package_id`。
- 若未帶入，WNCMS 會回退到模型的 package ID。
- 若仍為空，最後回退到 `wncms`。

模型 API 路由設定範例：

```php
protected static array $apiRoutes = [
    [
        'name' => 'api.v1.tags.store',
        'key' => 'wncms_api_tag_store',
        'action' => 'store',
        'package_id' => 'your-package-id',
    ],
];
```

當停用時，您將收到：

```json
{
  "status": 403,
  "message": "API access is disabled"
}
```

## 快取

WNCMS 實作快取以提升效能。在建立或更新資源後：

- 快取會自動清除
- 後續請求將反映變更
- 無需手動清除快取

## 國際化 (i18n)

某些端點支援語言環境參數：

```json
{
  "api_token": "your-api-token-here",
  "locale": "zh-TW"
}
```

支援的語言環境取決於您的 WNCMS 安裝設定。

## 最佳實務

### 1. 始終檢查回應狀態

```javascript
if (result.status === 'success') {
  // 處理資料
} else {
  // 處理錯誤
}
```

### 2. 實作適當的錯誤處理

不要假設請求總是會成功。適當地處理網路錯誤、API 錯誤和驗證錯誤。

### 3. 對大型資料集使用分頁

只請求您需要的內容以提升效能：

```json
{
  "page_size": 20,
  "page": 1
}
```

### 4. 適當時快取回應

如果資料不經常變更，在您這邊快取回應以減少 API 呼叫。

### 5. 在發送前驗證輸入

在發送 API 請求之前檢查必填欄位和資料類型，以避免不必要的驗證錯誤。

### 6. 使用 HTTPS

始終使用 HTTPS 來保護 API token 和傳輸中的敏感資料。

### 7. 監控 API 使用情況

追蹤您的 API 呼叫以識別效能問題或潛在的改進。

## 下一步

- 探索[文章 API 參考](./endpoints/posts.md)以獲得詳細的端點文件
- 查看[範例](./examples.md)以了解常見用例實作
- 查閱[錯誤參考](./errors.md)以獲得完整的錯誤代碼列表
- 查看[疑難排解](./troubleshooting.md)以獲得常見問題的解決方案
