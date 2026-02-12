# Getting Started

This guide will walk you through making your first API request to WNCMS.

## Prerequisites

Before you start, make sure you have:

- A WNCMS installation with API access enabled
- Admin access to generate an API token
- A tool to make HTTP requests (curl, Postman, or your programming language's HTTP client)

## Step 1: Generate an API Token

1. Log in to your WNCMS admin panel
2. Navigate to your user profile settings
3. Find the "API Token" section
4. Click "Generate Token" if you don't have one
5. Copy your API token - you'll need it for authentication

:::warning Security Notice
Keep your API token secure. Never commit it to version control or expose it in client-side code.
:::

## Step 2: Test the API Connection

Make a simple GET request to verify the API is accessible:

```bash
curl -X GET "https://your-domain.com/api/v1/posts" \
  -H "Content-Type: application/json" \
  -d '{"api_token": "your-api-token-here"}'
```

You should receive a JSON response with a list of posts (or an empty array if no posts exist).

## Step 3: Understanding the Response

A successful response will look like this:

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched posts",
  "data": {
    "data": [
      {
        "id": 1,
        "title": "Sample Post",
        "slug": "sample-post",
        "content": "Post content here...",
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "pagination": {
      "total": 1,
      "count": 1,
      "page_size": 15,
      "current_page": 1,
      "last_page": 1,
      "has_more": false
    }
  },
  "extra": {}
}
```

Key fields:

- `code`: HTTP status code
- `status`: "success" or "fail"
- `message`: Human-readable message
- `data`: The actual response data
- `extra`: Additional metadata (optional)

## Step 4: Create Your First Post

Now let's create a new post using the API:

```bash
curl -X POST "https://your-domain.com/api/v1/posts/store" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "title": "My First API Post",
    "content": "This post was created via the WNCMS API!"
  }'
```

Successful response:

```json
{
  "code": 200,
  "status": "success",
  "message": "Post #123 created successfully",
  "data": {
    "id": 123,
    "title": "My First API Post",
    "slug": "my-first-api-post",
    "content": "This post was created via the WNCMS API!",
    "created_at": "2024-01-15T10:30:00.000000Z"
  },
  "extra": {}
}
```

## Step 5: Retrieve a Specific Post

Fetch the post you just created:

```bash
curl -X POST "https://your-domain.com/api/v1/posts/my-first-api-post" \
  -H "Content-Type: application/json" \
  -d '{"api_token": "your-api-token-here"}'
```

## Common Patterns

### Authentication

Most endpoints require authentication. Include your API token in the request body:

```json
{
  "api_token": "your-api-token-here",
  "other_param": "value"
}
```

### Pagination

List endpoints support pagination parameters:

```json
{
  "api_token": "your-api-token-here",
  "page_size": 20,
  "page": 2
}
```

### Filtering

Use query parameters to filter results:

```json
{
  "api_token": "your-api-token-here",
  "keywords": "search term",
  "tags": [1, 2, 3]
}
```

## Code Examples

### JavaScript (Fetch API)

```javascript
const response = await fetch('https://your-domain.com/api/v1/posts', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    api_token: 'your-api-token-here',
  }),
})

const result = await response.json()
console.log(result.data)
```

### PHP

```php
$ch = curl_init('https://your-domain.com/api/v1/posts');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'api_token' => 'your-api-token-here'
]));

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

print_r($result['data']);
```

### Python (requests)

```python
import requests

response = requests.get(
    'https://your-domain.com/api/v1/posts',
    headers={'Content-Type': 'application/json'},
    json={'api_token': 'your-api-token-here'}
)

result = response.json()
print(result['data'])
```

## Next Steps

- Learn about [Core Concepts](./core-concepts.md) like pagination and error handling
- Explore the [Posts API](./endpoints/posts.md) for advanced features
- Check out [Examples](./examples.md) for common use cases
- Review [Authentication](./authentication.md) for security best practices

## Troubleshooting

**API returns 403 "API access is disabled"**

- Check that the API is enabled in your WNCMS settings
- Verify the specific endpoint is enabled (e.g., `wncms_api_posts_index`)

**API returns "Invalid token"**

- Verify your API token is correct
- Make sure you're including the token in the request body
- Check that your user account is still active

**Getting 404 errors**

- Verify the API base URL is correct
- Ensure you're using the correct HTTP method (GET/POST)
- Check that the endpoint exists in your WNCMS version

For more troubleshooting tips, see the [Troubleshooting Guide](./troubleshooting.md).
