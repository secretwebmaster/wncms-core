# 选单 API

选单 API 让您能够检索和同步 WNCMS 中的导览选单结构。

## 端点总览

| 方法     | 端点                  | 说明                    |
| -------- | --------------------- | ----------------------- |
| GET/POST | `/api/v1/menus`       | 列出所有选单（占位符）  |
| POST     | `/api/v1/menus/store` | 建立/更新选单（占位符） |
| POST     | `/api/v1/menus/sync`  | 同步网站的选单项目      |
| GET/POST | `/api/v1/menus/{id}`  | 透过 ID 取得单一选单    |

## 列出选单

:::warning 开发中
此端点目前为占位符，返回空集合。
:::

### 端点

```
GET|POST /api/v1/menus
```

### 回应范例

```json
{
  "code": 200,
  "status": "success",
  "message": "Successfully fetched menus",
  "data": []
}
```

## 同步选单

同步特定网站的选单项目。这是仅限管理员的操作，允许批次更新选单结构。

### 端点

```
POST /api/v1/menus/sync
```

### 身份验证

- **必需**：是
- **权限**：需要管理员角色
- **方法**：透过 `api_token` 进行简易身份验证

### 请求参数

| 参数         | 类型    | 必需 | 说明                   |
| ------------ | ------- | ---- | ---------------------- |
| `api_token`  | string  | 是   | 管理员使用者 API token |
| `website_id` | integer | 是\* | 网站 ID                |
| `domain`     | string  | 是\* | 网站网域               |
| `menu_items` | array   | 是   | 选单项目物件阵列       |

\*必须提供 `website_id` 或 `domain`

### 选单项目物件

`menu_items` 阵列中的每个选单项目应该有：

| 栏位        | 类型    | 必需 | 说明                                |
| ----------- | ------- | ---- | ----------------------------------- |
| `order`     | integer | 是   | 显示顺序/位置                       |
| `name`      | string  | 是   | 选单项目显示名称                    |
| `type`      | string  | 是   | 选单类型（例如「page」、「link」）  |
| `page_id`   | integer | 否   | 页面 ID（如果类型为「page」则必需） |
| `url`       | string  | 否   | 自定义 URL（用于连结类型）          |
| `target`    | string  | 否   | 连结目标（\_self、\_blank）         |
| `icon`      | string  | 否   | 图示识别符                          |
| `parent_id` | integer | 否   | 父选单项目 ID，用于巢状选单         |

### 请求范例

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

## 相关端点

- [页面 API](./pages.md) - 管理选单中引用的页面
- [文章 API](./posts.md) - 为选单目的地建立内容

## 疑难排解

有关常见问题和解决方案，请参阅[疑难排解指南](../troubleshooting.md)。
