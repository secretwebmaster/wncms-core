# 範例：透過 API 取得文章

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

## 預期結果

- `code: 200`
- `status: success`
- `data` 返回分頁文章列表
