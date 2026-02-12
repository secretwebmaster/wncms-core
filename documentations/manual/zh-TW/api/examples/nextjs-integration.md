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
