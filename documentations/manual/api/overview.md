# API Overview

WNCMS provides a comprehensive RESTful API that allows you to interact with your content management system programmatically. The API enables you to create, read, update, and delete posts, pages, menus, tags, and other resources.

## Base URL

All API requests should be made to:

```
https://your-domain.com/api/v1
```

## API Version

Current API version: **v1**

The version is included in the URL path to ensure backward compatibility when new versions are released.

## Features

- **Posts Management**: Create, update, delete, and retrieve posts with advanced filtering
- **Pages Management**: Manage website pages
- **Menus Management**: Synchronize and retrieve menu structures
- **Tags Management**: Create and manage categories and tags
- **Updates**: Trigger and monitor system updates
- **Flexible Authentication**: Multiple authentication methods supported
- **Consistent Response Format**: All endpoints return standardized JSON responses
- **Pagination Support**: Built-in pagination for list endpoints
- **Filtering & Sorting**: Advanced query options for data retrieval

## Quick Start

1. **Obtain API Token**: Generate an API token from your user profile in the admin panel
2. **Make Your First Request**: Use the token to authenticate your API calls

```bash
curl -X GET "https://your-domain.com/api/v1/posts" \
  -H "Content-Type: application/json" \
  -d '{"api_token": "your-api-token-here"}'
```

3. **Handle the Response**: All responses follow a consistent format

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched posts",
  "data": [...],
  "extra": {}
}
```

## Authentication

WNCMS API supports multiple authentication methods:

- **Simple Authentication**: Use `api_token` in request body or query parameters
- **Basic Authentication**: Standard HTTP Basic Auth (where enabled)
- **No Authentication**: Some endpoints may be publicly accessible based on configuration

For detailed information, see [Authentication](./authentication.md).

## Rate Limiting

Currently, there are no enforced rate limits on the API. However, we recommend implementing your own rate limiting on the client side to prevent excessive requests.

## Response Format

All API endpoints return JSON responses with the following structure:

```json
{
  "code": 200,
  "status": "success",
  "message": "Description of the result",
  "data": {},
  "extra": {}
}
```

For more details, see [Core Concepts](./core-concepts.md).

## Available Resources

| Resource    | Description                    | Endpoint  |
| ----------- | ------------------------------ | --------- |
| **Posts**   | Manage blog posts and articles | `/posts`  |
| **Pages**   | Manage website pages           | `/pages`  |
| **Menus**   | Manage navigation menus        | `/menus`  |
| **Tags**    | Manage categories and tags     | `/tags`   |
| **Updates** | System update operations       | `/update` |

## Next Steps

- [Getting Started Guide](./getting-started.md) - Learn how to authenticate and make your first API call
- [Core Concepts](./core-concepts.md) - Understand response formats, pagination, and error handling
- [API Reference](./endpoints/posts.md) - Detailed documentation for each endpoint
- [Examples](./examples.md) - Code examples for common use cases

## Support

If you encounter any issues or have questions about the API, please:

1. Check the [Troubleshooting](./troubleshooting.md) guide
2. Review the [Error Reference](./errors.md) for error codes and solutions
3. Contact support through the admin panel

## API Status

You can check if the API is enabled in your WNCMS installation by accessing:

```
GET /api/v1/posts
```

If the API is disabled, you will receive a 403 response with the message "API access is disabled".
