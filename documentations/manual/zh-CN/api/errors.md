# 错误参考

WNCMS API 错误代码的完整指南以及如何处理它们。

## HTTP 状态码

| 代码 | 状态                  | 说明                       |
| ---- | --------------------- | -------------------------- |
| 200  | OK                    | 请求成功                   |
| 400  | Bad Request           | 无效的请求参数             |
| 401  | Unauthorized          | 需要身份验证或身份验证失败 |
| 403  | Forbidden             | API 存取已停用或权限不足   |
| 404  | Not Found             | 资源未找到                 |
| 422  | Unprocessable Entity  | 验证失败                   |
| 500  | Internal Server Error | 发生伺服器端错误           |

## 常见错误讯息

### 身份验证错误

#### 无效 Token

```json
{
  "status": "fail",
  "message": "Invalid token"
}
```

**原因：**

- API token 不正确
- API token 已被撤销
- 使用者帐户已停用

**解决方案：**

1. 验证您的 API token 是否正确
2. 从管理后台重新产生 token
3. 检查使用者帐户状态

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
- 特定端点透过功能开关停用

**解决方案：**

1. 在 WNCMS 设定中启用 API
2. 检查端点特定设定（例如 `wncms_api_posts_index`）
3. 联系系统管理员

---

#### 需要管理员存取权限

```json
{
  "status": "fail",
  "message": "Admin access required"
}
```

**原因：**

- 端点需要管理员角色
- 目前使用者不是管理员

**解决方案：**

1. 使用管理员使用者的 API token
2. 请求管理员权限
3. 使用替代的非管理员端点

---

### 验证错误

#### 缺少必填栏位

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

- 未提供必填栏位
- 必填栏位为空值

**解决方案：**

1. 检查 API 文件以了解必填栏位
2. 确保所有必填栏位都有值
3. 在发送前验证资料

## 错误处理模式

### 基本错误处理程式

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

## 相关文件

- [核心概念](./core-concepts.md) - 回应格式和错误处理
- [疑难排解](./troubleshooting.md) - 常见问题和解决方案
- [范例](./examples.md) - 错误处理的程式码范例
