# API 总览

WNCMS 提供全面的 RESTful API，让您能够以程式化方式与内容管理系统互动。该 API 让您能够建立、读取、更新和删除文章、页面、选单、标签及其他资源。

## 基础 URL

所有 API 请求应发送至：

```
https://your-domain.com/api/v1
```

## API 版本

目前 API 版本：**v1**

版本包含在 URL 路径中，以确保在发布新版本时的向后相容性。

WNCMS 也提供 **v2** 路由群组给新版管理后台：

- `/api/v2/backend/*`：已验证的后台操作
- `/api/v2/frontend/*`：前台侧 v2 端点
- `/api/v2/translations`：按 namespace/group 读取翻译字典（例如 `namespace=wncms&group=word`）

## Links Backend API v2 参考资源

`/api/v2/backend/links` 是受保护的 API v2 参考资源，提供网站范围内的列表、
ID/slug 查看，以及预览优先的 create、patch、delete、原子 bulk update 与
原子 bulk tag sync。

所有 mutation 都使用已验证 bearer token 使用者作为 actor，默认以 HTTP `202`
预览；只有 `force=true` 且 `dry_run` 不为 true 时才会写入。成功写入会建立
`surface=api_v2` 的 `mutation_audits`。受保护的 Links bulk delete 尚未提供，
因此 Links API v2 仅因这个明确缺口维持 Partial。

完整路由、权限、筛选、payload 与回应 envelope 请参阅
[Links API v2 端点](./endpoints/links.md)。

## 功能特色

- **文章管理**：建立、更新、删除和检索文章，并提供进阶筛选功能
- **页面管理**：管理网站页面
- **选单管理**：同步和检索选单结构
- **标签管理**：建立和管理分类与标签
- **更新功能**：触发和监控系统更新
- **弹性身份验证**：支援多种身份验证方法
- **统一回应格式**：所有端点返回标准化的 JSON 回应
- **分页支援**：列表端点内建分页功能
- **筛选与排序**：资料检索的进阶查询选项

## 快速开始

1. **取得 API Token**：从管理后台的使用者个人资料中产生 API token
2. **发出第一个请求**：使用 token 来验证您的 API 呼叫

```bash
curl -X GET "https://your-domain.com/api/v1/posts" \
  -H "Content-Type: application/json" \
  -d '{"api_token": "your-api-token-here"}'
```

3. **处理回应**：所有回应都遵循一致的格式

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched posts",
  "data": [...],
  "extra": {}
}
```

## 身份验证

WNCMS API 支援多种身份验证方法：

- **简易验证**：在请求主体或查询参数中使用 `api_token`
- **基本验证**：使用 `email:password` 的标准 HTTP 基本验证（在启用时）
- **无需验证**：某些端点可能根据设定公开存取
- **白名单闸道**：当 `api_access_whitelist` 不为空时，请求 IP 或 `Origin`/`Referer` 主机也必须匹配

详细资讯请参阅[身份验证](./authentication.md)。

## 速率限制

目前 API 没有强制的速率限制。但我们建议在客户端实作您自己的速率限制，以防止过多的请求。

## 回应格式

所有 API 端点都返回具有以下结构的 JSON 回应：

```json
{
  "code": 200,
  "status": "success",
  "message": "Description of the result",
  "data": {},
  "extra": {}
}
```

更多详情请参阅[核心概念](./core-concepts.md)。

## 可用资源

| 资源     | 说明                 | 端点      |
| -------- | -------------------- | --------- |
| **文章** | 管理部落格文章和文章 | `/posts`  |
| **页面** | 管理网站页面         | `/pages`  |
| **选单** | 管理导览选单         | `/menus`  |
| **标签** | 管理分类和标签       | `/tags`   |
| **网站** | 管理网站域名         | `/websites` |
| **更新** | 系统更新操作         | `/update` |

## 下一步

- [入门指南](./getting-started.md) - 学习如何验证并发出您的第一个 API 呼叫
- [核心概念](./core-concepts.md) - 了解回应格式、分页和错误处理
- [API 参考](./endpoints/posts.md) - 每个端点的详细文件
- [范例](./examples.md) - 常见用例的程式码范例
