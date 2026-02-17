# WNCMS Theme Config Specification

This document defines the complete structure and usage of `config.php` inside every WNCMS theme.

A theme config contains:

1. **Theme metadata** (`info`)
2. **Theme option groups** (`option_tabs`)
3. **Default values** (`default`)

## 1. info — Theme Metadata

### Structure

| Key         | Type   | Required | Description                   |
| ----------- | ------ | -------- | ----------------------------- |
| label       | string | yes      | Theme display name            |
| name        | string | yes      | Theme ID, same as folder name |
| author      | string | yes      | Creator name                  |
| description | string | yes      | Brief description             |
| version     | string | yes      | Semantic version (e.g. 1.0.0) |
| created_at  | string | yes      | YYYY-MM-DD                    |
| updated_at  | string | yes      | Last update date              |

### Example

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

## 2. option_tabs — Option Groups

`option_tabs` is a multi-tab layout where each tab holds many input options.

Example:

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

# Field Types Reference

Each field type supports different keys.
Below are **all supported types** with tables and examples.

# Basic Text & Number

## text

| Key         | Type   | Required | Example           |
| ----------- | ------ | -------- | ----------------- |
| label       | string | yes      | "Site Name"       |
| name        | string | yes      | "site_name"       |
| type        | string | yes      | "text"            |
| default     | string | no       | "My Website"      |
| description | string | no       | "Shown in header" |

Example:

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

Example:

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

Behavior:

- If width only → height auto-calculated via aspect ratio
- If height only → width auto-calculated
- If both missing → defaults to width 400px, height auto

Example:

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

Behavior:

- Does **not** store data
- Used only for admin preview images
- If width/height missing → width=100%, height=auto
- `col` supports Bootstrap grid

Example:

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

Stored Value Example:

```json
[
  { "image": "/uploads/a.jpg", "text": "", "url": "" },
  { "image": "/uploads/b.jpg", "text": "", "url": "" }
]
```

Example Config:

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

Example:

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

Behavior:

- Color input now supports `required` and placeholder.
- Default display value is `#FFA218` when empty.

# Repeater

## repeater

| Key       | Type   | Required | Example       |
| --------- | ------ | -------- | ------------- |
| label     | string | yes      | "Slides"      |
| name      | string | yes      | "hero_slides" |
| type      | string | yes      | "repeater"    |
| fields    | array  | yes      | field schema  |
| add_label | string | no       | "Add Slide"   |

Behavior:

- Repeater JS is loaded from local `wncms/js/jquery.repeater.min.js` (not CDN).
- Starter repeater rows use overflow-safe layout (`overflow-auto`) to avoid horizontal clipping.

`fields` supports simple input configs:

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

# 3. default — Default Values

Example:

```php
'default' => [
    'site_name' => 'My Website',
    'subtitle'  => 'Just another WNCMS site',
    'show_banner' => 1,
],
```

# 4. pages — Theme Pages Configuration

The `pages` array defines custom theme pages that can be added to menus through the WNCMS backend menu editor.

## Structure

```php
'pages' => [
    'page_key' => [
        'label' => 'Display label',
        'key'   => 'translation_key',
        'route' => 'route.name',
        'blade' => 'path.to.blade',
    ],
],
```

## Keys

| Key   | Type   | Required | Description                                              |
| ----- | ------ | -------- | -------------------------------------------------------- |
| label | string | yes      | Display label shown in menu editor (not translated)      |
| key   | string | yes      | Translation key used for localization (theme::word.key)  |
| route | string | yes      | Laravel route name (must be registered in routes/web.php)|
| blade | string | yes      | Blade file path relative to theme views directory        |

## Example

```php
'pages' => [
    'blog' => [
        'label' => '部落格頁面',
        'key'   => 'blog',
        'route' => 'frontend.pages.blog',
        'blade' => 'pages.blog',
    ],
    'about' => [
        'label' => '關於我們',
        'key'   => 'about',
        'route' => 'frontend.pages.about',
        'blade' => 'pages.about',
    ],
],
```

## Usage

1. Define the page configuration in your theme's `config.php`
2. Create the corresponding blade file in `themes/yourtheme/views/pages/blog.blade.php`
3. Register the route in your theme or application routes
4. The page will appear in the backend menu editor under "Theme Pages"
5. Admins can add the page to any menu by selecting it from the theme pages list

## Translation

The `label` field is displayed as-is in the menu editor. For multi-language support, create translation keys:

```php
// resources/themes/yourtheme/lang/en/word.php
return [
    'blog' => 'Blog',
];

// resources/themes/yourtheme/lang/zh_TW/word.php
return [
    'blog' => '部落格',
];
```
