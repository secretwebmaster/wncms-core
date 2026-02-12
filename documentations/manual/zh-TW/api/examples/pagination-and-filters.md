# 範例：分頁與篩選

```json
{
  "api_token": "your-api-token-here",
  "page": 2,
  "page_size": 20,
  "keywords": "laravel",
  "tags": [1, 2],
  "sort": "published_at",
  "direction": "desc"
}
```

## 用法

將此 payload 送到 `POST /api/v1/posts`。

## 預期結果

- 返回篩選後列表
- 在 `extra` 中返回分頁資訊
