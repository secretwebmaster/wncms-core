# 範例：Next.js 整合

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

## 說明

- token 請存於服務端環境變數。
- 不要將管理員 token 暴露到瀏覽器端。

## Backend v2 管理後台模式

若要建立獨立的 Next.js 管理後台，建議使用伺服器端 BFF proxy，而不是讓瀏覽器直接呼叫 backend v2 token。

重點：

- 在 Next.js 將 `"/api/backend/*"` 代理到 WNCMS 的 `"/api/v2/backend/*"`。
- 將 WNCMS access token 存在 `httpOnly` cookie 或 server session。
- 文章編輯若包含檔案上傳，請送出 `FormData`，並帶上 `_method=PATCH`，讓 multipart 更新流程和 Blade 後台一致。
- 常見的文章管理操作可直接代理到專用 backend v2 action，例如：
  - `GET /api/backend/posts/meta/load`（單次取得表單 metadata：狀態、可見性、使用者、站點、語系）
  - `GET /api/backend/posts/{id}`（單次回傳文章主體 + translations + comments）
  - `GET /api/backend/posts/{id}/translations`（可選的輕量翻譯專用請求）
  - `POST /api/backend/posts/{id}/delete`
  - `POST /api/backend/posts/restore/{id}`
  - `POST /api/backend/posts/bulk_delete`
