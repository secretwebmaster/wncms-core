# 文章 API

文章 API 讓您能夠在 WNCMS 中建立、檢索、更新和刪除部落格文章和文章。

## 端點總覽

| 方法     | 端點                          | 說明                         |
| -------- | ----------------------------- | ---------------------------- |
| GET/POST | `/api/v1/posts`               | 列出所有文章並支援篩選和分頁 |
| POST     | `/api/v1/posts/store`         | 建立新文章                   |
| POST     | `/api/v1/posts/update/{slug}` | 更新現有文章                 |
| POST     | `/api/v1/posts/delete/{slug}` | 刪除文章                     |
| GET/POST | `/api/v1/posts/{slug}`        | 透過 slug 取得單一文章       |

## 列出文章

檢索文章列表，支援可選的篩選、排序和分頁。

### 端點

```
GET|POST /api/v1/posts
```

### 身份驗證

必需：可透過 `wncms_api_posts_index` 設定配置

### 請求參數

| 參數                | 類型    | 必需 | 預設值       | 說明                              |
| ------------------- | ------- | ---- | ------------ | --------------------------------- |
| `api_token`         | string  | 是\* | -            | 使用者 API token 用於身份驗證     |
| `keywords`          | string  | 否   | -            | 在標題和內容中搜尋                |
| `tags`              | array   | 否   | -            | 要篩選的標籤 ID 陣列              |
| `tag_type`          | string  | 否   | -            | 標籤類型（例如「post_category」） |
| `excluded_post_ids` | array   | 否   | -            | 要排除的文章 ID 陣列              |
| `sort`              | string  | 否   | `created_at` | 排序欄位                          |
| `direction`         | string  | 否   | `desc`       | 排序方向：`asc` 或 `desc`         |
| `page_size`         | integer | 否   | 15           | 每頁文章數                        |
| `page`              | integer | 否   | 1            | 頁碼                              |
| `is_random`         | boolean | 否   | false        | 以隨機順序返回文章                |

\*如果此端點啟用了身份驗證則為必需

### 請求範例

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

### 回應範例

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

### 端點

```
POST /api/v1/posts/store
```

### 身份驗證

必需：可透過 `wncms_api_posts_store` 設定配置

### 請求參數

| 參數               | 類型          | 必需 | 說明                                        |
| ------------------ | ------------- | ---- | ------------------------------------------- |
| `api_token`        | string        | 是\* | 使用者 API token                            |
| `title`            | string/object | 是   | 文章標題。若傳入物件會先標準化，再以純文字儲存 |
| `content`          | string        | 是   | 文章內容（允許 HTML）                       |
| `slug`             | string        | 否   | 自定義 slug（如果未提供則從標題自動產生）   |
| `excerpt`          | string        | 否   | 簡短描述/摘要                               |
| `thumbnail`        | file          | 否   | 特色圖片上傳                                |
| `thumbnail_url`    | string        | 否   | 特色圖片 URL（文件上傳的替代方案）          |
| `author_id`        | integer       | 否   | 作者使用者 ID（預設為已驗證使用者）         |
| `status`           | string        | 否   | 文章狀態：`draft`、`published`、`scheduled` |
| `published_at`     | datetime      | 否   | 發布日期（ISO 8601 格式）                   |
| `meta_title`       | string        | 否   | SEO 中繼標題                                |
| `meta_description` | string        | 否   | SEO 中繼描述                                |
| `meta_keywords`    | string        | 否   | SEO 關鍵字                                  |
| `tags`             | array         | 否   | 標籤 ID 陣列                                |
| `categories`       | array         | 否   | 分類 ID 陣列                                |
| `website_id`       | integer/array | 否   | 多站點的網站 ID                             |
| `localize_images`  | boolean       | 否   | 下載並儲存遠端圖片                          |

\*如果啟用了身份驗證則為必需

多站點綁定說明：

- `website_id` 支援單一 ID 或 ID 陣列。
- 對 `single`/`multi` 模式的文章模型，建立/更新後會同步網站綁定。
- 若未傳 `website_id`，API 會在可用時回退到當前網站上下文。

### 可翻譯欄位儲存格式

- 對可翻譯欄位（`title`、`excerpt`、`keywords`、`content`、`label`），基礎欄位會以純文字寫入資料表。
- 若可翻譯欄位傳入 locale JSON/物件（例如 `{"en":"Hello","zh-TW":"哈囉"}`），API 會選取一個值作為基礎欄位，並透過 `HasTranslations` 同步保存各語言翻譯。
- 保存翻譯前會先標準化 locale key（例如 `zh-TW` 會轉換並寫入為 `zh_TW`）。

### 請求範例

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

### 檔案上傳請求

```bash
curl -X POST "https://your-domain.com/api/v1/posts/store" \
  -H "Authorization: Bearer your-api-token-here" \
  -F "title=My New Post" \
  -F "content=Post content here" \
  -F "thumbnail=@/path/to/image.jpg" \
  -F "tags[]=1" \
  -F "tags[]=2"
```

### 回應範例

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

透過 slug 或 ID 更新現有文章。

### 端點

```
POST /api/v1/posts/update/{slug}
```

### 身份驗證

必需：可透過 `wncms_api_posts_update` 設定配置

### URL 參數

| 參數   | 類型   | 說明            |
| ------ | ------ | --------------- |
| `slug` | string | 文章 slug 或 ID |

### 請求參數

與建立文章相同，除了 `api_token` 外所有欄位都是可選的。只包含您想更新的欄位。

### 請求範例

```bash
curl -X POST "https://your-domain.com/api/v1/posts/update/my-post-slug" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "title": "Updated Title",
    "status": "published"
  }'
```

### 回應範例

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

## 刪除文章

透過 slug 或 ID 刪除文章。

### 端點

```
POST /api/v1/posts/delete/{slug}
```

### 身份驗證

必需：可透過 `wncms_api_posts_delete` 設定配置

### URL 參數

| 參數   | 類型   | 說明            |
| ------ | ------ | --------------- |
| `slug` | string | 文章 slug 或 ID |

### 請求範例

```bash
curl -X POST "https://your-domain.com/api/v1/posts/delete/old-post-slug" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here"
  }'
```

### 回應範例

```json
{
  "code": 200,
  "status": "success",
  "message": "Post deleted successfully",
  "data": null,
  "extra": {}
}
```

## 取得單一文章

透過 slug 或 ID 檢索單一文章。

### 端點

```
GET|POST /api/v1/posts/{slug}
```

### 身份驗證

必需：可透過 `wncms_api_posts_show` 設定配置

### URL 參數

| 參數   | 類型   | 說明            |
| ------ | ------ | --------------- |
| `slug` | string | 文章 slug 或 ID |

### 請求範例

```bash
curl -X POST "https://your-domain.com/api/v1/posts/getting-started-with-laravel" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here"
  }'
```

### 回應範例

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

## 進階功能

### 圖片本地化

建立/更新文章時，您可以自動下載遠端圖片並在本地儲存：

```json
{
  "api_token": "your-api-token-here",
  "title": "Post with Remote Images",
  "content": "<img src='https://external-site.com/image.jpg'>",
  "localize_images": true
}
```

WNCMS 將：

1. 找到內容中的所有 `<img>` 標籤
2. 下載外部圖片
3. 將它們儲存到您的媒體庫
4. 使用本地 URL 更新內容

圖片格式行為遵循 **System Settings -> Content -> `convert_thumbnail_to_webp`**：

- `開啟`：本地化/複製的內容圖片會轉換為 `.webp`
- `關閉`：本地化/複製的內容圖片保留原始副檔名

### 批次標記

一次指派多個標籤和分類：

```json
{
  "api_token": "your-api-token-here",
  "title": "Well-Categorized Post",
  "content": "Content here...",
  "tags": [1, 2, 3, 4, 5],
  "categories": [10, 20]
}
```

### 多站點支援

如果啟用了多站點模式，將文章指派給特定站點：

```json
{
  "api_token": "your-api-token-here",
  "title": "Cross-Site Post",
  "content": "Content here...",
  "website_id": [1, 2, 3]
}
```

## 錯誤回應

### 403 - API 已停用

```json
{
  "status": 403,
  "message": "API access is disabled"
}
```

### 401 - 無效 Token

```json
{
  "status": "fail",
  "message": "Invalid token"
}
```

### 422 - 驗證失敗

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

### 500 - 伺服器錯誤

```json
{
  "code": 500,
  "status": "fail",
  "message": "Server Error: Database connection failed"
}
```

## 最佳實務

1. **使用分頁**：始終使用 `page_size` 來限制結果以獲得更好的效能
2. **有效篩選**：使用 `tags`、`keywords` 和其他篩選器來精確獲取您需要的內容
3. **處理錯誤**：在處理資料之前始終檢查 `status` 欄位
4. **最佳化圖片**：在上傳前壓縮圖片以減少頻寬
5. **快取結果**：在您這邊快取文章列表以減少 API 呼叫
6. **使用 Slug**：優先使用 slug 而不是 ID 以獲得更好的 URL 可讀性

## 程式碼範例

查看[範例](../examples.md)頁面以獲得各種程式語言的完整程式碼實作。

## 相關端點

- [標籤 API](./tags.md) - 管理文章標籤和分類
- [頁面 API](./pages.md) - 管理靜態頁面
- [選單 API](./menus.md) - 組織導覽

## 疑難排解

有關常見問題和解決方案，請參閱[疑難排解指南](../troubleshooting.md)。
