# Tags API

The Tags API allows you to create and manage tags and categories for organizing your content in WNCMS.

## Endpoints Overview

| Method | Endpoint             | Description                         |
| ------ | -------------------- | ----------------------------------- |
| POST   | `/api/v1/tags`       | List tags with localization support |
| POST   | `/api/v1/tags/exist` | Check if tag IDs exist              |
| POST   | `/api/v1/tags/store` | Create or update a tag              |

## List Tags

Retrieve tags by type with optional localization.

### Endpoint

```
POST /api/v1/tags
```

### Authentication

Required: Configurable via settings

### Feature Toggle

- `wncms_api_tag_index`

### Request Parameters

| Parameter   | Type   | Required | Default        | Description                                  |
| ----------- | ------ | -------- | -------------- | -------------------------------------------- |
| `api_token` | string | Yes\*    | -              | User API token                               |
| `type`      | string | Yes      | -              | Tag type (e.g., "post_category", "post_tag") |
| `locale`    | string | No       | System default | Language code for translations               |

\*Required if authentication is enabled

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/tags" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "type": "post_category",
    "locale": "zh-TW"
  }'
```

### Response Example

```json
{
  "code": 200,
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "技術",
      "slug": "technology",
      "type": "post_category",
      "parent_id": null,
      "description": "技術相關文章",
      "icon": null,
      "sort": 10,
      "children": [
        {
          "id": 2,
          "name": "程式設計",
          "slug": "programming",
          "type": "post_category",
          "parent_id": 1,
          "sort": 5,
          "children": []
        }
      ],
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

### Features

- **Hierarchical Structure**: Returns tags with nested children
- **Localization**: Name is translated based on `locale` parameter
- **Sorted**: Results ordered by `sort` field (descending)
- **Multi-Level**: Supports nested children (children of children)

## Check Tag Existence

Verify if specific tag IDs exist in the database.

### Endpoint

```
POST /api/v1/tags/exist
```

### Authentication

Required: Configurable via settings

### Feature Toggle

- `wncms_api_tag_exist`

### Request Parameters

| Parameter   | Type   | Required | Description               |
| ----------- | ------ | -------- | ------------------------- |
| `api_token` | string | Yes\*    | User API token            |
| `tagIds`    | array  | Yes      | Array of tag IDs to check |

\*Required if authentication is enabled

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/tags/exist" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "tagIds": [1, 2, 5, 99, 100]
  }'
```

### Response Example

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched data",
  "data": {
    "ids": [1, 2, 5]
  },
  "extra": {}
}
```

The response returns only the IDs that exist. In this example, tags 99 and 100 don't exist.

### Use Cases

- **Validation**: Check if tags exist before assigning to posts
- **Cleanup**: Identify missing tags in import data
- **Verification**: Confirm tags are available before creating relationships

## Create Tag

Create a new tag or update an existing one if duplicates are found.

### Endpoint

```
POST /api/v1/tags/store
```

### Authentication

Required: Configurable via settings

### Feature Toggle

Primary setting key: `wncms_api_tag_store`.

Legacy compatibility: if `enable_api_tag_store` is still enabled in old setups, this endpoint remains available.

### Request Parameters

| Parameter                | Type                 | Required | Default         | Description                            |
| ------------------------ | -------------------- | -------- | --------------- | -------------------------------------- |
| `api_token`              | string               | Yes      | -               | User API token for authentication      |
| `name`                   | string               | Yes      | -               | Tag display name (max: 255 chars)      |
| `slug`                   | string               | No       | Auto-generated  | URL-friendly identifier                |
| `type`                   | string               | No       | `post_category` | Tag type (max: 50 chars)               |
| `parent_id`              | integer              | No       | null            | Parent tag ID for hierarchical tags    |
| `description`            | string               | No       | -               | Tag description                        |
| `icon`                   | string               | No       | -               | Icon identifier or class               |
| `sort`                   | integer              | No       | 0               | Sort order (higher = earlier)          |
| `website_id`             | integer/array/string | No       | -               | Website ID(s) for multi-site           |
| `update_when_duplicated` | boolean              | No       | false           | Update existing tag if duplicate found |

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/tags/store" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "name": "Laravel Framework",
    "slug": "laravel",
    "type": "post_category",
    "description": "Posts about Laravel PHP framework",
    "sort": 100
  }'
```

### Response Example - New Tag Created

```json
{
  "status": "success",
  "message": "tag #5 created",
  "data": {
    "id": 5,
    "name": "Laravel Framework",
    "slug": "laravel",
    "type": "post_category",
    "parent_id": null,
    "description": "Posts about Laravel PHP framework",
    "icon": null,
    "sort": 100,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

### Response Example - Duplicate Found (No Update)

```json
{
  "status": "success",
  "message": "Skipped. Duplicated tag found",
  "data": {
    "id": 3,
    "name": "Laravel Framework",
    "slug": "laravel",
    "type": "post_category",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

### Response Example - Duplicate Updated

When `update_when_duplicated` is `true`:

```json
{
  "status": "success",
  "message": "Existing tag updated",
  "data": {
    "id": 3,
    "name": "Laravel Framework",
    "slug": "laravel",
    "description": "Posts about Laravel PHP framework",
    "sort": 100,
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

## Duplicate Handling

The API detects duplicates based on `name` and `type` combination.

### Scenario 1: Skip Duplicates (Default)

```json
{
  "name": "Existing Tag",
  "type": "post_category"
}
```

Result: Returns existing tag without modification.

### Scenario 2: Update Duplicates

```json
{
  "name": "Existing Tag",
  "type": "post_category",
  "description": "Updated description",
  "update_when_duplicated": true
}
```

Result: Updates the existing tag with new data.

### Scenario 3: Create New (Different Type)

```json
{
  "name": "My Tag",
  "type": "post_tag" // Different type
}
```

Even if "My Tag" exists as `post_category`, this creates a new tag because the type is different.

## Tag Types

Common tag types in WNCMS:

| Type               | Description        | Use Case                              |
| ------------------ | ------------------ | ------------------------------------- |
| `post_category`    | Post categories    | Primary classification for blog posts |
| `post_tag`         | Post tags          | Secondary keywords for posts          |
| `product_category` | Product categories | E-commerce product classification     |
| `link_category`    | Link categories    | Organizing links/bookmarks            |
| `page_category`    | Page categories    | Static page organization              |

You can create custom types as needed.

## Hierarchical Tags

Create nested tag structures using `parent_id`:

### Example: Technology Category Tree

```bash
# 1. Create parent category
curl -X POST "/api/v1/tags/store" -d '{
  "api_token": "token",
  "name": "Technology",
  "type": "post_category"
}'
# Response: {"data": {"id": 10, ...}}

# 2. Create child category
curl -X POST "/api/v1/tags/store" -d '{
  "api_token": "token",
  "name": "Programming",
  "type": "post_category",
  "parent_id": 10
}'
# Response: {"data": {"id": 11, ...}}

# 3. Create grandchild
curl -X POST "/api/v1/tags/store" -d '{
  "api_token": "token",
  "name": "PHP",
  "type": "post_category",
  "parent_id": 11
}'
```

This creates:

```
Technology (id: 10)
└── Programming (id: 11)
    └── PHP (id: 12)
```

## Multi-Website Support

Assign tags to specific websites in multi-site installations:

### Single Website

```json
{
  "api_token": "token",
  "name": "Site-Specific Tag",
  "website_id": 1
}
```

### Multiple Websites (Array)

```json
{
  "api_token": "token",
  "name": "Cross-Site Tag",
  "website_id": [1, 2, 3]
}
```

### Multiple Websites (Comma-Separated)

```json
{
  "api_token": "token",
  "name": "Cross-Site Tag",
  "website_id": "1,2,3"
}
```

## Slug Generation

If `slug` is not provided, it's auto-generated from `name`:

```json
{
  "name": "My Tag Name"
}
```

Generated slug: `my-tag-name`

### Custom Slugs

Provide your own slug:

```json
{
  "name": "Search Engine Optimization",
  "slug": "seo"
}
```

## Error Responses

### 403 - API Disabled

```json
{
  "status": 403,
  "message": "API access is disabled"
}
```

### 401 - Invalid Token

```json
{
  "status": "fail",
  "message": "Invalid token"
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
      "name": ["The name field is required."],
      "parent_id": ["The selected parent_id is invalid."]
    }
  }
}
```

### 500 - Server Error

```json
{
  "status": 500,
  "message": "Server Error: Database connection failed"
}
```

## Best Practices

### 1. Validate Parent IDs

Ensure parent tag exists before creating child:

```javascript
// Check if parent exists
const checkResult = await fetch('/api/v1/tags/exist', {
  method: 'POST',
  body: JSON.stringify({
    api_token: token,
    tagIds: [parentId],
  }),
})

if (checkResult.ids.includes(parentId)) {
  // Safe to create child tag
}
```

### 2. Use Consistent Types

Maintain type consistency across your application:

```javascript
const TAG_TYPES = {
  POST_CATEGORY: 'post_category',
  POST_TAG: 'post_tag',
}

// Use constants instead of hardcoded strings
createTag({ type: TAG_TYPES.POST_CATEGORY })
```

### 3. Handle Duplicates Intentionally

Decide upfront how to handle duplicates:

```javascript
// For bulk imports - skip duplicates
const importTags = tags.map((tag) => ({
  ...tag,
  update_when_duplicated: false,
}))

// For manual updates - update duplicates
const manualTag = {
  name: 'Updated Tag',
  update_when_duplicated: true,
}
```

### 4. Sort Strategically

Use sort order for display priority:

```javascript
// Featured categories get higher sort values
{
  name: "Featured",
  sort: 1000
}

// Regular categories
{
  name: "Regular",
  sort: 100
}
```

### 5. Clear Cache After Bulk Operations

```javascript
// After creating multiple tags
await Promise.all(tags.map((tag) => createTag(tag)))

// Clear your application's tag cache
cache.delete('tags:all')
```

## Use Cases

### Import Tags from CSV

```javascript
async function importTagsFromCSV(csvData, apiToken) {
  const tags = parseCSV(csvData) // Your CSV parser

  for (const tag of tags) {
    try {
      const result = await fetch('/api/v1/tags/store', {
        method: 'POST',
        body: JSON.stringify({
          api_token: apiToken,
          name: tag.name,
          type: tag.type,
          update_when_duplicated: false,
        }),
      })

      console.log(`Created: ${tag.name}`)
    } catch (error) {
      console.error(`Failed: ${tag.name}`, error)
    }
  }
}
```

### Sync Tags Between Systems

```javascript
async function syncTags(externalTags, apiToken) {
  // 1. Get existing tag IDs
  const existingIds = await fetch('/api/v1/tags/exist', {
    method: 'POST',
    body: JSON.stringify({
      api_token: apiToken,
      tagIds: externalTags.map((t) => t.id),
    }),
  }).then((r) => r.json())

  // 2. Create missing tags
  const missing = externalTags.filter((t) => !existingIds.ids.includes(t.id))

  for (const tag of missing) {
    await createTag(tag, apiToken)
  }
}
```

## Related Endpoints

- [Posts API](./posts.md) - Assign tags to posts
- [Pages API](./pages.md) - Categorize pages

## Troubleshooting

**Tags not appearing in the correct order?**

- Check the `sort` field values (higher = earlier)
- Verify query orders by `sort desc`

**Localized names not showing?**

- Ensure translations exist in the database
- Pass correct `locale` parameter
- Check default locale setting

**Parent-child relationship not working?**

- Verify `parent_id` points to existing tag
- Check both tags have same `type`
- Use tags/exist endpoint to confirm IDs

For more help, see the [Troubleshooting Guide](../troubleshooting.md).
