# 范例：分页与筛选

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

将此 payload 送到 `POST /api/v1/posts`。

## 预期结果

- 返回筛选后的列表
- 在 `extra` 中返回分页信息
