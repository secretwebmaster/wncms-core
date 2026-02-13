# Base Controller

WNCMS 的 base controller 是一個精簡的基礎，集中處理視圖解析。在大多數情況下，**您不應該直接擴展此類別**。相反，應擴展其子 controllers，它們封裝了特定層級的行為。

## 我應該擴展哪個 Controller？

### 對於 Backend（後台 CRUD）

**擴展：** `Wncms\Http\Controllers\Backend\BackendController`

**用於：** 後台 CRUD 頁面、設定頁面、列表和編輯 models。

**原因：** 提供 model 命名、cache tag 輔助方法和統一的 backend CRUD 模式。

**命名空間：** `App\Http\Controllers\Backend\...` 或套件 backend controllers。

**視圖：** `backend.{models}.*` Blade 視圖。

### 對於 Frontend（公開主題頁面）

**擴展：** `Wncms\Http\Controllers\Frontend\FrontendController`

**用於：** 由當前主題渲染的公開網站頁面（首頁、文章、頁面、標籤等）。

**原因：** 主題感知渲染、網站上下文和 frontend 慣例。

**命名空間：** `App\Http\Controllers\Frontend\...` 或套件 frontend controllers。

**視圖：** `frontend.*` Blade 視圖，透過主題解析。

### 對於 API（JSON 端點）

**擴展：** `Wncms\Http\Controllers\Api\ApiController`

**用於：** 供外部應用程式（Vue、Next.js、mobile）使用的 JSON APIs。

**原因：** API 相關問題，如認證、標準化回應/resources。

**命名空間：** `App\Http\Controllers\Api\V1\...` 或套件 API controllers。

**回應：** JSON 回應 / API resources。

## 何時直接擴展 Base Class

- 建立新的 controller **層級**（例如，專用子系統），其他 controllers 將會擴展它。
- 建立共享抽象，在分層之前添加橫切輔助方法（罕見）。

如果您不符合這些情況，請使用上述子 controller。

## 共用多站點輔助方法

Base `Controller` 現在提供可重用的多站點輔助方法，backend/frontend controllers 可共用相同的網站解析與能力檢查邏輯：

```php
protected function supportsWncmsMultisite(string $modelClass): bool
protected function resolveModelWebsiteIds(string $modelClass, array|string|int|null $websiteIds = null): array
protected function syncModelWebsites($model, array $websiteIds): void
```

- `supportsWncmsMultisite()`：
  - 透過 `getWebsiteMode()` 與 `bindWebsites()` 檢查模型是否支援
  - 將 `single` 與 `multi` 視為已啟用多站點模式
- `resolveModelWebsiteIds()`：
  - 支援陣列或逗號分隔字串輸入網站 ID
  - 在 single 模式只取第一個網站 ID
  - 在 multi 模式使用全部網站 ID
  - 當 `gss('multi_website')` 關閉時，回退到當前網站 ID
  - 只保留存在的網站 ID
- `syncModelWebsites()`：
  - 依模型網站模式同步綁定
  - `single`：綁定第一個網站
  - `multi`：先清空舊綁定，再綁定當前選擇

## 下一步

- Backend：參見 [Backend Controller](./backend-controller)
- Frontend：參見 [Frontend Controller](./frontend-controller)
- API：參見 [API Controller](./api-controller)
- 腳手架：參見 [Create a Controller](./create-a-controller)
