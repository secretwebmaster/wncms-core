# 文章 API

文章 API 让您能够在 WNCMS 中建立、检索、更新和删除部落格文章和文章。

## 端点总览

| 方法     | 端点                          | 说明                         |
| -------- | ----------------------------- | ---------------------------- |
| GET/POST | `/api/v1/posts`               | 列出所有文章并支援筛选和分页 |
| POST     | `/api/v1/posts/store`         | 建立新文章                   |
| POST     | `/api/v1/posts/update/{slug}` | 更新现有文章                 |
| POST     | `/api/v1/posts/delete/{slug}` | 删除文章                     |
| GET/POST | `/api/v1/posts/{slug}`        | 透过 slug 取得单一文章       |

## 列出文章

检索文章列表，支援可选的筛选、排序和分页。

### 端点

```
GET|POST /api/v1/posts
```

### 身份验证

必需：可透过 `wncms_api_posts_index` 设定配置

### 请求参数

| 参数                | 类型    | 必需 | 预设值       | 说明                              |
| ------------------- | ------- | ---- | ------------ | --------------------------------- |
| `api_token`         | string  | 是\* | -            | 使用者 API token 用于身份验证     |
| `keywords`          | string  | 否   | -            | 在标题和内容中搜寻                |
| `tags`              | array   | 否   | -            | 要筛选的标签 ID 阵列              |
| `tag_type`          | string  | 否   | -            | 标签类型（例如「post_category」） |
| `excluded_post_ids` | array   | 否   | -            | 要排除的文章 ID 阵列              |
| `sort`              | string  | 否   | `created_at` | 排序栏位                          |
| `direction`         | string  | 否   | `desc`       | 排序方向：`asc` 或 `desc`         |
| `page_size`         | integer | 否   | 15           | 每页文章数                        |
| `page`              | integer | 否   | 1            | 页码                              |
| `is_random`         | boolean | 否   | false        | 以随机顺序返回文章                |

\*如果此端点启用了身份验证则为必需

### 请求范例

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

### 回应范例

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

## 建立文章

建立新的部落格文章。

### 端点

```
POST /api/v1/posts/store
```

### 身份验证

必需：可透过 `wncms_api_posts_store` 设定配置

### 请求参数

| 参数               | 类型          | 必需 | 说明                                        |
| ------------------ | ------------- | ---- | ------------------------------------------- |
| `api_token`        | string        | 是\* | 使用者 API token                            |
| `title`            | string/object | 是   | 文章标题。若传入对象会先标准化，再以纯文字储存 |
| `content`          | string        | 是   | 文章内容（允许 HTML）                       |
| `slug`             | string        | 否   | 自定义 slug（如果未提供则从标题自动产生）   |
| `excerpt`          | string        | 否   | 简短描述/摘要                               |
| `thumbnail`        | file          | 否   | 特色图片上传                                |
| `thumbnail_url`    | string        | 否   | 特色图片 URL（文件上传的替代方案）          |
| `author_id`        | integer       | 否   | 作者使用者 ID（预设为已验证使用者）         |
| `status`           | string        | 否   | 文章状态：`draft`、`published`、`scheduled` |
| `published_at`     | datetime      | 否   | 发布日期（ISO 8601 格式）                   |
| `meta_title`       | string        | 否   | SEO 中继标题                                |
| `meta_description` | string        | 否   | SEO 中继描述                                |
| `meta_keywords`    | string        | 否   | SEO 关键字                                  |
| `tags`             | array         | 否   | 标签 ID 阵列                                |
| `categories`       | array         | 否   | 分类 ID 阵列                                |
| `website_id`       | integer/array | 否   | 多站点的网站 ID                             |
| `localize_images`  | boolean       | 否   | 下载并储存远端图片                          |

\*如果启用了身份验证则为必需

### 可翻译栏位储存格式

- 对可翻译栏位（`title`、`excerpt`、`keywords`、`content`、`label`），基础栏位会以纯文字写入资料表。
- 若可翻译栏位传入 locale JSON/对象（例如 `{"en":"Hello","zh-TW":"哈囉"}`），API 会选取一个值作为基础栏位，并透过 `HasTranslations` 同步保存各语言翻译。
- 保存翻译前会先标准化 locale key（例如 `zh-TW` 会转换并写入为 `zh_TW`）。

### 请求范例

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

### 档案上传请求

```bash
curl -X POST "https://your-domain.com/api/v1/posts/store" \
  -H "Authorization: Bearer your-api-token-here" \
  -F "title=My New Post" \
  -F "content=Post content here" \
  -F "thumbnail=@/path/to/image.jpg" \
  -F "tags[]=1" \
  -F "tags[]=2"
```

### 回应范例

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

## 更新文章

透过 slug 或 ID 更新现有文章。

### 端点

```
POST /api/v1/posts/update/{slug}
```

### 身份验证

必需：可透过 `wncms_api_posts_update` 设定配置

### URL 参数

| 参数   | 类型   | 说明            |
| ------ | ------ | --------------- |
| `slug` | string | 文章 slug 或 ID |

### 请求参数

与建立文章相同，除了 `api_token` 外所有栏位都是可选的。只包含您想更新的栏位。

### 请求范例

```bash
curl -X POST "https://your-domain.com/api/v1/posts/update/my-post-slug" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "title": "Updated Title",
    "status": "published"
  }'
```

### 回应范例

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

## 删除文章

透过 slug 或 ID 删除文章。

### 端点

```
POST /api/v1/posts/delete/{slug}
```

### 身份验证

必需：可透过 `wncms_api_posts_delete` 设定配置

### URL 参数

| 参数   | 类型   | 说明            |
| ------ | ------ | --------------- |
| `slug` | string | 文章 slug 或 ID |

### 请求范例

```bash
curl -X POST "https://your-domain.com/api/v1/posts/delete/old-post-slug" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here"
  }'
```

### 回应范例

```json
{
  "code": 200,
  "status": "success",
  "message": "Post deleted successfully",
  "data": null,
  "extra": {}
}
```

## 取得单一文章

透过 slug 或 ID 检索单一文章。

### 端点

```
GET|POST /api/v1/posts/{slug}
```

### 身份验证

必需：可透过 `wncms_api_posts_show` 设定配置

### URL 参数

| 参数   | 类型   | 说明            |
| ------ | ------ | --------------- |
| `slug` | string | 文章 slug 或 ID |

### 请求范例

```bash
curl -X POST "https://your-domain.com/api/v1/posts/getting-started-with-laravel" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here"
  }'
```

### 回应范例

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

## 进阶功能

### 图片本地化

建立/更新文章时，您可以自动下载远端图片并在本地储存：

```json
{
  "api_token": "your-api-token-here",
  "title": "Post with Remote Images",
  "content": "<img src='https://external-site.com/image.jpg'>",
  "localize_images": true
}
```

WNCMS 将：

1. 找到内容中的所有 `<img>` 标签
2. 下载外部图片
3. 将它们储存到您的媒体库
4. 使用本地 URL 更新内容

图片格式行为遵循 **System Settings -> Content -> `convert_thumbnail_to_webp`**：

- `开启`：本地化/克隆的内容图片会转换为 `.webp`
- `关闭`：本地化/克隆的内容图片保留原始副档名

### 批次标记

一次指派多个标签和分类：

```json
{
  "api_token": "your-api-token-here",
  "title": "Well-Categorized Post",
  "content": "Content here...",
  "tags": [1, 2, 3, 4, 5],
  "categories": [10, 20]
}
```

### 多站点支援

如果启用了多站点模式，将文章指派给特定站点：

```json
{
  "api_token": "your-api-token-here",
  "title": "Cross-Site Post",
  "content": "Content here...",
  "website_id": [1, 2, 3]
}
```

## 错误回应

### 403 - API 已停用

```json
{
  "status": 403,
  "message": "API access is disabled"
}
```

### 401 - 无效 Token

```json
{
  "status": "fail",
  "message": "Invalid token"
}
```

### 422 - 验证失败

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

### 404 - 文章未找到

```json
{
  "code": 404,
  "status": "fail",
  "message": "Post not found"
}
```

### 500 - 伺服器错误

```json
{
  "code": 500,
  "status": "fail",
  "message": "Server Error: Database connection failed"
}
```

## 最佳实务

1. **使用分页**：始终使用 `page_size` 来限制结果以获得更好的效能
2. **有效筛选**：使用 `tags`、`keywords` 和其他筛选器来精确获取您需要的内容
3. **处理错误**：在处理资料之前始终检查 `status` 栏位
4. **最佳化图片**：在上传前压缩图片以减少频宽
5. **快取结果**：在您这边快取文章列表以减少 API 呼叫
6. **使用 Slug**：优先使用 slug 而不是 ID 以获得更好的 URL 可读性

## 程式码范例

查看[范例](../examples.md)页面以获得各种程式语言的完整程式码实作。

## 相关端点

- [标签 API](./tags.md) - 管理文章标签和分类
- [页面 API](./pages.md) - 管理静态页面
- [选单 API](./menus.md) - 组织导览

## 疑难排解

有关常见问题和解决方案，请参阅[疑难排解指南](../troubleshooting.md)。
