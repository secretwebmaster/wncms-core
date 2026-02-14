# Developer Guide 概述

WNCMS 旨在帮助 Laravel 开发者透过 models、controllers、managers 与 packages 扩充功能。  
本节提供详细的参考，说明如何建立新功能与客制化现有功能，同时保持与 WNCMS core 的完全相容性。

## 开发者类型

有两种与 WNCMS 一起工作的开发者：

### Website Developer

你为客户安装 WNCMS 并在 Laravel application 内扩充系统。  
你可能：

- 在 `app/` 中建立新的 **models**、**controllers** 与 **managers**。
- 扩充或覆写 `wncms-core` 提供的 classes。
- 新增自订 routes、migrations 或 Blade templates。
- 整合现有的 WNCMS 元件，如 `PostManager`、`LinkManager` 与 translation traits。

### Package Developer

你建立**扩充 WNCMS 功能的 Composer packages**，可供其他人安装。  
你可能：

- 透过 `wncms()->registerPackage()` 注册 packages。
- 提供 migrations、seeders、translations 与 backend menus。
- 建立独立 modules，如 `wncms-faqs`、`wncms-ecommerce` 等。

## 核心扩充层

WNCMS 提供 base classes 与 traits 使开发保持一致：

| 层级       | 说明                                        | 范例 Base Class                                    |
| ---------- | ------------------------------------------- | -------------------------------------------------- |
| Model      | 资料表示与 Eloquent 整合                    | `Wncms\Models\BaseModel`                           |
| Controller | Backend、frontend 与 API 的路由逻辑         | `Wncms\Http\Controllers\Backend\BackendController` |
| Manager    | 资料存取与商业逻辑抽象                      | `Wncms\Services\Managers\ModelManager`             |
| Resource   | API 序列化层                                | `Wncms\Http\Resources\BaseResource`                |
| Trait      | 可扩充的功能 modules                        | `Wncms\Traits\HasTranslations`                     |
| Route      | Web、backend、frontend 与 API 的系统 routes | `routes/backend.php`, `routes/frontend.php`        |

本节的每个部分解释如何正确扩充这些层。

## 开发环境

要扩充 WNCMS，请确保你的 Laravel app 或 package 包含：

```bash
composer require secretwebmaster/wncms-core
```

若你在本地开发：

- 将 package clone 到 `packages/secretwebmaster/wncms-core`
- 在 `composer.json` 中透过 `"repositories"` 区段为本地开发新增它
- 执行 `composer update`

## 下一步

- [Model → Base Model](./model/base-model.md)
- [Controller → Backend Controller](./controller/backend-controller.md)
- [Manager → Base Manager](./manager/base-manager.md)
- [Plugin → Development Overview](./plugin/overview.md)
- [Locale → Localization Overview](./locale/localization-overview.md)
