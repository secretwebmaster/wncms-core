# 標籤 API

標籤 API 讓您能夠建立和管理標籤與分類，以組織 WNCMS 中的內容。

## 端點總覽

| 方法 | 端點                 | 說明                 |
| ---- | -------------------- | -------------------- |
| POST | `/api/v1/tags`       | 列出標籤並支援本地化 |
| POST | `/api/v1/tags/exist` | 檢查標籤 ID 是否存在 |
| POST | `/api/v1/tags/store` | 建立或更新標籤       |

## 列出標籤

按類型檢索標籤，並支援可選的本地化。

### 端點

```
POST /api/v1/tags
```

### 身份驗證

必需：可透過設定配置

### 功能開關

- `wncms_api_tag_index`

### 請求參數

| 參數        | 類型   | 必需 | 預設值   | 說明                                            |
| ----------- | ------ | ---- | -------- | ----------------------------------------------- |
| `api_token` | string | 是\* | -        | 使用者 API token                                |
| `type`      | string | 是   | -        | 標籤類型（例如「post_category」、「post_tag」） |
| `locale`    | string | 否   | 系統預設 | 翻譯的語言代碼                                  |

\*如果啟用了身份驗證則為必需

### 請求範例

```bash
curl -X POST "https://your-domain.com/api/v1/tags" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "type": "post_category",
    "locale": "zh-TW"
  }'
```

### 回應範例

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

### 功能

- **階層結構**：返回帶有巢狀子項目的標籤
- **本地化**：根據 `locale` 參數翻譯名稱
- **排序**：結果按 `sort` 欄位排序（降序）
- **多層級**：支援巢狀子項目（子項目的子項目）

## 檢查標籤存在

驗證特定標籤 ID 是否存在於資料庫中。

### 端點

```
POST /api/v1/tags/exist
```

### 身份驗證

必需：可透過設定配置

### 功能開關

- `wncms_api_tag_exist`

### 請求參數

| 參數        | 類型   | 必需 | 說明                 |
| ----------- | ------ | ---- | -------------------- |
| `api_token` | string | 是\* | 使用者 API token     |
| `tagIds`    | array  | 是   | 要檢查的標籤 ID 陣列 |

\*如果啟用了身份驗證則為必需

### 請求範例

```bash
curl -X POST "https://your-domain.com/api/v1/tags/exist" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "tagIds": [1, 2, 5, 99, 100]
  }'
```

### 回應範例

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

回應僅返回存在的 ID。在此範例中，標籤 99 和 100 不存在。

### 使用情境

- **驗證**：在將標籤指派給文章之前檢查標籤是否存在
- **清理**：識別匯入資料中缺少的標籤
- **確認**：在建立關係之前確認標籤可用

## 建立標籤

建立新標籤或在找到重複項目時更新現有標籤。

### 端點

```
POST /api/v1/tags/store
```

### 身份驗證

必需：可透過設定配置

### 功能開關

主要設定鍵：`wncms_api_tag_store`。

舊版相容：若舊環境仍啟用 `enable_api_tag_store`，此端點仍可用。

### 請求參數

| 參數                     | 類型                 | 必需 | 預設值          | 說明                           |
| ------------------------ | -------------------- | ---- | --------------- | ------------------------------ |
| `api_token`              | string               | 是   | -               | 使用者 API token 用於身份驗證  |
| `name`                   | string               | 是   | -               | 標籤顯示名稱（最多：255 字元） |
| `slug`                   | string               | 否   | 自動產生        | URL 友善識別符                 |
| `type`                   | string               | 否   | `post_category` | 標籤類型（最多：50 字元）      |
| `parent_id`              | integer              | 否   | null            | 父標籤 ID，用於階層標籤        |
| `description`            | string               | 否   | -               | 標籤描述                       |
| `icon`                   | string               | 否   | -               | 圖示識別符或類別               |
| `sort`                   | integer              | 否   | 0               | 排序順序（越高越早）           |
| `website_id`             | integer/array/string | 否   | -               | 多站點的網站 ID                |
| `update_when_duplicated` | boolean              | 否   | false           | 找到重複標籤時更新現有標籤     |

多站點綁定說明：

- `website_id` 支援整數、陣列或逗號分隔字串。
- 綁定前會透過共用多站點 helper 標準化網站 ID。
- 標準化過程中會忽略無效或不存在的網站 ID。

### 請求範例

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

## 相關端點

- [文章 API](./posts.md) - 將標籤指派給文章
- [頁面 API](./pages.md) - 分類頁面

## 疑難排解

有關常見問題和解決方案，請參閱[疑難排解指南](../troubleshooting.md)。
