# Example: Pagination and Filters

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

## Usage

Send this payload to `POST /api/v1/posts`.

## Expected Result

- filtered result set
- pagination metadata in `extra`
