# API Controller

`Wncms\Http\Controllers\Api\V1\ApiController` is the base for **versioned JSON APIs**. It adds global API-access gating, per-feature switches, and a helper to authenticate users via `api_token`.

## Responsibilities

- Enforce global API access toggle (`gss('enable_api_access')`) via middleware.
- Provide a lightweight **feature flag** check per endpoint.
- Provide **token-based authentication** helper (`api_token` on User model).
- Return consistent JSON error payloads.

## Middleware behavior

On every request, the constructor middleware blocks access when the global setting is off:

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

## Feature flag helper

Use inside actions to short-circuit when a specific API feature is disabled:

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

Usage:

```php
if ($resp = $this->checkApiEnabled('enable_posts_api')) {
    return $resp;
}
```

## Token authentication helper

Authenticates a user via `api_token` and logs them in for the request lifecycle.

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

Usage pattern:

```php
$user = $this->authenticateByApiToken($request);
if ($user instanceof \Illuminate\Http\JsonResponse) {
    return $user; // early return on auth failure
}
```

## Minimal usage example

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

        // Public listing (no token required)
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

## Route example (versioned)

```php
use Wncms\Http\Controllers\Api\V1\PostApiController;

Route::prefix('api/v1')->group(function () {
    Route::get('posts', [PostApiController::class, 'index'])->name('api.v1.posts.index');
    Route::post('posts', [PostApiController::class, 'store'])->name('api.v1.posts.store');
});
```

## Response conventions

- Success: `200`/`201` with `{ status: "success", data: ... }`
- Auth/feature errors: `401`/`403` with `{ status: "fail" | 403, message: "..." }`
- Not found: `404` with `{ status: 404, message: "Not Found" }`

Keep payloads small and stable; use API Resources where shaping is needed.

## Notes

- Use `ApiController` for JSON endpoints only; browser-rendered pages should use `FrontendController`.
- Prefer Managers for fetching lists/detail to reuse caching and filters.
- Add rate limiting and CORS at the route group/middleware level as needed.
