# WNCMS Theme Config 規格

本文件定義每個 WNCMS theme 中 `config.php` 的完整結構與使用方式。

theme config 包含：

1. **Theme metadata** (`info`)
2. **Theme option 群組** (`option_tabs`)
3. **預設值** (`default`)

## 1. info — Theme Metadata

### 結構

| Key         | Type   | Required | Description                   |
| ----------- | ------ | -------- | ----------------------------- |
| label       | string | yes      | Theme 顯示名稱                |
| name        | string | yes      | Theme ID，與資料夾名稱相同    |
| author      | string | yes      | 建立者名稱                    |
| description | string | yes      | 簡短描述                      |
| version     | string | yes      | Semantic version (例如 1.0.0) |
| created_at  | string | yes      | YYYY-MM-DD                    |
| updated_at  | string | yes      | 最後更新日期                  |

### 範例

```php
'info' => [
    'label'       => 'WNCMS Starter Theme',
    'name'        => 'starter',
    'author'      => 'Winnie',
    'description' => 'Official WNCMS base theme',
    'version'     => '1.0.0',
    'created_at'  => '2025-01-01',
    'updated_at'  => '2025-12-01',
],
```

## 2. option_tabs — Option 群組

`option_tabs` 是多分頁佈局，每個分頁包含多個輸入選項。

範例：

```php
'option_tabs' => [
    'general' => [
        [
            'label' => 'General Settings',
            'type'  => 'heading',
        ],
        [
            'label' => 'Subtitle',
            'name'  => 'subtitle',
            'type'  => 'text',
        ],
    ],
],
```

# Field Types 參考

每種 field type 支援不同的 keys。
以下是**所有支援的 types** 與表格和範例。

# Basic Text & Number

## text

| Key         | Type   | Required | Example           |
| ----------- | ------ | -------- | ----------------- |
| label       | string | yes      | "Site Name"       |
| name        | string | yes      | "site_name"       |
| type        | string | yes      | "text"            |
| default     | string | no       | "My Website"      |
| description | string | no       | "Shown in header" |

範例：

```php
[
    'label' => 'Site Name',
    'name'  => 'site_name',
    'type'  => 'text',
],
```

## number

| Key     | Type   | Required | Example          |
| ------- | ------ | -------- | ---------------- |
| label   | string | yes      | "Items per page" |
| name    | string | yes      | "per_page"       |
| type    | string | yes      | "number"         |
| default | int    | no       | 12               |

範例：

```php
[
    'label' => 'Items per page',
    'name'  => 'per_page',
    'type'  => 'number',
],
```

# Media / Image

## image

| Key          | Type       | Required | Example              |
| ------------ | ---------- | -------- | -------------------- |
| label        | string     | yes      | "Top Banner"         |
| name         | string     | yes      | "banner"             |
| type         | string     | yes      | "image"              |
| width        | int/string | no       | 800 / "50%" / "auto" |
| height       | int/string | no       | 300 / "auto"         |
| aspect_ratio | string     | no       | "16/9"               |

行為：

- 若僅設定 width → height 透過 aspect ratio 自動計算
- 若僅設定 height → width 自動計算
- 若兩者皆未設定 → 預設 width 400px，height auto

範例：

```php
[
    'label' => 'Homepage Banner',
    'name'  => 'home_banner',
    'type'  => 'image',
    'width' => 800,
    'aspect_ratio' => '16/9',
],
```

## display_image (Static Preview Image)

| Key          | Type       | Required | Example                         |
| ------------ | ---------- | -------- | ------------------------------- |
| label        | string     | yes      | "Preview"                       |
| type         | string     | yes      | "display_image"                 |
| path         | string     | yes      | "theme/starter/images/demo.png" |
| width        | int/string | no       | 300 / "100%"                    |
| height       | int/string | no       | 120                             |
| aspect_ratio | string     | no       | "4/3"                           |
| col          | string     | no       | "col-12 col-md-4"               |

行為：

- **不**儲存資料
- 僅用於 admin 預覽圖片
- 若 width/height 未設定 → width=100%，height=auto
- `col` 支援 Bootstrap grid

範例：

```php
[
    'label' => 'Ad Slot Preview',
    'type'  => 'display_image',
    'path'  => 'starter/images/hero-demo.png',
    'col'   => 'col-12 col-md-4',
],
```

# Gallery (Multiple Images)

## gallery

| Key             | Type       | Required | Example        |
| --------------- | ---------- | -------- | -------------- |
| label           | string     | yes      | "Gallery"      |
| name            | string     | yes      | "hero_gallery" |
| type            | string     | yes      | "gallery"      |
| desktop_columns | int        | no       | 4              |
| mobile_columns  | int        | no       | 2              |
| width           | int/string | no       | 300 / "50%"    |
| height          | int/string | no       | "auto"         |
| aspect_ratio    | string     | no       | "1/1"          |

儲存值範例：

```json
[
  { "image": "/uploads/a.jpg", "text": "", "url": "" },
  { "image": "/uploads/b.jpg", "text": "", "url": "" }
]
```

Config 範例：

```php
[
    'label' => 'Homepage Gallery',
    'name'  => 'hero_gallery',
    'type'  => 'gallery',
    'desktop_columns' => 4,
    'mobile_columns'  => 2,
    'aspect_ratio'    => '1/1',
],
```

# Select / Boolean

## select

| Key      | Type         | Required | Example                         |
| -------- | ------------ | -------- | ------------------------------- |
| label    | string       | yes      | "Category"                      |
| name     | string       | yes      | "category"                      |
| type     | string       | yes      | "select"                        |
| options  | array/string | yes      | ["A","B"] or "posts" or "menus" |
| tag_type | string       | no       | "post_category"                 |

範例：

```php
[
    'label'   => 'Category',
    'name'    => 'category',
    'type'    => 'select',
    'options' => 'posts',
],
```

## boolean

| Key     | Type   | Required | Example        |
| ------- | ------ | -------- | -------------- |
| label   | string | yes      | "Show Banner?" |
| name    | string | yes      | "show_banner"  |
| type    | string | yes      | "boolean"      |
| default | int    | no       | 1              |

# Textarea

## textarea

| Key     | Type   | Required | Example    |
| ------- | ------ | -------- | ---------- |
| label   | string | yes      | "About"    |
| name    | string | yes      | "about"    |
| type    | string | yes      | "textarea" |
| default | string | no       | ""         |

# Color

## color

| Key         | Type   | Required | Example    |
| ----------- | ------ | -------- | ---------- |
| label       | string | yes      | "Brand"    |
| name        | string | yes      | "brand"    |
| type        | string | yes      | "color"    |
| required    | bool   | no       | true       |
| placeholder | string | no       | "#FFA218"  |
| default     | string | no       | "#FFA218"  |

行為：

- 顏色輸入現在支援 `required` 與 placeholder。
- 為空時顯示預設值 `#FFA218`。

# Repeater

## repeater

| Key       | Type   | Required | Example       |
| --------- | ------ | -------- | ------------- |
| label     | string | yes      | "Slides"      |
| name      | string | yes      | "hero_slides" |
| type      | string | yes      | "repeater"    |
| fields    | array  | yes      | 欄位定義      |
| add_label | string | no       | "Add Slide"   |

`fields` 支援簡單輸入定義：

```php
[
    'label' => 'Hero Slides',
    'name' => 'hero_slides',
    'type' => 'repeater',
    'fields' => [
        ['name' => 'text', 'type' => 'text', 'label' => 'Text'],
        ['name' => 'number', 'type' => 'number', 'label' => 'Number'],
    ],
]
```

# Editor (TinyMCE)

## editor

| Key   | Type   | Required | Example   |
| ----- | ------ | -------- | --------- |
| label | string | yes      | "Content" |
| name  | string | yes      | "content" |
| type  | string | yes      | "editor"  |

# Structure Layout

## heading

| Key         | Type   | Required | Example               |
| ----------- | ------ | -------- | --------------------- |
| label       | string | yes      | "General Settings"    |
| type        | string | yes      | "heading"             |
| description | string | no       | "Section description" |

## sub_heading

| Key   | Type   | Required | Example           |
| ----- | ------ | -------- | ----------------- |
| label | string | yes      | "Homepage Banner" |
| type  | string | yes      | "sub_heading"     |

## inline

| Key       | Type   | Required | Example         |
| --------- | ------ | -------- | --------------- |
| label     | string | yes      | "Three Columns" |
| name      | string | yes      | "stats"         |
| type      | string | yes      | "inline"        |
| sub_items | array  | yes      | multiple inputs |
| repeat    | int    | no       | 3               |

## accordion

| Key      | Type   | Required | Example           |
| -------- | ------ | -------- | ----------------- |
| label    | string | yes      | "FAQ"             |
| name     | string | yes      | "faq_items"       |
| type     | string | yes      | "accordion"       |
| content  | array  | yes      | fields inside FAQ |
| sortable | bool   | no       | true              |
| repeat   | int    | no       | 3                 |

# Hidden

## hidden

| Key     | Type   | Required | Example  |
| ------- | ------ | -------- | -------- |
| label   | string | no       | ""       |
| name    | string | yes      | "token"  |
| type    | string | yes      | "hidden" |
| default | string | yes      | "123"    |

# 3. default — 預設值

範例：

```php
'default' => [
    'site_name' => 'My Website',
    'subtitle'  => 'Just another WNCMS site',
    'show_banner' => 1,
],
```
