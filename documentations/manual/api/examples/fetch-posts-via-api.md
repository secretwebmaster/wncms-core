# Example: Fetch Posts via API

```bash
curl -X POST "https://your-domain.com/api/v1/posts" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "page_size": 10,
    "page": 1,
    "sort": "published_at",
    "direction": "desc"
  }'
```

## Expected Result

- `code: 200`
- `status: success`
- paginated post list in `data`
