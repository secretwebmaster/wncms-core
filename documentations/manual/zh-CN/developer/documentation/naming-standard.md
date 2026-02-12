# WNCMS 命名标准

本文件定义 WNCMS 生态系统中所有 repositories、packages、plugins、themes 与 projects 的官方命名惯例。
目标是确保清晰度、一致性与长期可维护性。

## 1. Core System (Composer Packages)

这些是包含 models、traits、service providers 与系统功能的基础函式库。

格式：

```
secretwebmaster/wncms-{package}
```

范例：

```
secretwebmaster/wncms-core
secretwebmaster/wncms-novels
secretwebmaster/wncms-faqs
secretwebmaster/wncms-tags
```

特性：

- 不得包含完整的 Laravel application 结构
- 透过 Composer 载入
- 提供可重复使用的 WNCMS 元件

## 2. WNCMS Base Application

这是载入 `wncms-core` 的完整 Laravel project skeleton。

官方 repo：

```
secretwebmaster/wncms
```

特性：

- 包含完整的 Laravel 目录结构
- 作为新 WNCMS 安装的 base app
- 不包含网站特定的客制化

## 3. WNCMS Projects (Custom Applications)

Projects 是建立在 WNCMS 之上的完整网站或应用程式。

格式：

```
secretwebmaster/wncms-project-{project_name}
```

范例：

```
secretwebmaster/wncms-project-list
secretwebmaster/wncms-project-video
secretwebmaster/wncms-project-navigation
```

特性：

- 完整的 Laravel application
- 扩充或客制化 base WNCMS 安装
- 可能包含自订 modules、configs、themes 与 views

此命名避免与 Composer packages 混淆，并清楚识别它们为独立应用程式。

## 4. WNCMS Plugins

Plugins 透过 plugin 系统扩充核心功能。

格式：

```
secretwebmaster/wncms-plugin-{plugin_name}
```

范例：

```
secretwebmaster/wncms-plugin-keyword-replacer
secretwebmaster/wncms-plugin-affiliate
secretwebmaster/wncms-plugin-analytics
```

特性：

- 必须遵循 WNCMS plugin 结构
- 提供隔离的、可启用的功能
- 不是完整的 Laravel project

## 5. WNCMS Themes

Themes 为 WNCMS 提供 frontend 呈现层。

格式：

```
secretwebmaster/wncms-theme-{theme_name}
```

范例：

```
secretwebmaster/wncms-theme-starter
secretwebmaster/wncms-theme-novelist
secretwebmaster/wncms-theme-custom
```

特性：

- 储存于 WNCMS theme 目录下
- 应包含 config、assets 与 views
- 不应包含 backend 逻辑或 models

## 6. Summary Table

| 类别         | 格式                 | 范例                          |
| ------------ | -------------------- | ----------------------------- |
| Core package | wncms-{package}      | wncms-core                    |
| Base app     | wncms                | wncms                         |
| Projects     | wncms-project-{name} | wncms-project-video           |
| Plugins      | wncms-plugin-{name}  | wncms-plugin-keyword-replacer |
| Themes       | wncms-theme-{name}   | wncms-theme-starter           |

## 7. 此标准的目标

- 防止命名冲突
- 清楚区分 packages vs. projects vs. plugins vs. themes
- 使 repository 用途一目了然
