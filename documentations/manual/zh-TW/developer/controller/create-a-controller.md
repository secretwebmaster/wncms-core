# Create a Controller

Controllers 定義在 WNCMS 中處理請求的邏輯。根據您的使用案例，您將擴展 WNCMS 的內建 base controllers 以獲得正確的上下文。

## 選擇正確的 Base

| 使用案例                  | 擴展自                                               | 命名空間                        | 視圖 / 回應                |
| ------------------------- | ---------------------------------------------------- | ------------------------------- | -------------------------- |
| Backend（後台 CRUD 頁面） | `Wncms\Http\Controllers\Backend\BackendController`   | `App\Http\Controllers\Backend`  | `backend.{model_plural}.*` |
| Frontend（主題頁面）      | `Wncms\Http\Controllers\Frontend\FrontendController` | `App\Http\Controllers\Frontend` | `frontend.theme.{theme}.*` |
| API（JSON 端點）          | `Wncms\Http\Controllers\Api\V1\ApiController`        | `App\Http\Controllers\Api\V1`   | JSON 回應                  |
| 自訂 base（罕見）         | `Wncms\Http\Controllers\Controller`                  | `App\Http\Controllers`          | 任意選擇                   |

> 避免直接擴展 base `Controller`，除非您正在建立新的 controller 層級或專門的抽象。

## 範例：Backend Controller

```php
namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Backend\BackendController;

class PostController extends BackendController
{
    public function index(Request $request)
    {
        $query = $this->modelClass::query();
        $this->applyBackendListWebsiteScope($query);
        // 對預設應顯示全部資料的頁面，可改用：
        // $this->applyBackendListWebsiteScope($query, $request, true);

        if ($keyword = $request->get('keyword')) {
            $query->where('title', 'like', "%{$keyword}%");
        }

        $posts = $query->orderByDesc('id')->paginate(20);

        return $this->view("backend.{$this->plural}.index", [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.' . $this->singular)]),
            'posts' => $posts,
        ]);
    }
}
```

### Backend 多站點 create/update 模式

對於使用 WNCMS `single` 或 `multi` 網站模式的模型，建議使用 controller 共用 helper 解析並同步網站關聯：

```php
$websiteIds = $this->resolveModelWebsiteIds($this->modelClass);

if ($this->supportsWncmsMultisite($this->modelClass) && empty($websiteIds)) {
    return back()->withInput()->withErrors(['message' => __('wncms::word.website_not_found')]);
}

$post = $this->modelClass::create($payload);
$this->syncModelWebsites($post, $websiteIds);
```

在 backend form-items 中，網站輸入建議使用共用 partial：

```blade
@include('wncms::backend.common.website_selector', ['model' => $post, 'websites' => $websites ?? []])
```

## 範例：Frontend Controller

```php
namespace App\Http\Controllers\Frontend;

use Wncms\Http\Controllers\Frontend\FrontendController;

class PageController extends FrontendController
{
    public function show(string $slug)
    {
        $page = wncms()->getModelClass('page')::where('slug', $slug)->first();

        abort_unless($page, 404);

        return $this->view("frontend.theme.{$this->theme}.pages.show", compact('page'));
    }
}
```

## 範例：API Controller

```php
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Api\V1\ApiController;

class LinkController extends ApiController
{
    public function index(Request $request)
    {
        $links = wncms()->link()->getList([
            'status' => 'active',
            'page_size' => (int) $request->input('page_size', 20),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $links,
        ]);
    }
}
```

## 命名和 Routes

- 使用複數 route 前綴（例如 `posts`、`links`）。
- 將 route 名稱與複數慣例匹配（`posts.index`、`links.edit`、`frontend.links.single`）。
- 在正確的 route 檔案中註冊：

  - `routes/backend.php` 用於 backend controllers。
  - `routes/frontend.php` 用於 frontend controllers。
  - `routes/api.php` 用於 API controllers。

## 總結

1. **決定上下文** — backend、frontend 或 API。
2. **擴展適當的 base controller。**
3. **遵循命名慣例** 用於 route 名稱和視圖資料夾。
4. **使用 WNCMS helpers** 例如 `wncms()->getModelClass()`、`wncms()->cache()` 和 `wncms()->view()`。
5. **保持 controllers 精簡** — 盡可能將業務邏輯移至 Managers 或 Resources。
