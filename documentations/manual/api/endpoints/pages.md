# Pages API

The Pages API allows you to manage static website pages in WNCMS.

:::warning Work in Progress
The Pages API endpoints are currently placeholders and will be fully implemented in a future version.
:::

## Endpoints Overview

| Method | Endpoint              | Description       | Status      |
| ------ | --------------------- | ----------------- | ----------- |
| POST   | `/api/v1/pages`       | List pages        | Placeholder |
| POST   | `/api/v1/pages/store` | Create a page     | Placeholder |
| POST   | `/api/v1/pages/{id}`  | Get a single page | Placeholder |

## List Pages

### Endpoint

```
POST /api/v1/pages
```

### Current Response

```json
{
  "status": "success",
  "message": "Successfully fetched page index"
}
```

## Create Page

### Endpoint

```
POST /api/v1/pages/store
```

### Current Response

```json
{
  "status": "success",
  "message": "Successfully fetched page store"
}
```

## Get Single Page

### Endpoint

```
POST /api/v1/pages/{id}
```

### Current Response

```json
{
  "status": "success",
  "message": "Successfully fetched page show"
}
```

## Future Implementation

The Pages API will support similar functionality to the Posts API:

- Full CRUD operations (Create, Read, Update, Delete)
- Page templates management
- SEO metadata
- Parent-child page relationships
- Page visibility controls
- Custom fields support

## Alternative: Use Posts API

Until the Pages API is fully implemented, you can use the [Posts API](./posts.md) with a custom post type for managing static pages.

## Related Endpoints

- [Posts API](./posts.md) - Full-featured content management
- [Menus API](./menus.md) - Link pages in navigation menus

## Troubleshooting

For current API issues, see the [Troubleshooting Guide](../troubleshooting.md).
