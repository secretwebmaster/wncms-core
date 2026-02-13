# Create a Controller

Controllers 定义在 WNCMS 中处理请求的逻辑。根据您的使用案例，您将扩展 WNCMS 的内建 base controllers 以获得正确的上下文。

## 选择正确的 Base

| 使用案例                  | 扩展自                                               | 命名空间                        | 视图 / 回应                |
| ------------------------- | ---------------------------------------------------- | ------------------------------- | -------------------------- |
| Backend（后台 CRUD 页面） | `Wncms\Http\Controllers\Backend\BackendController`   | `App\Http\Controllers\Backend`  | `backend.{model_plural}.*` |
| Frontend（主题页面）      | `Wncms\Http\Controllers\Frontend\FrontendController` | `App\Http\Controllers\Frontend` | `frontend.theme.{theme}.*` |
| API（JSON 端点）          | `Wncms\Http\Controllers\Api\V1\ApiController`        | `App\Http\Controllers\Api\V1`   | JSON 回应                  |
| 自订 base（罕见）         | `Wncms\Http\Controllers\Controller`                  | `App\Http\Controllers`          | 任意选择                   |

> 避免直接扩展 base `Controller`，除非您正在建立新的 controller 层级或专门的抽象。

## 范例：Backend Controller

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

### Backend 多站点 create/update 模式

对于使用 WNCMS `single` 或 `multi` 网站模式的模型，建议使用 controller 共享 helper 解析并同步网站关系：

```php
$websiteIds = $this->resolveModelWebsiteIds($this->modelClass);

if ($this->supportsWncmsMultisite($this->modelClass) && empty($websiteIds)) {
    return back()->withInput()->withErrors(['message' => __('wncms::word.website_not_found')]);
}

$post = $this->modelClass::create($payload);
$this->syncModelWebsites($post, $websiteIds);
```

在 backend form-items 中，网站输入建议使用共用 partial：

```blade
@include('wncms::backend.common.website_selector', ['model' => $post, 'websites' => $websites ?? []])
```

## 范例：Frontend Controller

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

## 范例：API Controller

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

- 使用复数 route 前缀（例如 `posts`、`links`）。
- 将 route 名称与复数惯例匹配（`posts.index`、`links.edit`、`frontend.links.single`）。
- 在正确的 route 档案中注册：

  - `routes/backend.php` 用于 backend controllers。
  - `routes/frontend.php` 用于 frontend controllers。
  - `routes/api.php` 用于 API controllers。

## 总结

1. **决定上下文** — backend、frontend 或 API。
2. **扩展适当的 base controller。**
3. **遵循命名惯例** 用于 route 名称和视图资料夹。
4. **使用 WNCMS helpers** 例如 `wncms()->getModelClass()`、`wncms()->cache()` 和 `wncms()->view()`。
5. **保持 controllers 精简** — 尽可能将业务逻辑移至 Managers 或 Resources。
