# API Controller

`Wncms\Http\Controllers\Api\V1\ApiController` 是**版本化 JSON APIs** 的基础。它添加全域 API 存取控制、每个功能的开关和透过 `api_token` 验证使用者的辅助方法。

## 职责

- 透过 middleware 强制执行全域 API 存取切换（`gss('enable_api_access')`）。
- 为每个端点提供轻量的**功能标志**检查。
- 提供基于 **token 的认证**辅助方法（User model 上的 `api_token`）。
- 回传一致的 JSON 错误 payload。

## Middleware 行为

在每个请求上，constructor middleware 在全域设定关闭时阻止存取：

```php
public function __construct()
{
    $this->middleware(function ($request, $next) {
        if (!gss('enable_api_access')) {
            return response()->json([
                'status'  => 403,
                'message' => 'API access is disabled',
            ], 403);
        }
        return $next($request);
    });
}
```

## 功能标志辅助方法

在操作中使用，当特定 API 功能被停用时短路：

```php
protected function checkApiEnabled(string $key): ?\Illuminate\Http\JsonResponse
{
    if (!gss($key)) {
        return response()->json([
            'status'  => 403,
            'message' => "API feature '{$key}' is disabled",
        ], 403);
    }
    return null;
}
```

使用方式：

```php
if ($resp = $this->checkApiEnabled('enable_posts_api')) {
    return $resp;
}
```

## Token 认证辅助方法

透过 `api_token` 验证使用者并在请求生命周期中登入。

```php
protected function authenticateByApiToken(
    \Illuminate\Http\Request $request
): \Illuminate\Http\JsonResponse|\Illuminate\Contracts\Auth\Authenticatable
{
    $token = $request->input('api_token');

    if (empty($token)) {
        return response()->json(['status' => 'fail', 'message' => 'Missing api_token'], 401);
    }

    $userModel = wncms()->getModelClass('user');
    $user = $userModel::where('api_token', $token)->first();

    if (!$user) {
        return response()->json(['status' => 'fail', 'message' => 'Invalid token'], 401);
    }

    auth()->login($user);
    return $user;
}
```

使用模式：

```php
$user = $this->authenticateByApiToken($request);
if ($user instanceof \Illuminate\Http\JsonResponse) {
    return $user; // 认证失败时提前回传
}
```

## 最小使用范例

```php
namespace Wncms\Http\Controllers\Api\V1;

use Illuminate\Http\Request;

class PostApiController extends ApiController
{
    public function index(Request $request)
    {
        if ($resp = $this->checkApiEnabled('enable_posts_api')) {
            return $resp;
        }

        // 公开列表（不需要 token）
        $posts = wncms()->post()->getList([
            'status' => 'published',
            'page_size' => (int) $request->input('page_size', 10),
            'cache' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $posts,
        ]);
    }

    public function store(Request $request)
    {
        if ($resp = $this->checkApiEnabled('enable_posts_api_write')) {
            return $resp;
        }

        $user = $this->authenticateByApiToken($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $post = wncms()->getModelClass('post')::create([
            'title' => $request->string('title'),
            'content' => $request->string('content'),
            'status' => 'draft',
            'user_id' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => ['id' => $post->id],
        ], 201);
    }
}
```

## Route 范例（版本化）

```php
use Wncms\Http\Controllers\Api\V1\PostApiController;

Route::prefix('api/v1')->group(function () {
    Route::get('posts', [PostApiController::class, 'index'])->name('api.v1.posts.index');
    Route::post('posts', [PostApiController::class, 'store'])->name('api.v1.posts.store');
});
```

## 回应惯例

- 成功：`200`/`201` 与 `{ status: "success", data: ... }`
- 认证/功能错误：`401`/`403` 与 `{ status: "fail" | 403, message: "..." }`
- 找不到：`404` 与 `{ status: 404, message: "Not Found" }`

保持 payload 小且稳定；在需要塑形时使用 API Resources。

## 注意事项

- 仅对 JSON 端点使用 `ApiController`；浏览器渲染的页面应使用 `FrontendController`。
- 偏好使用 Managers 来获取列表/详情以重用快取和筛选。
- 根据需要在 route group/middleware 层级添加速率限制和 CORS。
