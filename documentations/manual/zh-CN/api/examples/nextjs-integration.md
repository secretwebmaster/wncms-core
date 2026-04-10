# 范例：Next.js 整合

```ts
export async function fetchPosts() {
  const res = await fetch('https://your-domain.com/api/v1/posts', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      api_token: process.env.WNCMS_API_TOKEN,
      page_size: 10,
    }),
    cache: 'no-store',
  })

  return await res.json()
}
```

## 说明

- token 请存于服务端环境变量。
- 不要将管理员 token 暴露到浏览器端。

## Backend v2 管理后台模式

若要建立独立的 Next.js 管理后台，建议使用服务端 BFF proxy，而不是让浏览器直接调用 backend v2 token。

重点：

- 在 Next.js 将 `"/api/backend/*"` 代理到 WNCMS 的 `"/api/v2/backend/*"`。
- 将 WNCMS access token 存在 `httpOnly` cookie 或 server session。
- 文章编辑若包含文件上传，请提交 `FormData`，并带上 `_method=PATCH`，让 multipart 更新流程和 Blade 后台一致。
- 常见的文章管理操作可直接代理到专用 backend v2 action，例如：
  - `GET /api/backend/posts/meta/load`（单次获取表单 metadata：状态、可见性、用户、站点、语言）
  - `GET /api/backend/posts/{id}`（单次返回文章主体 + translations + comments）
  - `GET /api/backend/posts/{id}/translations`（可选的轻量翻译专用请求）
  - `POST /api/backend/posts/{id}/delete`
  - `POST /api/backend/posts/restore/{id}`
  - `POST /api/backend/posts/bulk_delete`
