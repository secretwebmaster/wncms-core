# 總覽

WNCMS 是一個模組化的 Laravel 驅動 CMS，專為構建多語言、多站點網站和 API 而設計。它附帶一個小型的、有主見的核心，並鼓勵通過套件、主題和管理器擴展功能。本指南為您提供系統的高層次架構圖，並根據您的角色為您指引正確的方向。

## 誰應該閱讀本指南

| 角色       | 您的工作                                                    | 從哪裡開始                        |
| ---------- | ----------------------------------------------------------- | --------------------------------- |
| 客戶       | 使用瀏覽器儀表板發布文章、頁面和鏈接                        | [用戶指南](/user/overview)        |
| 網站開發者 | 在 Laravel 應用中構建自定義模型、控制器、管理器、視圖和路由 | [開發者指南](/developer/overview) |
| 套件開發者 | 通過 Composer 發布可重用的 WNCMS 套件                       | [套件指南](/package/overview)     |
| API 用戶   | 在 WNCMS 中管理內容並從另一個應用(Next.js、Vue 等)使用它    | [API 參考](/api/overview)         |

## 主要功能

- **Laravel 12 基礎**，具有熟悉的 Eloquent、Blade、路由、隊列和緩存。
- **模組化核心**，為模型、控制器、管理器、資源和路由提供乾淨的擴展點。
- **多語言和多站點**支持，通過為實際 i18n 設計的 trait 和 helper 實現。
- **可主題化前台**，從 `resources/views/frontend/theme/{themeId}` 加載模板，可選的 `ThemeServiceProvider`。
- **一流的 API** 控制器和資源，用於構建無頭或混合站點。
- **套件生命週期**，具有註冊鉤子、激活時自動遷移、菜單和翻譯。

## 架構概覽

- **核心**: 由 `secretwebmaster/wncms-core` 提供，包括基類如 `BaseModel`、`BackendController`、`FrontendController`、`ApiController`、基礎管理器、trait(標籤、多站點、翻譯)、資源、路由和後台 UI。
- **應用層自定義**: 創建擴展核心類的本地模型/控制器/管理器，並在需要時覆蓋行為。
- **套件**: 可安裝的 Composer 套件，註冊模型、遷移、種子、控制器、管理器、菜單、翻譯和路由。
- **主題**: 位於 `resources/views/frontend/theme/{themeId}` 下的前台模板、選項和小部件，帶有 `system/config.php` 和可選的提供者。
- **API**: 為文章、鏈接、標籤、用戶、網站等提供一致的資源層和端點。

## 常見概念

- **模型管理器**: 一個以統一方式包裝列表/獲取查詢、過濾器、標籤、緩存和分頁的服務。
- **標籤系統**: 將語義分類附加到任何模型(`post_category`、`post_tag`、`link_category` 等)。
- **翻譯**: 可翻譯屬性根據請求語言環境解析，具有乾淨的回退機制。
- **緩存**: 每個管理器的緩存鍵和標籤標準化，以加速高流量頁面。
- **路由**: 分為 `frontend`、`backend`、`api` 和 `install`，以提高清晰度和可測試性。

## 您可以構建什麼

- 使用後台和主題的博客或文檔站點。
- 由獨立 SPA 或移動應用程序使用的內容 API。
- 在 Packagist 上分發的商業插件，具有自己的菜單、屏幕和數據庫表。
- 具有共享用戶群和本地化內容的完整多站點設置。

## 需求和安裝

在安裝之前，請檢查[需求](/getting-started/requirements)。準備好後，按照[安裝](/getting-started/installation)指南設置一個帶有 `wncms-core` 的新 Laravel 項目，啟用後台並登錄。

## 約定

- **命名空間**: 核心位於 `Wncms\*` 下。您的應用代碼可以擴展和覆蓋這些。
- **視圖**: 後台視圖使用 `wncms::backend.*` 命名空間。前台主題位於 `resources/views/frontend/theme/{themeId}` 下。
- **翻譯**: 在 PHP 中使用 `__('wncms::word.xxx')`，在 Blade 中使用 `@lang('wncms::word.xxx')`。
- **套件無需手動遷移**: 套件在後台激活期間運行遷移/種子。

## 版本控制和兼容性

- 針對 **Laravel 12** 和該版本支持的 PHP 版本。
- `wncms-*` 套件使用語義版本控制。請參閱每個套件的變更日志以獲取升級說明。
- 發布說明中會宣布重大更改，並提供明確的遷移步驟。

## 下一步

- 探索[用戶指南](/user/overview)以了解儀表板。
- 閱讀[開發者指南](/developer/overview)以擴展模型、控制器和管理器。
- 通過[套件指南](/package/overview)構建和發布插件。
- 使用[API 參考](/api/overview)集成前端應用。
