# API Controller

`Wncms\Http\Controllers\Api\V1\ApiController` 是**版本化 JSON APIs** 的基礎。它添加全域 API 存取控制、每個功能的開關和透過 `api_token` 驗證使用者的輔助方法。

## 職責

- 透過 middleware 強制執行全域 API 存取切換（`gss('enable_api_access')`）。
- 為每個端點提供輕量的**功能標誌**檢查。
- 提供基於 **token 的認證**輔助方法（User model 上的 `api_token`）。
- 回傳一致的 JSON 錯誤 payload。

## Middleware 行為

在每個請求上，constructor middleware 在全域設定關閉時阻止存取：

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

## 功能標誌輔助方法

在操作中使用，當特定 API 功能被停用時短路：

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

## Token 認證輔助方法

透過 `api_token` 驗證使用者並在請求生命週期中登入。

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
    return $user; // 認證失敗時提前回傳
}
```

## 最小使用範例

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

        // 公開列表（不需要 token）
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

## Route 範例（版本化）

```php
use Wncms\Http\Controllers\Api\V1\PostApiController;

Route::prefix('api/v1')->group(function () {
    Route::get('posts', [PostApiController::class, 'index'])->name('api.v1.posts.index');
    Route::post('posts', [PostApiController::class, 'store'])->name('api.v1.posts.store');
});
```

## 回應慣例

- 成功：`200`/`201` 與 `{ status: "success", data: ... }`
- 認證/功能錯誤：`401`/`403` 與 `{ status: "fail" | 403, message: "..." }`
- 找不到：`404` 與 `{ status: 404, message: "Not Found" }`

保持 payload 小且穩定；在需要塑形時使用 API Resources。

## 注意事項

- 僅對 JSON 端點使用 `ApiController`；瀏覽器渲染的頁面應使用 `FrontendController`。
- 偏好使用 Managers 來獲取列表/詳情以重用快取和篩選。
- 根據需要在 route group/middleware 層級添加速率限制和 CORS。
