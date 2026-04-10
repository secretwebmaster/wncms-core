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

## Backend v2 Admin Pattern

For a separate Next.js admin dashboard, prefer a server-side BFF proxy instead of calling backend v2 tokens directly from the browser.

Key points:

- Proxy `"/api/backend/*"` in Next.js to `"/api/v2/backend/*"` in WNCMS.
- Keep the WNCMS access token in an `httpOnly` cookie or server session.
- For post editing with file uploads, send `FormData` and use `_method=PATCH` so multipart updates follow the same pattern as the Blade backend.
- Common Post management actions can be proxied as dedicated backend v2 actions, for example:
  - `GET /api/backend/posts/meta/load` (single request for form metadata: statuses, visibilities, users, websites, locales)
  - `GET /api/backend/posts/{id}` (includes post payload + translations + comments in one response)
  - `GET /api/backend/posts/{id}/translations` (optional lightweight translation-only fetch)
  - `POST /api/backend/posts/{id}/delete`
  - `POST /api/backend/posts/restore/{id}`
  - `POST /api/backend/posts/bulk_delete`
