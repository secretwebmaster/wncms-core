# 总览

WNCMS 是一个模组化的 Laravel 驱动 CMS，专为构建多语言、多站点网站和 API 而设计。它附带一个小型的、有主见的核心，并鼓励通过套件、主题和管理器扩展功能。本指南为您提供系统的高层次架构图，并根据您的角色为您指引正确的方向。

## 谁应该阅读本指南

| 角色       | 您的工作                                                    | 从哪里开始                        |
| ---------- | ----------------------------------------------------------- | --------------------------------- |
| 客户       | 使用浏览器仪表板发布文章、页面和链接                        | [用户指南](/user/overview)        |
| 网站开发者 | 在 Laravel 应用中构建自定义模型、控制器、管理器、视图和路由 | [开发者指南](/developer/overview) |
| 套件开发者 | 通过 Composer 发布可重用的 WNCMS 套件                       | [套件指南](/package/overview)     |
| API 用户   | 在 WNCMS 中管理内容并从另一个应用(Next.js、Vue 等)使用它    | [API 参考](/api/overview)         |

## 主要功能

- **Laravel 12 基础**，具有熟悉的 Eloquent、Blade、路由、队列和缓存。
- **模组化核心**，为模型、控制器、管理器、资源和路由提供干净的扩展点。
- **多语言和多站点**支持，通过为实际 i18n 设计的 trait 和 helper 实现。
- **可主题化前台**，从 `resources/views/frontend/theme/{themeId}` 加载模板，可选的 `ThemeServiceProvider`。
- **一流的 API** 控制器和资源，用于构建无头或混合站点。
- **套件生命周期**，具有注册钩子、激活时自动迁移、菜单和翻译。

## 架构概览

- **核心**: 由 `secretwebmaster/wncms-core` 提供，包括基类如 `BaseModel`、`BackendController`、`FrontendController`、`ApiController`、基础管理器、trait(标签、多站点、翻译)、资源、路由和后台 UI。
- **应用层自定义**: 创建扩展核心类的本地模型/控制器/管理器，并在需要时覆盖行为。
- **套件**: 可安装的 Composer 套件，注册模型、迁移、种子、控制器、管理器、菜单、翻译和路由。
- **主题**: 位于 `resources/views/frontend/theme/{themeId}` 下的前台模板、选项和小部件，带有 `system/config.php` 和可选的提供者。
- **API**: 为文章、链接、标签、用户、网站等提供一致的资源层和端点。

## 常见概念

- **模型管理器**: 一个以统一方式包装列表/获取查询、过滤器、标签、缓存和分页的服务。
- **标签系统**: 将语义分类附加到任何模型(`post_category`、`post_tag`、`link_category` 等)。
- **翻译**: 可翻译属性根据请求语言环境解析，具有干净的回退机制。
- **缓存**: 每个管理器的缓存键和标签标准化，以加速高流量页面。
- **路由**: 分为 `frontend`、`backend`、`api` 和 `install`，以提高清晰度和可测试性。

## 您可以构建什么

- 使用后台和主题的博客或文档站点。
- 由独立 SPA 或移动应用程序使用的内容 API。
- 在 Packagist 上分发的商业插件，具有自己的菜单、屏幕和数据库表。
- 具有共享用户群和本地化内容的完整多站点设置。

## 需求和安装

在安装之前，请检查[需求](/getting-started/requirements)。准备好后，按照[安装](/getting-started/installation)指南设置一个带有 `wncms-core` 的新 Laravel 项目，启用后台并登录。

## 约定

- **命名空间**: 核心位于 `Wncms\*` 下。您的应用代码可以扩展和覆盖这些。
- **视图**: 后台视图使用 `wncms::backend.*` 命名空间。前台主题位于 `resources/views/frontend/theme/{themeId}` 下。
- **翻译**: 在 PHP 中使用 `__('wncms::word.xxx')`，在 Blade 中使用 `@lang('wncms::word.xxx')`。
- **套件无需手动迁移**: 套件在后台激活期间运行迁移/种子。

## 版本控制和兼容性

- 针对 **Laravel 12** 和该版本支持的 PHP 版本。
- `wncms-*` 套件使用语义版本控制。请参阅每个套件的变更日志以获取升级说明。
- 发布说明中会宣布重大更改，并提供明确的迁移步骤。

## 下一步

- 探索[用户指南](/user/overview)以了解仪表板。
- 阅读[开发者指南](/developer/overview)以扩展模型、控制器和管理器。
- 通过[套件指南](/package/overview)构建和发布插件。
- 使用[API 参考](/api/overview)集成前端应用。
