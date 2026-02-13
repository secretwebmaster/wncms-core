# Posts API

The Posts API allows you to create, retrieve, update, and delete blog posts and articles in WNCMS.

## Endpoints Overview

| Method   | Endpoint                      | Description                                  |
| -------- | ----------------------------- | -------------------------------------------- |
| GET/POST | `/api/v1/posts`               | List all posts with filtering and pagination |
| POST     | `/api/v1/posts/store`         | Create a new post                            |
| POST     | `/api/v1/posts/update/{slug}` | Update an existing post                      |
| POST     | `/api/v1/posts/delete/{slug}` | Delete a post                                |
| GET/POST | `/api/v1/posts/{slug}`        | Get a single post by slug                    |

## List Posts

Retrieve a list of posts with optional filtering, sorting, and pagination.

### Endpoint

```
GET|POST /api/v1/posts
```

### Authentication

Required: Configurable via `wncms_api_posts_index` setting

### Request Parameters

| Parameter           | Type    | Required | Default      | Description                       |
| ------------------- | ------- | -------- | ------------ | --------------------------------- |
| `api_token`         | string  | Yes\*    | -            | User API token for authentication |
| `keywords`          | string  | No       | -            | Search in title and content       |
| `tags`              | array   | No       | -            | Array of tag IDs to filter by     |
| `tag_type`          | string  | No       | -            | Tag type (e.g., "post_category")  |
| `excluded_post_ids` | array   | No       | -            | Array of post IDs to exclude      |
| `sort`              | string  | No       | `created_at` | Field to sort by                  |
| `direction`         | string  | No       | `desc`       | Sort direction: `asc` or `desc`   |
| `page_size`         | integer | No       | 15           | Number of posts per page          |
| `page`              | integer | No       | 1            | Page number                       |
| `is_random`         | boolean | No       | false        | Return posts in random order      |

\*Required if authentication is enabled for this endpoint

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/posts" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "keywords": "Laravel",
    "tags": [1, 2, 3],
    "page_size": 10,
    "sort": "created_at",
    "direction": "desc"
  }'
```

### Response Example

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched posts",
  "data": {
    "data": [
      {
        "id": 123,
        "title": "Getting Started with Laravel",
        "slug": "getting-started-with-laravel",
        "excerpt": "A comprehensive guide to Laravel...",
        "content": "Full post content here...",
        "thumbnail": "https://your-domain.com/storage/posts/thumbnail.jpg",
        "author": {
          "id": 1,
          "name": "John Doe"
        },
        "tags": [
          {
            "id": 1,
            "name": "Laravel",
            "type": "post_category"
          }
        ],
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
      }
    ],
    "pagination": {
      "total": 150,
      "count": 10,
      "page_size": 10,
      "current_page": 1,
      "last_page": 15,
      "has_more": true,
      "next": "/api/v1/posts?page=2",
      "previous": null
    }
  },
  "extra": {}
}
```

## Create Post

Create a new blog post.

### Endpoint

```
POST /api/v1/posts/store
```

### Authentication

Required: Configurable via `wncms_api_posts_store` setting

### Request Parameters

| Parameter          | Type          | Required | Description                                             |
| ------------------ | ------------- | -------- | ------------------------------------------------------- |
| `api_token`        | string        | Yes\*    | User API token                                          |
| `title`            | string/object | Yes      | Post title. Object input is normalized and stored as plain text |
| `content`          | string        | Yes      | Post content (HTML allowed)                             |
| `slug`             | string        | No       | Custom slug (auto-generated from title if not provided) |
| `excerpt`          | string        | No       | Short description/summary                               |
| `thumbnail`        | file          | No       | Featured image upload                                   |
| `thumbnail_url`    | string        | No       | Featured image URL (alternative to file upload)         |
| `author_id`        | integer       | No       | Author user ID (defaults to authenticated user)         |
| `status`           | string        | No       | Post status: `draft`, `published`, `scheduled`          |
| `published_at`     | datetime      | No       | Publication date (ISO 8601 format)                      |
| `meta_title`       | string        | No       | SEO meta title                                          |
| `meta_description` | string        | No       | SEO meta description                                    |
| `meta_keywords`    | string        | No       | SEO keywords                                            |
| `tags`             | array         | No       | Array of tag IDs                                        |
| `categories`       | array         | No       | Array of category IDs                                   |
| `website_id`       | integer/array | No       | Website ID(s) for multi-site                            |
| `localize_images`  | boolean       | No       | Download and save remote images                         |

\*Required if authentication is enabled

Multisite binding notes:

- `website_id` accepts a single ID or an array.
- For scoped post models (`single`/`multi`), website bindings are synced after create/update.
- If `website_id` is omitted, API falls back to current website context when available.

### Translatable Field Storage Format

- For translatable fields (`title`, `excerpt`, `keywords`, `content`, `label`), base column values are persisted as plain text.
- If a translatable field is sent as locale JSON/object (for example `{"en":"Hello","zh-TW":"哈囉"}`), the API uses one value as the base column value and saves locale entries through `HasTranslations`.
- Locale keys are normalized before saving translations (for example `zh-TW` will be stored as `zh_TW`).

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/posts/store" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "title": "My New Post",
    "content": "<p>This is the post content with <strong>HTML</strong>.</p>",
    "excerpt": "A brief summary of the post",
    "status": "published",
    "tags": [1, 2, 3],
    "categories": [5],
    "meta_title": "Custom SEO Title",
    "meta_description": "SEO-friendly description"
  }'
```

### Request with File Upload

```bash
curl -X POST "https://your-domain.com/api/v1/posts/store" \
  -H "Authorization: Bearer your-api-token-here" \
  -F "title=My New Post" \
  -F "content=Post content here" \
  -F "thumbnail=@/path/to/image.jpg" \
  -F "tags[]=1" \
  -F "tags[]=2"
```

### Response Example

```json
{
  "code": 200,
  "status": "success",
  "message": "Post #123 created successfully",
  "data": {
    "id": 123,
    "title": "My New Post",
    "slug": "my-new-post",
    "content": "<p>This is the post content with <strong>HTML</strong>.</p>",
    "excerpt": "A brief summary of the post",
    "thumbnail": "https://your-domain.com/storage/posts/123/thumbnail.jpg",
    "status": "published",
    "author": {
      "id": 1,
      "name": "John Doe"
    },
    "tags": [
      { "id": 1, "name": "Laravel" },
      { "id": 2, "name": "PHP" }
    ],
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  },
  "extra": {}
}
```

## Update Post

Update an existing post by slug or ID.

### Endpoint

```
POST /api/v1/posts/update/{slug}
```

### Authentication

Required: Configurable via `wncms_api_posts_update` setting

### URL Parameters

| Parameter | Type   | Description     |
| --------- | ------ | --------------- |
| `slug`    | string | Post slug or ID |

### Request Parameters

Same as Create Post, all fields are optional except `api_token`. Only include fields you want to update.

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/posts/update/my-post-slug" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "title": "Updated Title",
    "status": "published"
  }'
```

### Response Example

```json
{
  "code": 200,
  "status": "success",
  "message": "Post #123 updated successfully",
  "data": {
    "id": 123,
    "title": "Updated Title",
    "slug": "updated-title",
    "status": "published",
    "updated_at": "2024-01-15T11:00:00.000000Z"
  },
  "extra": {}
}
```

## Delete Post

Delete a post by slug or ID.

### Endpoint

```
POST /api/v1/posts/delete/{slug}
```

### Authentication

Required: Configurable via `wncms_api_posts_delete` setting

### URL Parameters

| Parameter | Type   | Description     |
| --------- | ------ | --------------- |
| `slug`    | string | Post slug or ID |

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/posts/delete/old-post-slug" \
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
  "message": "Post deleted successfully",
  "data": null,
  "extra": {}
}
```

## Get Single Post

Retrieve a single post by slug or ID.

### Endpoint

```
GET|POST /api/v1/posts/{slug}
```

### Authentication

Required: Configurable via `wncms_api_posts_show` setting

### URL Parameters

| Parameter | Type   | Description     |
| --------- | ------ | --------------- |
| `slug`    | string | Post slug or ID |

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/posts/getting-started-with-laravel" \
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
  "message": "Successfully fetched post",
  "data": {
    "id": 123,
    "title": "Getting Started with Laravel",
    "slug": "getting-started-with-laravel",
    "content": "Full post content...",
    "excerpt": "Brief summary...",
    "thumbnail": "https://your-domain.com/storage/posts/thumbnail.jpg",
    "status": "published",
    "author": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "tags": [...],
    "categories": [...],
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  },
  "extra": {}
}
```

## Advanced Features

### Image Localization

When creating/updating posts, you can automatically download remote images and save them locally:

```json
{
  "api_token": "your-api-token-here",
  "title": "Post with Remote Images",
  "content": "<img src='https://external-site.com/image.jpg'>",
  "localize_images": true
}
```

WNCMS will:

1. Find all `<img>` tags in the content
2. Download external images
3. Save them to your media library
4. Update the content with local URLs

Image format behavior follows **System Settings -> Content -> `convert_thumbnail_to_webp`**:

- `On`: localized/cloned content images are converted to `.webp`
- `Off`: localized/cloned content images keep their source extension

### Batch Tagging

Assign multiple tags and categories at once:

```json
{
  "api_token": "your-api-token-here",
  "title": "Well-Categorized Post",
  "content": "Content here...",
  "tags": [1, 2, 3, 4, 5],
  "categories": [10, 20]
}
```

### Multi-Website Support

If multi-website mode is enabled, assign posts to specific sites:

```json
{
  "api_token": "your-api-token-here",
  "title": "Cross-Site Post",
  "content": "Content here...",
  "website_id": [1, 2, 3]
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
      "title": ["The title field is required."],
      "content": ["The content field is required."]
    }
  }
}
```

### 404 - Post Not Found

```json
{
  "code": 404,
  "status": "fail",
  "message": "Post not found"
}
```

### 500 - Server Error

```json
{
  "code": 500,
  "status": "fail",
  "message": "Server Error: Database connection failed"
}
```

## Best Practices

1. **Use Pagination**: Always use `page_size` to limit results for better performance
2. **Filter Effectively**: Use `tags`, `keywords`, and other filters to get exactly what you need
3. **Handle Errors**: Always check the `status` field before processing data
4. **Optimize Images**: Compress images before uploading to reduce bandwidth
5. **Cache Results**: Cache post lists on your end to reduce API calls
6. **Use Slugs**: Prefer slugs over IDs for better URL readability

## Code Examples

See the [Examples](../examples.md) page for complete code implementations in various programming languages.

## Related Endpoints

- [Tags API](./tags.md) - Manage post tags and categories
- [Pages API](./pages.md) - Manage static pages
- [Menus API](./menus.md) - Organize navigation

## Troubleshooting

For common issues and solutions, see the [Troubleshooting Guide](../troubleshooting.md).
