# Create a Controller

Controllers define the logic for handling requests in WNCMS. Depending on your use case, you’ll extend one of WNCMS’s built-in base controllers for the correct context.

## Choosing the right base

| Use case                   | Extend from                                          | Namespace                       | View / Response            |
| -------------------------- | ---------------------------------------------------- | ------------------------------- | -------------------------- |
| Backend (admin CRUD pages) | `Wncms\Http\Controllers\Backend\BackendController`   | `App\Http\Controllers\Backend`  | `backend.{model_plural}.*` |
| Frontend (theme pages)     | `Wncms\Http\Controllers\Frontend\FrontendController` | `App\Http\Controllers\Frontend` | `frontend.theme.{theme}.*` |
| API (JSON endpoints)       | `Wncms\Http\Controllers\Api\V1\ApiController`        | `App\Http\Controllers\Api\V1`   | JSON responses             |
| Custom base (rare)         | `Wncms\Http\Controllers\Controller`                  | `App\Http\Controllers`          | Choose any                 |

> Avoid extending the base `Controller` directly unless you’re creating a new controller layer or a specialized abstraction.

## Example: Backend controller

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
        // For pages that should show all data by default, use:
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

### Backend multisite create/update pattern

For models using WNCMS `single` or `multi` website modes, resolve and sync websites with shared controller helpers:

```php
$websiteIds = $this->resolveModelWebsiteIds($this->modelClass);

if ($this->supportsWncmsMultisite($this->modelClass) && empty($websiteIds)) {
    return back()->withInput()->withErrors(['message' => __('wncms::word.website_not_found')]);
}

$post = $this->modelClass::create($payload);
$this->syncModelWebsites($post, $websiteIds);
```

For most backend controllers, you can use the higher-level helper:

```php
$post = $this->modelClass::create($payload);
$this->syncBackendMutationWebsites($post);
```

For backend form-items, render website input via the shared partial:

```blade
@include('wncms::backend.common.website_selector', ['model' => $post, 'websites' => $websites ?? []])
```

## Example: Frontend controller

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

## Example: API controller

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

## Naming and routes

- Use plural route prefixes (e.g. `posts`, `links`).
- Match route names with plural convention (`posts.index`, `links.edit`, `frontend.links.single`).
- Register in the correct route file:

  - `routes/backend.php` for backend controllers.
  - `routes/frontend.php` for frontend controllers.
  - `routes/api.php` for API controllers.

## Summary

1. **Decide context** — backend, frontend, or API.
2. **Extend the appropriate base controller.**
3. **Follow naming conventions** for route names and view folders.
4. **Use WNCMS helpers** such as `wncms()->getModelClass()`, `wncms()->cache()`, and `wncms()->view()`.
5. **Keep controllers thin** — move business logic into Managers or Resources when possible.
