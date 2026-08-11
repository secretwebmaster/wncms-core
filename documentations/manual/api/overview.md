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

WNCMS also provides **v2** route groups for the new admin stack:

- `/api/v2/backend/*` for authenticated backend actions
- `/api/v2/frontend/*` for frontend-facing v2 endpoints
- `/api/v2/translations` for namespace/group translation payloads (for example `namespace=wncms&group=word`)

## API v2 Contract Kernel

API v2 now exposes one registry-backed contract for clients that need to build
an independent admin application:

- [API v2 Contracts](./contracts.md) documents authentication, middleware, the
  six-key envelope, query options, and `If-Match` revisions.
- [Runtime Capabilities](./capabilities.md) documents
  `GET /api/v2/capabilities` and actor-specific permission/website filtering.
- [OpenAPI 3.1](./openapi.md) documents `GET /api/v2/openapi.json` and the five
  `x-wncms-*` operation extensions.
- [Asynchronous Operations](./operations.md) documents operation state, TTL,
  cancellation, and idempotent replay.

Legacy v2 operations remain discoverable, but operations classified as
`legacy_resource`, `legacy_controller`, or `legacy_bridge` do not satisfy final
v7 domain parity.

## Links Backend API v2 Reference

`/api/v2/backend/links` is the guarded API v2 reference resource. It provides
website-scoped list and ID/slug inspect reads plus preview-first create, patch,
delete, atomic bulk update, and atomic bulk tag synchronization.

All mutation requests use the authenticated bearer-token user as actor, preview
by default with HTTP `202`, and write only when `force=true` and `dry_run` is not
true. Successful writes create `mutation_audits` records with
`surface=api_v2`. Guarded Links bulk delete is intentionally unavailable, so the
Links API v2 surface remains partial for that explicit gap.

See [Links API v2 Endpoints](./endpoints/links.md) for exact routes, permissions,
filters, request payloads, and response envelopes.

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
- **Basic Authentication**: Standard HTTP Basic Auth using `email:password` (where enabled)
- **No Authentication**: Some endpoints may be publicly accessible based on configuration
- **Whitelist Gate**: When `api_access_whitelist` is not empty, the request IP or `Origin`/`Referer` host must also match

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
| **Websites** | Manage website domains         | `/websites` |
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
