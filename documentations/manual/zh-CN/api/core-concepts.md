# 核心概念

了解这些核心概念将帮助您有效地使用 WNCMS API。

## 回应格式

所有 API 端点都返回一致的 JSON 结构：

```json
{
  "code": 200,
  "status": "success",
  "message": "Description of the operation",
  "data": {},
  "extra": {}
}
```

### 回应栏位

| 栏位      | 类型    | 说明                                 |
| --------- | ------- | ------------------------------------ |
| `code`    | integer | HTTP 状态码（200、400、403、500 等） |
| `status`  | string  | 操作状态：「success」或「fail」      |
| `message` | string  | 描述结果的人类可读讯息               |
| `data`    | mixed   | 实际的回应资料（物件、阵列或 null）  |
| `extra`   | object  | 额外的中继资料（可选）               |

### 成功回应范例

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

### 错误回应范例

```json
{
  "code": 403,
  "status": "fail",
  "message": "API access is disabled",
  "data": null,
  "extra": {}
}
```

## 身份验证

WNCMS API 支援每个端点的可配置身份验证。

### 简易验证 (api_token)

最常见的方法。在请求主体中包含您的 API token：

```json
{
  "api_token": "your-api-token-here",
  "other_params": "..."
}
```

### Token 产生

1. 登入 WNCMS 管理后台
2. 导览至您的使用者个人资料
3. 找到 API Token 区块
4. 产生或复制现有的 token

### Token 安全性

:::warning 安全性最佳实务

- 切勿在客户端程式码中公开 API token
- 使用环境变数来储存 token
- 定期轮换 token
- 对所有 API 请求使用 HTTPS
- 在应用程式中实作速率限制
  :::

### 检查身份验证要求

每个端点可能有不同的身份验证设定。如果身份验证失败：

```json
{
  "status": "fail",
  "message": "Invalid token"
}
```

## 分页

返回多个项目的列表端点包含分页资料。

### 分页参数

在请求中包含这些参数以控制分页：

| 参数        | 类型    | 预设值 | 说明       |
| ----------- | ------- | ------ | ---------- |
| `page_size` | integer | 15     | 每页项目数 |
| `page`      | integer | 1      | 目前页码   |

请求范例：

```json
{
  "api_token": "your-api-token-here",
  "page_size": 20,
  "page": 2
}
```

### 分页中继资料

回应包含全面的分页资讯：

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

### 分页栏位说明

| 栏位           | 说明                              |
| -------------- | --------------------------------- |
| `total`        | 所有页面的总项目数                |
| `count`        | 目前页面的项目数                  |
| `page_size`    | 每页最大项目数                    |
| `current_page` | 目前页码                          |
| `last_page`    | 最后可用的页码                    |
| `has_more`     | 指示是否有更多页面的布林值        |
| `next`         | 下一页的 URL（如果没有则为 null） |
| `previous`     | 上一页的 URL（如果没有则为 null） |

## 筛选与排序

许多端点支援筛选和排序选项。

### 常见筛选参数

不同端点支援不同的筛选器。常见的包括：

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

控制结果的顺序：

```json
{
  "api_token": "your-api-token-here",
  "sort": "created_at",
  "direction": "desc"
}
```

常见排序栏位：

- `created_at` - 建立日期
- `updated_at` - 最后修改日期
- `title` - 按标题字母顺序
- `sort` - 手动排序顺序

常见方向：

- `desc` - 降序（最新/最高优先）
- `asc` - 升序（最旧/最低优先）

### 随机结果

某些端点支援随机排序：

```json
{
  "api_token": "your-api-token-here",
  "is_random": true,
  "page_size": 5
}
```

## 错误处理

### HTTP 状态码

| 代码 | 状态           | 意义                     |
| ---- | -------------- | ------------------------ |
| 200  | 成功           | 请求成功完成             |
| 400  | 错误请求       | 无效的请求参数           |
| 401  | 未授权         | 需要身份验证             |
| 403  | 禁止           | API 存取已停用或权限不足 |
| 404  | 未找到         | 资源未找到               |
| 422  | 无法处理的实体 | 验证失败                 |
| 500  | 伺服器错误     | 内部伺服器错误           |

### 错误回应格式

错误遵循相同的回应结构：

```json
{
  "code": 500,
  "status": "fail",
  "message": "Server Error: Database connection failed",
  "data": null,
  "extra": {}
}
```

### 验证错误

当验证失败时（422），回应包含详细资讯：

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

### 在程式码中处理错误

#### JavaScript 范例

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
    // 处理错误
  } else {
    console.log('Success:', result.data)
    // 处理成功
  }
} catch (error) {
  console.error('Network Error:', error)
}
```

#### PHP 范例

```php
try {
    $response = // ... 发送 API 请求
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

## 功能开关

API 端点可以透过 WNCMS 设定启用/停用。每个端点检查自己的设定：

- `wncms_api_posts_index` - 控制文章列表
- `wncms_api_posts_store` - 控制文章建立
- `wncms_api_tag_store` - 控制标签建立
- 等等

### System Settings API 页签中的套件感知标签

在 **System Settings -> API** 中，每个端点标签现在会按路由所属套件解析翻译：

- 模型 `$apiRoutes` 可选带入 `package_id`。
- 若未带入，WNCMS 会回退到模型的 package ID。
- 若仍为空，最后回退到 `wncms`。

模型 API 路由设定范例：

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

当停用时，您将收到：

```json
{
  "status": 403,
  "message": "API access is disabled"
}
```

## 快取

WNCMS 实作快取以提升效能。在建立或更新资源后：

- 快取会自动清除
- 后续请求将反映变更
- 无需手动清除快取

## 国际化 (i18n)

某些端点支援语言环境参数：

```json
{
  "api_token": "your-api-token-here",
  "locale": "zh-TW"
}
```

支援的语言环境取决于您的 WNCMS 安装设定。

## 最佳实务

### 1. 始终检查回应状态

```javascript
if (result.status === 'success') {
  // 处理资料
} else {
  // 处理错误
}
```

### 2. 实作适当的错误处理

不要假设请求总是会成功。适当地处理网路错误、API 错误和验证错误。

### 3. 对大型资料集使用分页

只请求您需要的内容以提升效能：

```json
{
  "page_size": 20,
  "page": 1
}
```

### 4. 适当时快取回应

如果资料不经常变更，在您这边快取回应以减少 API 呼叫。

### 5. 在发送前验证输入

在发送 API 请求之前检查必填栏位和资料类型，以避免不必要的验证错误。

### 6. 使用 HTTPS

始终使用 HTTPS 来保护 API token 和传输中的敏感资料。

### 7. 监控 API 使用情况

追踪您的 API 呼叫以识别效能问题或潜在的改进。

## 下一步

- 探索[文章 API 参考](./endpoints/posts.md)以获得详细的端点文件
- 查看[范例](./examples.md)以了解常见用例实作
- 查阅[错误参考](./errors.md)以获得完整的错误代码列表
- 查看[疑难排解](./troubleshooting.md)以获得常见问题的解决方案
