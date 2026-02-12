# 标签 API

标签 API 让您能够建立和管理标签与分类，以组织 WNCMS 中的内容。

## 端点总览

| 方法 | 端点                 | 说明                 |
| ---- | -------------------- | -------------------- |
| POST | `/api/v1/tags`       | 列出标签并支援本地化 |
| POST | `/api/v1/tags/exist` | 检查标签 ID 是否存在 |
| POST | `/api/v1/tags/store` | 建立或更新标签       |

## 列出标签

按类型检索标签，并支援可选的本地化。

### 端点

```
POST /api/v1/tags
```

### 身份验证

必需：可透过设定配置

### 请求参数

| 参数        | 类型   | 必需 | 预设值   | 说明                                            |
| ----------- | ------ | ---- | -------- | ----------------------------------------------- |
| `api_token` | string | 是\* | -        | 使用者 API token                                |
| `type`      | string | 是   | -        | 标签类型（例如「post_category」、「post_tag」） |
| `locale`    | string | 否   | 系统预设 | 翻译的语言代码                                  |

\*如果启用了身份验证则为必需

### 请求范例

```bash
curl -X POST "https://your-domain.com/api/v1/tags" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "type": "post_category",
    "locale": "zh-TW"
  }'
```

### 回应范例

```json
{
  "code": 200,
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "技术",
      "slug": "technology",
      "type": "post_category",
      "parent_id": null,
      "description": "技术相关文章",
      "icon": null,
      "sort": 10,
      "children": [
        {
          "id": 2,
          "name": "程式设计",
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

- **阶层结构**：返回带有巢状子项目的标签
- **本地化**：根据 `locale` 参数翻译名称
- **排序**：结果按 `sort` 栏位排序（降序）
- **多层级**：支援巢状子项目（子项目的子项目）

## 检查标签存在

验证特定标签 ID 是否存在于资料库中。

### 端点

```
POST /api/v1/tags/exist
```

### 身份验证

必需：可透过设定配置

### 请求参数

| 参数        | 类型   | 必需 | 说明                 |
| ----------- | ------ | ---- | -------------------- |
| `api_token` | string | 是\* | 使用者 API token     |
| `tagIds`    | array  | 是   | 要检查的标签 ID 阵列 |

\*如果启用了身份验证则为必需

### 请求范例

```bash
curl -X POST "https://your-domain.com/api/v1/tags/exist" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "tagIds": [1, 2, 5, 99, 100]
  }'
```

### 回应范例

```json
{
  "status": "success",
  "message": "Successfully fetched data",
  "ids": [1, 2, 5]
}
```

回应仅返回存在的 ID。在此范例中，标签 99 和 100 不存在。

### 使用情境

- **验证**：在将标签指派给文章之前检查标签是否存在
- **清理**：识别汇入资料中缺少的标签
- **确认**：在建立关系之前确认标签可用

## 建立标签

建立新标签或在找到重复项目时更新现有标签。

### 端点

```
POST /api/v1/tags/store
```

### 身份验证

必需：是（透过 `api_token`）

### 功能开关

此端点可以透过 `enable_api_tag_store` 设定停用。

### 请求参数

| 参数                     | 类型                 | 必需 | 预设值          | 说明                           |
| ------------------------ | -------------------- | ---- | --------------- | ------------------------------ |
| `api_token`              | string               | 是   | -               | 使用者 API token 用于身份验证  |
| `name`                   | string               | 是   | -               | 标签显示名称（最多：255 字元） |
| `slug`                   | string               | 否   | 自动产生        | URL 友善识别符                 |
| `type`                   | string               | 否   | `post_category` | 标签类型（最多：50 字元）      |
| `parent_id`              | integer              | 否   | null            | 父标签 ID，用于阶层标签        |
| `description`            | string               | 否   | -               | 标签描述                       |
| `icon`                   | string               | 否   | -               | 图示识别符或类别               |
| `sort`                   | integer              | 否   | 0               | 排序顺序（越高越早）           |
| `website_id`             | integer/array/string | 否   | -               | 多站点的网站 ID                |
| `update_when_duplicated` | boolean              | 否   | false           | 找到重复标签时更新现有标签     |

### 请求范例

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

## 相关端点

- [文章 API](./posts.md) - 将标签指派给文章
- [页面 API](./pages.md) - 分类页面

## 疑难排解

有关常见问题和解决方案，请参阅[疑难排解指南](../troubleshooting.md)。
