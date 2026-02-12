# 網站 API

網站 API 允許您管理網站模型與網站網域。

## 端點總覽

| 方法     | 端點                             | 說明                         |
| -------- | -------------------------------- | ---------------------------- |
| GET/POST | `/api/v1/websites`              | 列出 API 使用者可存取網站     |
| POST     | `/api/v1/websites/store`        | 建立網站（僅管理員）          |
| GET/POST | `/api/v1/websites/{id}`         | 取得單一網站                  |
| POST     | `/api/v1/websites/update/{id}`  | 更新網站                      |
| POST     | `/api/v1/websites/delete/{id}`  | 刪除網站（僅管理員）          |
| POST     | `/api/v1/websites/add-domain`   | 新增網站網域別名              |
| POST     | `/api/v1/websites/remove-domain`| 從網站移除網域                |

## 身份驗證

所有端點都需要 `api_token`。

## 功能開關

網站 API 需要同時啟用兩層開關：

- 模型層：`enable_api_website`
- 端點層：對應動作鍵值（例如 `wncms_api_website_update`）

## 列出網站

### 端點

```
GET|POST /api/v1/websites
```

### 請求參數

| 參數         | 類型   | 必需 | 說明                               |
| ------------ | ------ | ---- | ---------------------------------- |
| `api_token`  | string | 是   | 使用者 API token                   |
| `keyword`    | string | 否   | 以 `domain` / `site_name` 搜尋     |
| `page_size`  | int    | 否   | 每頁數量（預設 20，最大 100）      |

## 建立網站

### 端點

```
POST /api/v1/websites/store
```

### 請求參數

| 參數         | 類型   | 必需 | 說明                 |
| ------------ | ------ | ---- | -------------------- |
| `api_token`  | string | 是   | 管理員 API token     |
| `site_name`  | string | 是   | 網站名稱             |
| `domain`     | string | 是   | 主網域               |
| `theme`      | string | 否   | 主題 key             |
| `remark`     | string | 否   | 備註                 |

## 取得單一網站

### 端點

```
GET|POST /api/v1/websites/{id}
```

## 更新網站

### 端點

```
POST /api/v1/websites/update/{id}
```

### 請求參數

| 參數                     | 類型    | 必需 | 說明                             |
| ------------------------ | ------- | ---- | -------------------------------- |
| `api_token`              | string  | 是   | 使用者 API token                 |
| `user_id`                | integer | 否   | 網站擁有者使用者 ID              |
| `domain`                 | string  | 否   | 主網域                           |
| `site_name`              | string/object | 否 | 網站名稱（支援翻譯對照）         |
| `site_logo`              | string  | 否   | 網站 Logo 路徑/URL               |
| `site_favicon`           | string  | 否   | 網站 Favicon 路徑/URL            |
| `site_slogan`            | string/object | 否 | 網站標語（支援翻譯對照）         |
| `site_seo_keywords`      | string/object | 否 | SEO 關鍵字（支援翻譯對照）       |
| `site_seo_description`   | string/object | 否 | SEO 描述（支援翻譯對照）         |
| `theme`                  | string  | 否   | 主題 key                         |
| `homepage`               | string  | 否   | 首頁識別                         |
| `remark`                 | string  | 否   | 備註                             |
| `meta_verification`      | string  | 否   | Meta 驗證碼                      |
| `head_code`              | string  | 否   | 插入 `<head>` 的 HTML            |
| `body_code`              | string  | 否   | 插入 `</body>` 前的 HTML         |
| `analytics`              | string  | 否   | 分析腳本/設定                    |
| `license`                | string  | 否   | License 值                       |
| `enabled_page_cache`     | boolean | 否   | 啟用整頁快取                     |
| `enabled_data_cache`     | boolean | 否   | 啟用資料快取                     |

## 刪除網站

### 端點

```
POST /api/v1/websites/delete/{id}
```

僅管理員可用。

## 新增網域別名

為網站新增網域別名（例如：`demo001.wndhcms.com`）。

### 端點

```
POST /api/v1/websites/add-domain
```

### 功能開關

此端點可透過 `enable_api_website_add_domain` 設定停用。

### 請求參數

| 參數         | 類型    | 必需 | 說明            |
| ------------ | ------- | ---- | --------------- |
| `api_token`  | string  | 是   | 使用者 API token |
| `website_id` | integer | 是   | 目標網站 ID     |
| `domain`     | string  | 是   | 要新增的網域別名 |

### 回應範例 - 新增成功

```json
{
  "code": 200,
  "status": "success",
  "message": "Domain alias created",
  "data": {
    "website_id": 1,
    "domain": "demo001.wndhcms.com",
    "domain_alias_id": 8,
    "already_exists": false,
    "is_primary_domain": false
  },
  "extra": []
}
```

## 移除網域

### 端點

```
POST /api/v1/websites/remove-domain
```

### 請求參數

| 參數         | 類型    | 必需 | 說明             |
| ------------ | ------- | ---- | ---------------- |
| `api_token`  | string  | 是   | 使用者 API token |
| `website_id` | integer | 是   | 目標網站 ID      |
| `domain`     | string  | 是   | 要移除的網域     |

### 回應範例 - 移除別名成功

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully Deleted",
  "data": {
    "website_id": 1,
    "removed_domain": "demo001.wndhcms.com",
    "new_primary_domain": "main-domain.com"
  },
  "extra": []
}
```

### 回應範例 - 不可移除最後一個網域

```json
{
  "code": 422,
  "status": "fail",
  "message": "Cannot remove the last domain of a website",
  "data": [],
  "extra": []
}
```

## 行為說明

- `domain` 輸入會標準化為主機名稱。
- 若該網域已被其他網站（主網域或別名）使用，將拒絕新增。
- 非管理員只能為自己可存取的網站新增網域。
- 移除主網域時，系統會提升一個別名成為新主網域。
- 系統會阻止移除網站最後一個網域。
- 網站/網域更新後會清理 `websites` 快取標籤。

## API 設定鍵值

網站 API 動作會透過模型 `$apiRoutes` 映射到系統設定 -> API 開關：

- `wncms_api_website_index`
- `wncms_api_website_show`
- `wncms_api_website_store`
- `wncms_api_website_update`
- `wncms_api_website_delete`
- `wncms_api_website_add_domain`
- `wncms_api_website_remove_domain`
