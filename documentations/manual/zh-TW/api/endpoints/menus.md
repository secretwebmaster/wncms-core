# 選單 API

選單 API 讓您能夠檢索和同步 WNCMS 中的導覽選單結構。

## 端點總覽

| 方法     | 端點                  | 說明                    |
| -------- | --------------------- | ----------------------- |
| GET/POST | `/api/v1/menus`       | 列出所有選單（佔位符）  |
| POST     | `/api/v1/menus/store` | 建立/更新選單（佔位符） |
| POST     | `/api/v1/menus/sync`  | 同步網站的選單項目      |
| GET/POST | `/api/v1/menus/{id}`  | 透過 ID 取得單一選單    |

## 列出選單

:::warning 開發中
此端點目前為佔位符，返回空集合。
:::

### 端點

```
GET|POST /api/v1/menus
```

### 回應範例

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched menus",
  "data": []
}
```

## 同步選單

同步特定網站的選單項目。這是僅限管理員的操作，允許批次更新選單結構。

### 端點

```
POST /api/v1/menus/sync
```

### 身份驗證

- **必需**：是
- **權限**：需要管理員角色
- **方法**：透過 `api_token` 進行簡易身份驗證

### 請求參數

| 參數         | 類型    | 必需 | 說明                   |
| ------------ | ------- | ---- | ---------------------- |
| `api_token`  | string  | 是   | 管理員使用者 API token |
| `website_id` | integer | 是\* | 網站 ID                |
| `domain`     | string  | 是\* | 網站網域               |
| `menu_items` | array   | 是   | 選單項目物件陣列       |

\*必須提供 `website_id` 或 `domain`

### 選單項目物件

`menu_items` 陣列中的每個選單項目應該有：

| 欄位        | 類型    | 必需 | 說明                                |
| ----------- | ------- | ---- | ----------------------------------- |
| `order`     | integer | 是   | 顯示順序/位置                       |
| `name`      | string  | 是   | 選單項目顯示名稱                    |
| `type`      | string  | 是   | 選單類型（例如「page」、「link」）  |
| `page_id`   | integer | 否   | 頁面 ID（如果類型為「page」則必需） |
| `url`       | string  | 否   | 自定義 URL（用於連結類型）          |
| `target`    | string  | 否   | 連結目標（\_self、\_blank）         |
| `icon`      | string  | 否   | 圖示識別符                          |
| `parent_id` | integer | 否   | 父選單項目 ID，用於巢狀選單         |

### 請求範例

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
      }
    ]
  }'
```

## 相關端點

- [頁面 API](./pages.md) - 管理選單中引用的頁面
- [文章 API](./posts.md) - 為選單目的地建立內容

## 疑難排解

有關常見問題和解決方案，請參閱[疑難排解指南](../troubleshooting.md)。
