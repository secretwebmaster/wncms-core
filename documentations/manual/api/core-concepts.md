# Core Concepts

Understanding these core concepts will help you work effectively with the WNCMS API.

## Response Format

All API endpoints return a consistent JSON structure:

```json
{
  "code": 200,
  "status": "success",
  "message": "Description of the operation",
  "data": {},
  "extra": {}
}
```

### Response Fields

| Field     | Type    | Description                                       |
| --------- | ------- | ------------------------------------------------- |
| `code`    | integer | HTTP status code (200, 400, 403, 500, etc.)       |
| `status`  | string  | Operation status: "success" or "fail"             |
| `message` | string  | Human-readable message describing the result      |
| `data`    | mixed   | The actual response data (object, array, or null) |
| `extra`   | object  | Additional metadata (optional)                    |

### Success Response Example

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched posts",
  "data": {
    "data": [...],
    "pagination": {...}
  },
  "extra": {}
}
```

### Error Response Example

```json
{
  "code": 403,
  "status": "fail",
  "message": "API access is disabled",
  "data": null,
  "extra": {}
}
```

## Authentication

WNCMS API supports configurable authentication per endpoint.

### Simple Authentication (api_token)

The most common method. Include your API token in the request body:

```json
{
  "api_token": "your-api-token-here",
  "other_params": "..."
}
```

### Token Generation

1. Log in to WNCMS admin panel
2. Navigate to your user profile
3. Find the API Token section
4. Generate or copy your existing token

### Token Security

:::warning Security Best Practices

- Never expose API tokens in client-side code
- Use environment variables to store tokens
- Rotate tokens periodically
- Use HTTPS for all API requests
- Implement rate limiting on your application
  :::

### Checking Authentication Requirements

Each endpoint may have different authentication settings. If authentication fails:

```json
{
  "status": "fail",
  "message": "Invalid token"
}
```

## Pagination

List endpoints that return multiple items include pagination data.

### Pagination Parameters

Include these in your request to control pagination:

| Parameter   | Type    | Default | Description              |
| ----------- | ------- | ------- | ------------------------ |
| `page_size` | integer | 15      | Number of items per page |
| `page`      | integer | 1       | Current page number      |

Example request:

```json
{
  "api_token": "your-api-token-here",
  "page_size": 20,
  "page": 2
}
```

### Pagination Metadata

Response includes comprehensive pagination information:

```json
{
  "code": 200,
  "status": "success",
  "data": {
    "data": [...],
    "pagination": {
      "total": 150,
      "count": 20,
      "page_size": 20,
      "current_page": 2,
      "last_page": 8,
      "has_more": true,
      "next": "/api/v1/posts?page=3",
      "previous": "/api/v1/posts?page=1"
    }
  }
}
```

### Pagination Fields Explained

| Field          | Description                            |
| -------------- | -------------------------------------- |
| `total`        | Total number of items across all pages |
| `count`        | Number of items in the current page    |
| `page_size`    | Maximum items per page                 |
| `current_page` | Current page number                    |
| `last_page`    | Last available page number             |
| `has_more`     | Boolean indicating if more pages exist |
| `next`         | URL to next page (null if none)        |
| `previous`     | URL to previous page (null if none)    |

## Filtering and Sorting

Many endpoints support filtering and sorting options.

### Common Filter Parameters

Different endpoints support different filters. Common ones include:

```json
{
  "api_token": "your-api-token-here",
  "keywords": "search term",
  "tags": [1, 2, 3],
  "tag_type": "post_category",
  "excluded_post_ids": [5, 10, 15]
}
```

### Sorting

Control the order of results:

```json
{
  "api_token": "your-api-token-here",
  "sort": "created_at",
  "direction": "desc"
}
```

Common sort fields:

- `created_at` - Creation date
- `updated_at` - Last modification date
- `title` - Alphabetical by title
- `sort` - Manual sort order

Common directions:

- `desc` - Descending (newest/highest first)
- `asc` - Ascending (oldest/lowest first)

### Random Results

Some endpoints support random ordering:

```json
{
  "api_token": "your-api-token-here",
  "is_random": true,
  "page_size": 5
}
```

## Error Handling

### HTTP Status Codes

| Code | Status               | Meaning                                         |
| ---- | -------------------- | ----------------------------------------------- |
| 200  | Success              | Request completed successfully                  |
| 400  | Bad Request          | Invalid request parameters                      |
| 401  | Unauthorized         | Authentication required                         |
| 403  | Forbidden            | API access disabled or insufficient permissions |
| 404  | Not Found            | Resource not found                              |
| 422  | Unprocessable Entity | Validation failed                               |
| 500  | Server Error         | Internal server error                           |

### Error Response Format

Errors follow the same response structure:

```json
{
  "code": 500,
  "status": "fail",
  "message": "Server Error: Database connection failed",
  "data": null,
  "extra": {}
}
```

### Validation Errors

When validation fails (422), the response includes details:

```json
{
  "code": 422,
  "status": "fail",
  "message": "Validation failed",
  "data": {
    "errors": {
      "title": ["The title field is required."],
      "content": ["The content field is required."]
    }
  }
}
```

### Handling Errors in Your Code

#### JavaScript Example

```javascript
try {
  const response = await fetch('https://your-domain.com/api/v1/posts', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ api_token: 'your-token' }),
  })

  const result = await response.json()

  if (result.status === 'fail') {
    console.error('API Error:', result.message)
    // Handle error
  } else {
    console.log('Success:', result.data)
    // Handle success
  }
} catch (error) {
  console.error('Network Error:', error)
}
```

#### PHP Example

```php
try {
    $response = // ... make API call
    $result = json_decode($response, true);

    if ($result['status'] === 'fail') {
        throw new Exception($result['message']);
    }

    return $result['data'];
} catch (Exception $e) {
    logger()->error('API Error: ' . $e->getMessage());
    return null;
}
```

## Feature Toggles

API endpoints can be enabled/disabled via WNCMS settings. Each endpoint checks its own setting:

- `wncms_api_posts_index` - Controls POST listing
- `wncms_api_posts_store` - Controls POST creation
- `wncms_api_tag_store` - Controls tag creation
- etc.

### Package-Aware Toggle Labels In System Settings

In **System Settings -> API**, each endpoint label now resolves translation by route package context:

- Route config may include `package_id` in model `$apiRoutes`.
- If omitted, WNCMS falls back to the model package ID.
- If still missing, WNCMS falls back to `wncms`.

Example model API route metadata:

```php
protected static array $apiRoutes = [
    [
        'name' => 'api.v1.tags.store',
        'key' => 'wncms_api_tag_store',
        'action' => 'store',
        'package_id' => 'your-package-id',
    ],
];
```

When disabled, you'll receive:

```json
{
  "status": 403,
  "message": "API access is disabled"
}
```

## Caching

WNCMS implements caching for performance. After creating or updating resources:

- Caches are automatically flushed
- Subsequent requests will reflect the changes
- No manual cache clearing required

## Internationalization (i18n)

Some endpoints support locale parameters:

```json
{
  "api_token": "your-api-token-here",
  "locale": "zh-TW"
}
```

Supported locales depend on your WNCMS installation configuration.

## Best Practices

### 1. Always Check the Response Status

```javascript
if (result.status === 'success') {
  // Process data
} else {
  // Handle error
}
```

### 2. Implement Proper Error Handling

Don't assume requests will always succeed. Handle network errors, API errors, and validation errors appropriately.

### 3. Use Pagination for Large Datasets

Request only what you need to improve performance:

```json
{
  "page_size": 20,
  "page": 1
}
```

### 4. Cache Responses When Appropriate

If data doesn't change frequently, cache responses on your end to reduce API calls.

### 5. Validate Input Before Sending

Check required fields and data types before making API requests to avoid unnecessary validation errors.

### 6. Use HTTPS

Always use HTTPS to protect API tokens and sensitive data in transit.

### 7. Monitor API Usage

Keep track of your API calls to identify performance issues or potential improvements.

## Next Steps

- Explore the [Posts API Reference](./endpoints/posts.md) for detailed endpoint documentation
- See [Examples](./examples.md) for common use case implementations
- Review [Error Reference](./errors.md) for a complete list of error codes
- Check [Troubleshooting](./troubleshooting.md) for solutions to common issues
