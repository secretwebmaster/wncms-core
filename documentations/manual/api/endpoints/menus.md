# Menus API

The Menus API allows you to retrieve and synchronize navigation menu structures in WNCMS.

## Endpoints Overview

| Method   | Endpoint              | Description                          |
| -------- | --------------------- | ------------------------------------ |
| GET/POST | `/api/v1/menus`       | List all menus (placeholder)         |
| POST     | `/api/v1/menus/store` | Create/update menu (placeholder)     |
| POST     | `/api/v1/menus/sync`  | Synchronize menu items for a website |
| GET/POST | `/api/v1/menus/{id}`  | Get a single menu by ID              |

## List Menus

:::warning Work in Progress
This endpoint is currently a placeholder and returns an empty collection.
:::

### Endpoint

```
GET|POST /api/v1/menus
```

### Response Example

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched menus",
  "data": []
}
```

## Store Menu

:::warning Work in Progress
This endpoint is currently a placeholder for logging purposes only.
:::

### Endpoint

```
POST /api/v1/menus/store
```

## Synchronize Menu

Synchronize menu items for a specific website. This is an admin-only operation that allows bulk updating of menu structures.

### Endpoint

```
POST /api/v1/menus/sync
```

### Authentication

- **Required**: Yes
- **Permission**: Admin role required
- **Method**: Simple authentication via `api_token`

### Request Parameters

| Parameter    | Type    | Required | Description                |
| ------------ | ------- | -------- | -------------------------- |
| `api_token`  | string  | Yes      | Admin user API token       |
| `website_id` | integer | Yes\*    | Website ID                 |
| `domain`     | string  | Yes\*    | Website domain             |
| `menu_items` | array   | Yes      | Array of menu item objects |

\*Either `website_id` or `domain` must be provided

### Menu Item Object

Each menu item in the `menu_items` array should have:

| Field       | Type    | Required | Description                          |
| ----------- | ------- | -------- | ------------------------------------ |
| `order`     | integer | Yes      | Display order/position               |
| `name`      | string  | Yes      | Menu item display name               |
| `type`      | string  | Yes      | Menu type (e.g., "page", "link")     |
| `page_id`   | integer | No       | Page ID (required if type is "page") |
| `url`       | string  | No       | Custom URL (for link type)           |
| `target`    | string  | No       | Link target (\_self, \_blank)        |
| `icon`      | string  | No       | Icon identifier                      |
| `parent_id` | integer | No       | Parent menu item ID for nested menus |

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/menus/sync" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "admin-api-token-here",
    "website_id": 1,
    "menu_items": [
      {
        "order": 1,
        "name": "Home",
        "type": "page",
        "page_id": 1
      },
      {
        "order": 2,
        "name": "About",
        "type": "page",
        "page_id": 2
      },
      {
        "order": 3,
        "name": "Blog",
        "type": "link",
        "url": "/blog"
      },
      {
        "order": 4,
        "name": "External Link",
        "type": "link",
        "url": "https://external-site.com",
        "target": "_blank"
      }
    ]
  }'
```

### Request Example with Nested Menus

```bash
curl -X POST "https://your-domain.com/api/v1/menus/sync" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "admin-api-token-here",
    "domain": "example.com",
    "menu_items": [
      {
        "order": 1,
        "name": "Products",
        "type": "page",
        "page_id": 10
      },
      {
        "order": 2,
        "name": "Product Category 1",
        "type": "page",
        "page_id": 11,
        "parent_id": 10
      },
      {
        "order": 3,
        "name": "Product Category 2",
        "type": "page",
        "page_id": 12,
        "parent_id": 10
      }
    ]
  }'
```

### Response Example

```json
{
  "code": 200,
  "status": "success",
  "message": "Menu synchronized successfully",
  "data": {
    "website_id": 1,
    "items_created": 4,
    "items_deleted": 2
  },
  "extra": {}
}
```

### How Sync Works

1. **Identify Website**: Finds website by `website_id` or `domain`
2. **Delete Existing**: Removes all current menu items for the website
3. **Create New Items**: Creates all menu items from the request
4. **Process Page Types**: For "page" type items, loads page details and generates URLs
5. **Flush Cache**: Clears menu cache for immediate updates

### Sync Behavior

:::warning Destructive Operation
Menu sync is a **destructive operation**. All existing menu items for the specified website will be deleted and replaced with the new items from your request.

Make sure to include ALL menu items you want to keep, not just the ones you're adding or changing.
:::

## Get Single Menu

Retrieve a specific menu by ID.

### Endpoint

```
GET|POST /api/v1/menus/{id}
```

### Authentication

Required: Configurable via settings

### URL Parameters

| Parameter | Type    | Description |
| --------- | ------- | ----------- |
| `id`      | integer | Menu ID     |

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/menus/1" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here"
  }'
```

### Response Example

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched menu",
  "data": {
    "id": 1,
    "website_id": 1,
    "order": 1,
    "name": "Home",
    "type": "page",
    "url": "/",
    "target": "_self",
    "icon": null,
    "parent_id": null,
    "page": {
      "id": 1,
      "title": "Homepage",
      "slug": "home"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  },
  "extra": {}
}
```

## Menu Types

WNCMS supports different menu types:

### Page Type

Links to an internal page:

```json
{
  "order": 1,
  "name": "About Us",
  "type": "page",
  "page_id": 5
}
```

The system will automatically:

- Load the page details
- Generate the correct URL
- Handle localization if enabled

### Link Type

Custom URL (internal or external):

```json
{
  "order": 2,
  "name": "Blog",
  "type": "link",
  "url": "/blog",
  "target": "_self"
}
```

### External Link

Link to external websites:

```json
{
  "order": 3,
  "name": "Partner Site",
  "type": "link",
  "url": "https://partner-site.com",
  "target": "_blank"
}
```

## Nested Menus

Create hierarchical menu structures using `parent_id`:

```json
{
  "menu_items": [
    {
      "order": 1,
      "name": "Services",
      "type": "page",
      "page_id": 10
    },
    {
      "order": 2,
      "name": "Web Development",
      "type": "page",
      "page_id": 11,
      "parent_id": 10
    },
    {
      "order": 3,
      "name": "Mobile Apps",
      "type": "page",
      "page_id": 12,
      "parent_id": 10
    },
    {
      "order": 4,
      "name": "iOS Development",
      "type": "page",
      "page_id": 13,
      "parent_id": 12
    }
  ]
}
```

This creates:

```
Services
├── Web Development
└── Mobile Apps
    └── iOS Development
```

## Error Responses

### 403 - Not Admin

```json
{
  "status": "fail",
  "message": "Admin access required"
}
```

### 404 - Website Not Found

```json
{
  "code": 404,
  "status": "fail",
  "message": "Website not found"
}
```

### 422 - Validation Failed

```json
{
  "code": 422,
  "status": "fail",
  "message": "Validation failed",
  "data": {
    "errors": {
      "menu_items": ["The menu items field is required."],
      "website_id": ["Either website_id or domain is required."]
    }
  }
}
```

### 500 - Server Error

```json
{
  "code": 500,
  "status": "fail",
  "message": "Server Error: Failed to sync menu"
}
```

## Best Practices

### 1. Always Include All Menu Items

When syncing, include the complete menu structure:

```json
{
  "menu_items": [
    // Include ALL items, not just changes
  ]
}
```

### 2. Use Consistent Ordering

Maintain logical order values:

```json
{
  "menu_items": [
    { "order": 1, "name": "First" },
    { "order": 2, "name": "Second" },
    { "order": 3, "name": "Third" }
  ]
}
```

### 3. Verify Page IDs

Ensure page IDs exist before syncing:

```javascript
// Pseudo-code
const pageExists = await checkPageExists(pageId)
if (!pageExists) {
  console.error('Invalid page ID')
  return
}
```

### 4. Handle Errors Gracefully

```javascript
try {
  const result = await syncMenu(menuData)
  if (result.status === 'fail') {
    // Rollback or notify
  }
} catch (error) {
  // Handle network errors
}
```

### 5. Clear Client-Side Cache

After syncing, clear any client-side menu caches:

```javascript
await syncMenu(menuData)
localStorage.removeItem('cached_menus')
```

## Use Cases

### Complete Menu Rebuild

Replace the entire menu structure:

```javascript
const newMenuStructure = [
  { order: 1, name: 'Home', type: 'page', page_id: 1 },
  { order: 2, name: 'About', type: 'page', page_id: 2 },
  { order: 3, name: 'Contact', type: 'page', page_id: 3 },
]

await fetch('/api/v1/menus/sync', {
  method: 'POST',
  body: JSON.stringify({
    api_token: adminToken,
    website_id: 1,
    menu_items: newMenuStructure,
  }),
})
```

### Adding Items to Existing Menu

First fetch current menu, then add new items:

```javascript
// 1. Get current menu items
const current = await getCurrentMenuItems(websiteId)

// 2. Add new items
const updated = [
  ...current,
  { order: current.length + 1, name: 'New Page', type: 'page', page_id: 99 },
]

// 3. Sync
await syncMenu(updated)
```

## Related Endpoints

- [Pages API](./pages.md) - Manage pages referenced in menus
- [Posts API](./posts.md) - Create content for menu destinations

## Troubleshooting

**Menu not updating on frontend?**

- Clear browser cache
- Check if menu cache is enabled
- Verify menu is assigned to correct website

**Page URLs not generating?**

- Ensure page_id exists in database
- Check page has valid slug
- Verify website configuration

For more troubleshooting tips, see the [Troubleshooting Guide](../troubleshooting.md).
