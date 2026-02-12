# 网站 API

网站 API 允许您管理网站模型与网站域名。

## 端点总览

| 方法     | 端点                             | 说明                         |
| -------- | -------------------------------- | ---------------------------- |
| GET/POST | `/api/v1/websites`              | 列出 API 用户可访问的网站     |
| POST     | `/api/v1/websites/store`        | 建立网站（仅管理员）          |
| GET/POST | `/api/v1/websites/{id}`         | 取得单一网站                  |
| POST     | `/api/v1/websites/update/{id}`  | 更新网站                      |
| POST     | `/api/v1/websites/delete/{id}`  | 删除网站（仅管理员）          |
| POST     | `/api/v1/websites/add-domain`   | 新增网站域名别名              |
| POST     | `/api/v1/websites/remove-domain`| 从网站移除域名                |

## 身份验证

所有端点都需要 `api_token`。

## 功能开关

网站 API 需要同时启用两层开关：

- 模型层：`enable_api_website`
- 端点层：对应动作键值（例如 `wncms_api_website_update`）

## 列出网站

### 端点

```
GET|POST /api/v1/websites
```

### 请求参数

| 参数         | 类型   | 必需 | 说明                               |
| ------------ | ------ | ---- | ---------------------------------- |
| `api_token`  | string | 是   | 用户 API token                     |
| `keyword`    | string | 否   | 以 `domain` / `site_name` 搜索     |
| `page_size`  | int    | 否   | 每页数量（预设 20，最大 100）      |

## 建立网站

### 端点

```
POST /api/v1/websites/store
```

### 请求参数

| 参数         | 类型   | 必需 | 说明                 |
| ------------ | ------ | ---- | -------------------- |
| `api_token`  | string | 是   | 管理员 API token     |
| `site_name`  | string | 是   | 网站名称             |
| `domain`     | string | 是   | 主域名               |
| `theme`      | string | 否   | 主题 key             |
| `remark`     | string | 否   | 备注                 |

## 取得单一网站

### 端点

```
GET|POST /api/v1/websites/{id}
```

## 更新网站

### 端点

```
POST /api/v1/websites/update/{id}
```

### 请求参数

| 参数                     | 类型    | 必需 | 说明                             |
| ------------------------ | ------- | ---- | -------------------------------- |
| `api_token`              | string  | 是   | 用户 API token                   |
| `user_id`                | integer | 否   | 网站拥有者用户 ID                |
| `domain`                 | string  | 否   | 主域名                           |
| `site_name`              | string/object | 否 | 网站名称（支持翻译映射）         |
| `site_logo`              | string  | 否   | 网站 Logo 路径/URL               |
| `site_favicon`           | string  | 否   | 网站 Favicon 路径/URL            |
| `site_slogan`            | string/object | 否 | 网站标语（支持翻译映射）         |
| `site_seo_keywords`      | string/object | 否 | SEO 关键词（支持翻译映射）       |
| `site_seo_description`   | string/object | 否 | SEO 描述（支持翻译映射）         |
| `theme`                  | string  | 否   | 主题 key                         |
| `homepage`               | string  | 否   | 首页标识                         |
| `remark`                 | string  | 否   | 备注                             |
| `meta_verification`      | string  | 否   | Meta 验证码                      |
| `head_code`              | string  | 否   | 插入 `<head>` 的 HTML            |
| `body_code`              | string  | 否   | 插入 `</body>` 前的 HTML         |
| `analytics`              | string  | 否   | 统计脚本/配置                    |
| `license`                | string  | 否   | License 值                       |
| `enabled_page_cache`     | boolean | 否   | 启用整页缓存                     |
| `enabled_data_cache`     | boolean | 否   | 启用资料缓存                     |

## 删除网站

### 端点

```
POST /api/v1/websites/delete/{id}
```

仅管理员可用。

## 新增域名别名

为网站新增域名别名（例如：`demo001.wndhcms.com`）。

### 端点

```
POST /api/v1/websites/add-domain
```

### 功能开关

此端点可通过 `enable_api_website_add_domain` 设定停用。

### 请求参数

| 参数         | 类型    | 必需 | 说明            |
| ------------ | ------- | ---- | --------------- |
| `api_token`  | string  | 是   | 用户 API token  |
| `website_id` | integer | 是   | 目标网站 ID     |
| `domain`     | string  | 是   | 要新增的域名别名 |

### 回应示例 - 新增成功

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

## 移除域名

### 端点

```
POST /api/v1/websites/remove-domain
```

### 请求参数

| 参数         | 类型    | 必需 | 说明             |
| ------------ | ------- | ---- | ---------------- |
| `api_token`  | string  | 是   | 用户 API token   |
| `website_id` | integer | 是   | 目标网站 ID      |
| `domain`     | string  | 是   | 要移除的域名     |

### 回应示例 - 移除别名成功

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

### 回应示例 - 不可移除最后一个域名

```json
{
  "code": 422,
  "status": "fail",
  "message": "Cannot remove the last domain of a website",
  "data": [],
  "extra": []
}
```

## 行为说明

- `domain` 输入会标准化为主机名。
- 若该域名已被其他网站（主域名或别名）使用，将拒绝新增。
- 非管理员只能为自己可访问的网站新增域名。
- 移除主域名时，系统会提升一个别名为新主域名。
- 系统会阻止移除网站最后一个域名。
- 网站/域名更新后会清理 `websites` 缓存标签。

## API 设定键值

网站 API 动作会透过模型 `$apiRoutes` 映射到系统设定 -> API 开关：

- `wncms_api_website_index`
- `wncms_api_website_show`
- `wncms_api_website_store`
- `wncms_api_website_update`
- `wncms_api_website_delete`
- `wncms_api_website_add_domain`
- `wncms_api_website_remove_domain`
