# WNCMS 命名標準

本文件定義 WNCMS 生態系統中所有 repositories、packages、plugins、themes 與 projects 的官方命名慣例。
目標是確保清晰度、一致性與長期可維護性。

## 1. Core System (Composer Packages)

這些是包含 models、traits、service providers 與系統功能的基礎函式庫。

格式：

```
secretwebmaster/wncms-{package}
```

範例：

```
secretwebmaster/wncms-core
secretwebmaster/wncms-novels
secretwebmaster/wncms-faqs
secretwebmaster/wncms-tags
```

特性：

- 不得包含完整的 Laravel application 結構
- 透過 Composer 載入
- 提供可重複使用的 WNCMS 元件

## 2. WNCMS Base Application

這是載入 `wncms-core` 的完整 Laravel project skeleton。

官方 repo：

```
secretwebmaster/wncms
```

特性：

- 包含完整的 Laravel 目錄結構
- 作為新 WNCMS 安裝的 base app
- 不包含網站特定的客製化

## 3. WNCMS Projects (Custom Applications)

Projects 是建立在 WNCMS 之上的完整網站或應用程式。

格式：

```
secretwebmaster/wncms-project-{project_name}
```

範例：

```
secretwebmaster/wncms-project-list
secretwebmaster/wncms-project-video
secretwebmaster/wncms-project-navigation
```

特性：

- 完整的 Laravel application
- 擴充或客製化 base WNCMS 安裝
- 可能包含自訂 modules、configs、themes 與 views

此命名避免與 Composer packages 混淆，並清楚識別它們為獨立應用程式。

## 4. WNCMS Plugins

Plugins 透過 plugin 系統擴充核心功能。

格式：

```
secretwebmaster/wncms-plugin-{plugin_name}
```

範例：

```
secretwebmaster/wncms-plugin-keyword-replacer
secretwebmaster/wncms-plugin-affiliate
secretwebmaster/wncms-plugin-analytics
```

特性：

- 必須遵循 WNCMS plugin 結構
- 提供隔離的、可啟用的功能
- 不是完整的 Laravel project

## 5. WNCMS Themes

Themes 為 WNCMS 提供 frontend 呈現層。

格式：

```
secretwebmaster/wncms-theme-{theme_name}
```

範例：

```
secretwebmaster/wncms-theme-starter
secretwebmaster/wncms-theme-novelist
secretwebmaster/wncms-theme-custom
```

特性：

- 儲存於 WNCMS theme 目錄下
- 應包含 config、assets 與 views
- 不應包含 backend 邏輯或 models

## 6. Summary Table

| 類別         | 格式                 | 範例                          |
| ------------ | -------------------- | ----------------------------- |
| Core package | wncms-{package}      | wncms-core                    |
| Base app     | wncms                | wncms                         |
| Projects     | wncms-project-{name} | wncms-project-video           |
| Plugins      | wncms-plugin-{name}  | wncms-plugin-keyword-replacer |
| Themes       | wncms-theme-{name}   | wncms-theme-starter           |

## 7. 此標準的目標

- 防止命名衝突
- 清楚區分 packages vs. projects vs. plugins vs. themes
- 使 repository 用途一目了然
