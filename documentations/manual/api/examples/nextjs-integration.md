# Example: Next.js Integration

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

## Notes

- Store token in server-side environment variables.
- Do not expose admin tokens in browser client bundles.
