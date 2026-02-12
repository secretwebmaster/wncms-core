# Developer Guide 概述

WNCMS 旨在幫助 Laravel 開發者透過 models、controllers、managers 與 packages 擴充功能。  
本節提供詳細的參考，說明如何建立新功能與客製化現有功能，同時保持與 WNCMS core 的完全相容性。

## 開發者類型

有兩種與 WNCMS 一起工作的開發者：

### Website Developer

你為客戶安裝 WNCMS 並在 Laravel application 內擴充系統。  
你可能：

- 在 `app/` 中建立新的 **models**、**controllers** 與 **managers**。
- 擴充或覆寫 `wncms-core` 提供的 classes。
- 新增自訂 routes、migrations 或 Blade templates。
- 整合現有的 WNCMS 元件，如 `PostManager`、`LinkManager` 與 translation traits。

### Package Developer

你建立**擴充 WNCMS 功能的 Composer packages**，可供其他人安裝。  
你可能：

- 透過 `wncms()->registerPackage()` 註冊 packages。
- 提供 migrations、seeders、translations 與 backend menus。
- 建立獨立 modules，如 `wncms-faqs`、`wncms-ecommerce` 等。

## 核心擴充層

WNCMS 提供 base classes 與 traits 使開發保持一致：

| 層級       | 說明                                        | 範例 Base Class                                    |
| ---------- | ------------------------------------------- | -------------------------------------------------- |
| Model      | 資料表示與 Eloquent 整合                    | `Wncms\Models\BaseModel`                           |
| Controller | Backend、frontend 與 API 的路由邏輯         | `Wncms\Http\Controllers\Backend\BackendController` |
| Manager    | 資料存取與商業邏輯抽象                      | `Wncms\Services\Managers\ModelManager`             |
| Resource   | API 序列化層                                | `Wncms\Http\Resources\BaseResource`                |
| Trait      | 可擴充的功能 modules                        | `Wncms\Traits\HasTranslations`                     |
| Route      | Web、backend、frontend 與 API 的系統 routes | `routes/backend.php`, `routes/frontend.php`        |

本節的每個部分解釋如何正確擴充這些層。

## 開發環境

要擴充 WNCMS，請確保你的 Laravel app 或 package 包含：

```bash
composer require secretwebmaster/wncms-core
```

若你在本地開發：

- 將 package clone 到 `packages/secretwebmaster/wncms-core`
- 在 `composer.json` 中透過 `"repositories"` 區段為本地開發新增它
- 執行 `composer update`

## 下一步

- [Model → Base Model](./model/base-model.md)
- [Controller → Backend Controller](./controller/backend-controller.md)
- [Manager → Base Manager](./manager/base-manager.md)
- [Locale → Localization Overview](./locale/localization-overview.md)
