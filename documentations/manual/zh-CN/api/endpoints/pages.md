# 页面 API

页面 API 让您能够管理 WNCMS 中的静态网站页面。

:::warning 开发中
页面 API 端点目前为占位符，将在未来版本中完整实作。
:::

## 端点总览

| 方法 | 端点                  | 说明         | 状态   |
| ---- | --------------------- | ------------ | ------ |
| POST | `/api/v1/pages`       | 列出页面     | 占位符 |
| POST | `/api/v1/pages/store` | 建立页面     | 占位符 |
| POST | `/api/v1/pages/{id}`  | 取得单一页面 | 占位符 |

## 列出页面

### 端点

```
POST /api/v1/pages
```

### 目前回应

```json
{
  "status": "success",
  "message": "Successfully fetched page index"
}
```

## 建立页面

### 端点

```
POST /api/v1/pages/store
```

### 目前回应

```json
{
  "status": "success",
  "message": "Successfully fetched page store"
}
```

## 取得单一页面

### 端点

```
POST /api/v1/pages/{id}
```

### 目前回应

```json
{
  "status": "success",
  "message": "Successfully fetched page show"
}
```

## 未来实作

页面 API 将支援类似于文章 API 的功能：

- 完整的 CRUD 操作（建立、读取、更新、删除）
- 页面范本管理
- SEO 中继资料
- 父子页面关系
- 页面可见性控制
- 自定义栏位支援

## 替代方案：使用文章 API

在页面 API 完整实作之前，您可以使用[文章 API](./posts.md)搭配自定义文章类型来管理静态页面。

## 相关端点

- [文章 API](./posts.md) - 全功能内容管理
- [选单 API](./menus.md) - 在导览选单中连结页面

## 疑难排解

有关目前 API 问题，请参阅[疑难排解指南](../troubleshooting.md)。
