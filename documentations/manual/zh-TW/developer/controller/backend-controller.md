# Backend Controller

`Wncms\Http\Controllers\Backend\BackendController` 是 **後台 CRUD** controllers 的基礎。它標準化了 model 解析、命名、cache-tag 處理和常見操作（`index/create/store/edit/update/destroy/bulk_delete`）。為每個後台資源擴展它。

## 主要職責

- 從 controller 名稱解析 **model class**（`PostController` → model key `post` → `wncms()->getModelClass('post')`）。
- 派生用於視圖和標籤的**資料表 / 單數 / 複數**名稱。
- 提供 **cache tag** 輔助方法和 `flush()` 方法。
- 提供具有合理預設值和 AJAX JSON 回應的主觀 **CRUD** 方法。

## 屬性和預設值

| 屬性          | 類型     | 來源                  | 預設行為                                                                                           |
| ------------- | -------- | --------------------- | -------------------------------------------------------------------------------------------------- |
| `$modelClass` | `string` | `getModelClass()`     | 從不含 `Controller` 的 controller 基礎名稱，snake-cased，透過 `wncms()->getModelClass(...)` 解析。 |
| `$cacheTags`  | `array`  | `getModelCacheTags()` | 預設為 `[$this->getModelTable()]`。                                                                |
| `$singular`   | `string` | `getModelSingular()`  | `str()->singular($this->getModelTable())`。                                                        |
| `$plural`     | `string` | `getModelPlural()`    | `str()->plural($this->getModelSingular())`。                                                       |

> 在需要時，在子 controller 中將這些作為 protected 屬性覆蓋。

## 可覆蓋的輔助方法

```php
// 從 controller 名稱解析 model class；如需自訂映射請覆蓋。
public function getModelClass(): string

// 取得底層 Eloquent 資料表名稱。
protected function getModelTable()

// 為此資源提供自訂 cache tags。
protected function getModelCacheTags(): array

// 自訂資源名詞。
protected function getModelSingular(): string
protected function getModelPlural(): string

// 為 single/multi 網站模式套用當前網站列表篩選。
protected function applyBackendListWebsiteScope(Builder $q): void
```

## Cache 控制

```php
public function flush(string|array|null $tags = null): bool
```

- 透過 `wncms()->cache()->tags($tag)->flush()` 清除已標記的快取。
- 如果 `$tags` 為 `null`，使用 `$this->cacheTags`。

## 多站點列表篩選輔助方法

`applyBackendListWebsiteScope()` 用於標準化後台 index 列表篩選（僅針對網站模式為 `single` 或 `multi` 的模型）。

- 從 `wncms()->website()->get()?->id` 讀取當前網站 ID。
- 僅在模型支援多站點作用域時呼叫 `applyWebsiteScope(...)`。
- 對 `global` 模型或無法解析當前網站時不做任何處理。
- 對 index 工具列篩選，建議統一使用 `website_id` 作為請求參數，並相容讀取舊鍵 `website`。

## 內建 CRUD 操作

所有操作假設標準的 backend Blade 路徑：`backend.{plural}.*`。

- `index(Request $request)`

  - 在 `$modelClass` 上建立基礎查詢，按 `id desc` 排序，回傳 `backend.{plural}.index`。
  - 傳遞 `page_title`、`models`。

- `create(int|string|null $id = null)`

  - 新實例或載入現有實例用於「複製/編輯為新」模式。
  - 回傳 `backend.{plural}.create` 與 `model`。

- `store(Request $request)`

  - `create($request->all())`，然後：

    - 如果是 AJAX：JSON `{ status, message, redirect }`。
    - 否則：重定向到 `route('{plural}.edit', ['id' => $model->id])`。

- `edit(int|string $id)`

  - 載入 model，回傳 `backend.{plural}.edit` 與 `model`。

- `update(Request $request, $id)`

  - 類似 `findOrFail` 的行為（如果缺少則回傳訊息），`update($request->all())`。
  - 如果是 AJAX：JSON `{ status, message, redirect }`。
  - 否則：重定向回編輯頁面。

- `destroy($id)`

  - 刪除 model，呼叫 `$this->flush()`，重定向到 index 並顯示成功訊息。

- `bulk_delete(Request $request)`

  - 接受 `model_ids` 作為 CSV 或陣列，批次刪除。
  - 如果是 AJAX：包含已刪除數量的 JSON；否則 `back()` 並顯示訊息。

> 訊息遵循 WNCMS 翻譯（例如 `__('wncms::word.successfully_updated')`）。標題使用 `__('wncms::word.' . $this->singular)`。

## 最小子類別範例

```php
namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Backend\BackendController;

class ProductController extends BackendController
{
    // BackendController 會自動解析 'product' → wncms()->getModelClass('product')
    // 除非您覆蓋 getModelClass() 或設定 protected $modelClass
}
```

## 覆蓋範例

```php
namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Backend\BackendController;

class CustomProductController extends BackendController
{
    protected function getModelClass(): string
    {
        return \App\Models\Product::class; // 自訂映射
    }

    protected function getModelCacheTags(): array
    {
        return ['products', 'catalog']; // 自訂 tags
    }

    public function index(Request $request)
    {
        // 使用 parent 的邏輯，或完全覆蓋
        $query = $this->modelClass::query();
        $this->applyBackendListWebsiteScope($query);

        // 添加自訂篩選
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $models = $query->orderByDesc('id')->paginate(20);

        return $this->view("backend.{$this->plural}.index", [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.' . $this->singular)]),
            'models' => $models,
        ]);
    }
}
```

## 總結

- 為每個後台資源擴展 `BackendController`。
- 依賴自動 model 解析或根據需要覆蓋。
- 使用內建的 CRUD 方法或覆蓋以進行自訂邏輯。
- 利用 cache flushing 和 WNCMS 輔助方法保持程式碼簡潔。

## WNCMS 多站點相容寫入模式

當模型支援 WNCMS 多站點方法時，不要在 `create()/update()` payload 硬寫舊版外鍵欄位（例如 `website_id`）。建議先更新一般欄位，再用 `bindWebsites(...)` 綁定站點關聯：

```php
$payload = [
    'name' => $request->name,
    'type' => $request->type,
];

$model->update($payload);

if (method_exists($model, 'bindWebsites') && method_exists($model, 'getWebsiteMode')) {
    if (in_array($model::getWebsiteMode(), ['single', 'multi'], true) && $websiteId) {
        $model->bindWebsites($websiteId);
    }
}
```
