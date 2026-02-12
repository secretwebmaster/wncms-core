# 頁面 API

頁面 API 讓您能夠管理 WNCMS 中的靜態網站頁面。

:::warning 開發中
頁面 API 端點目前為佔位符，將在未來版本中完整實作。
:::

## 端點總覽

| 方法 | 端點                  | 說明         | 狀態   |
| ---- | --------------------- | ------------ | ------ |
| POST | `/api/v1/pages`       | 列出頁面     | 佔位符 |
| POST | `/api/v1/pages/store` | 建立頁面     | 佔位符 |
| POST | `/api/v1/pages/{id}`  | 取得單一頁面 | 佔位符 |

## 列出頁面

### 端點

```
POST /api/v1/pages
```

### 目前回應

```json
{
  "status": "success",
  "message": "Successfully fetched page index"
}
```

## 建立頁面

### 端點

```
POST /api/v1/pages/store
```

### 目前回應

```json
{
  "status": "success",
  "message": "Successfully fetched page store"
}
```

## 取得單一頁面

### 端點

```
POST /api/v1/pages/{id}
```

### 目前回應

```json
{
  "status": "success",
  "message": "Successfully fetched page show"
}
```

## 未來實作

頁面 API 將支援類似於文章 API 的功能：

- 完整的 CRUD 操作（建立、讀取、更新、刪除）
- 頁面範本管理
- SEO 中繼資料
- 父子頁面關係
- 頁面可見性控制
- 自定義欄位支援

## 替代方案：使用文章 API

在頁面 API 完整實作之前，您可以使用[文章 API](./posts.md)搭配自定義文章類型來管理靜態頁面。

## 相關端點

- [文章 API](./posts.md) - 全功能內容管理
- [選單 API](./menus.md) - 在導覽選單中連結頁面

## 疑難排解

有關目前 API 問題，請參閱[疑難排解指南](../troubleshooting.md)。
