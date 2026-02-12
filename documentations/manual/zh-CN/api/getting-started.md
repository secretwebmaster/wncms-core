# 入门指南

本指南将引导您完成对 WNCMS 的第一个 API 请求。

## 先决条件

在开始之前，请确保您拥有：

- 已启用 API 存取的 WNCMS 安装
- 用于产生 API token 的管理员存取权限
- 用于发送 HTTP 请求的工具（curl、Postman 或您的程式语言的 HTTP 客户端）

## 步骤 1：产生 API Token

1. 登入您的 WNCMS 管理后台
2. 导览至您的使用者个人资料设定
3. 找到「API Token」区块
4. 如果您还没有，请点击「产生 Token」
5. 复制您的 API token - 您将需要它进行身份验证

:::warning 安全注意事项
请妥善保管您的 API token。切勿将其提交至版本控制或在客户端程式码中公开。
:::

## 步骤 2：测试 API 连线

发送一个简单的 GET 请求以验证 API 是否可存取：

```bash
curl -X GET "https://your-domain.com/api/v1/posts" \
  -H "Content-Type: application/json" \
  -d '{"api_token": "your-api-token-here"}'
```

您应该会收到一个包含文章列表的 JSON 回应（如果没有文章则为空阵列）。

## 步骤 3：了解回应

成功的回应将如下所示：

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

重要栏位：

- `code`：HTTP 状态码
- `status`：「success」或「fail」
- `message`：人类可读的讯息
- `data`：实际的回应资料
- `extra`：额外的中继资料（可选）

## 步骤 4：建立您的第一篇文章

现在让我们使用 API 建立一篇新文章：

```bash
curl -X POST "https://your-domain.com/api/v1/posts/store" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "title": "My First API Post",
    "content": "This post was created via the WNCMS API!"
  }'
```

成功回应：

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

## 步骤 5：检索特定文章

获取您刚建立的文章：

```bash
curl -X POST "https://your-domain.com/api/v1/posts/my-first-api-post" \
  -H "Content-Type: application/json" \
  -d '{"api_token": "your-api-token-here"}'
```

## 常见模式

### 身份验证

大多数端点需要身份验证。在请求主体中包含您的 API token：

```json
{
  "api_token": "your-api-token-here",
  "other_param": "value"
}
```

### 分页

列表端点支援分页参数：

```json
{
  "api_token": "your-api-token-here",
  "page_size": 20,
  "page": 2
}
```

### 筛选

使用查询参数来筛选结果：

```json
{
  "api_token": "your-api-token-here",
  "keywords": "search term",
  "tags": [1, 2, 3]
}
```

## 程式码范例

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

- 了解[核心概念](./core-concepts.md)，如分页和错误处理
- 探索[文章 API](./endpoints/posts.md)的进阶功能
- 查看[范例](./examples.md)了解常见用例
- 查阅[身份验证](./authentication.md)了解安全性最佳实务

## 疑难排解

**API 返回 403「API access is disabled」**

- 检查 WNCMS 设定中是否已启用 API
- 验证特定端点是否已启用（例如 `wncms_api_posts_index`）

**API 返回「Invalid token」**

- 验证您的 API token 是否正确
- 确保您在请求主体中包含了 token
- 检查您的使用者帐户是否仍然有效

**收到 404 错误**

- 验证 API 基础 URL 是否正确
- 确保您使用正确的 HTTP 方法（GET/POST）
- 检查端点是否存在于您的 WNCMS 版本中

更多疑难排解提示，请参阅[疑难排解指南](./troubleshooting.md)。
