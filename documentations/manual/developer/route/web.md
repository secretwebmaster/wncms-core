# Web Routes

## Overview

The `web.php` route file serves as the main entry point for all web routes in WNCMS. It acts as a central hub that includes other route files (authentication, installation, backend, and frontend) and configures global middleware such as localization.

## File Location

```
wncms-core/routes/web.php
```

## Route Structure

### Main Route Group

All routes are wrapped in a main group that handles localization:

```php
Route::group([
    'prefix' => gss('enable_translation', true) ? LaravelLocalization::setLocale() : null,
    'middleware' => gss('enable_translation', true)
        ? ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
        : [],
], function () {
    // Route includes here
});
```

**Key Features:**

- **Conditional Prefix**: Adds locale prefix (e.g., `/en`, `/zh_TW`) if translation is enabled
- **Localization Middleware**:
  - `localeSessionRedirect`: Redirects based on session locale
  - `localizationRedirect`: Handles locale URL redirects
  - `localeViewPath`: Sets view path based on locale
- **Global Setting**: Uses `gss('enable_translation')` to check if multi-language is enabled

### Included Route Files

The web.php file includes four separate route files:

```php
// Installation routes
require __DIR__ . '/install.php';

// Authentication routes
require __DIR__ . '/auth.php';

// Backend admin panel routes
require __DIR__ . '/backend.php';

// Frontend public routes
require __DIR__ . '/frontend.php';
```

### Fallback Route

A fallback route catches all undefined URLs:

```php
Route::fallback([PageController::class, 'fallback']);
```

This allows for:

- Custom 404 pages
- Dynamic page routing
- Theme-specific fallback views

## Localization System

### URL Structure

When `enable_translation` is enabled, all routes are prefixed with the locale:

```
# English
https://example.com/en/
https://example.com/en/blog
https://example.com/en/post/my-article

# Traditional Chinese
https://example.com/zh_TW/
https://example.com/zh_TW/blog
https://example.com/zh_TW/post/my-article
```

### Locale Detection

WNCMS uses the `secretwebmaster/laravel-localization` package for locale handling:

1. **URL Locale**: Checks URL prefix first
2. **Session Locale**: Falls back to session-stored locale
3. **Browser Locale**: Uses Accept-Language header
4. **Default Locale**: Uses app's default locale

### Middleware Explanation

**localeSessionRedirect:**

- Stores user's selected locale in session
- Redirects to session locale if URL locale differs

**localizationRedirect:**

- Redirects to correct locale URL if missing or invalid
- Handles locale switching

**localeViewPath:**

- Sets view namespace based on locale
- Allows locale-specific views

## RouteServiceProvider Integration

The RouteServiceProvider bootstraps the web routes:

```php
public function boot()
{
    $this->routes(function () {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(__DIR__ . '/../../routes/web.php');
    });
}
```

**Key Points:**

- **web Middleware Group**: Applies session, CSRF protection, cookie encryption
- **Namespace**: Automatically prefixes controller namespaces
- **Group**: Loads web.php routes

## Global Settings

### enable_translation

Controls whether multi-language support is active:

```php
// Check if translation is enabled
if (gss('enable_translation')) {
    // Translation logic
}
```

Managed in backend: **Settings → General → Multi-Language**

### Supported Locales

Configure in `config/wncms.php`:

```php
'locales' => [
    'en' => 'English',
    'zh_TW' => '繁體中文',
    'zh_CN' => '简体中文',
    'ja' => '日本語',
],
```

## Working with Localized Routes

### Generating URLs

Use Laravel's `route()` helper with locale awareness:

```php
// Current locale URL
route('frontend.posts.show', ['slug' => 'my-post']);
// Output: /en/post/my-post (if current locale is 'en')

// Specific locale URL
route('frontend.posts.show', ['slug' => 'my-post', 'locale' => 'zh_TW']);
// Output: /zh_TW/post/my-post
```

### Switching Locales

Generate locale switcher links:

```blade
@foreach(config('wncms.locales') as $locale => $name)
    <a href="{{ LaravelLocalization::getLocalizedURL($locale) }}">
        {{ $name }}
    </a>
@endforeach
```

### Non-Localized Routes

Some routes don't need localization (e.g., API, webhooks):

```php
// Outside the localization group
Route::post('webhook/payment', [WebhookController::class, 'payment']);
```

## Route Caching

For production performance, cache routes:

```bash
# Generate route cache
php artisan route:cache

# Clear route cache
php artisan route:clear
```

**Important:** Route caching doesn't work with closures. Always use controller references.

## Best Practices

### 1. Use Named Routes

Always name routes for easy reference:

```php
// Good
Route::get('about', [PageController::class, 'about'])->name('pages.about');

// In views
<a href="{{ route('pages.about') }}">About</a>
```

### 2. Group Related Routes

Use route groups for organization:

```php
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('{slug}', [BlogController::class, 'show'])->name('show');
});
```

### 3. Apply Middleware Appropriately

```php
// Auth required
Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
});

// Guest only
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin']);
});
```

### 4. Separate Concerns

Keep route files focused:

- `web.php`: Main entry point, includes only
- `auth.php`: Authentication routes
- `backend.php`: Admin panel routes
- `frontend.php`: Public-facing routes
- `api.php`: API routes

### 5. Use Route Model Binding

Let Laravel resolve models automatically:

```php
Route::get('posts/{post}', [PostController::class, 'show']);

// In controller
public function show(Post $post)
{
    // $post is already loaded
}
```

## Debugging Routes

### List All Routes

```bash
# Show all registered routes
php artisan route:list

# Filter by name
php artisan route:list --name=frontend

# Filter by method
php artisan route:list --method=GET
```

### Check Route Registration

Verify a route exists:

```php
// In controller or view
$route = Route::getRoutes()->getByName('frontend.posts.show');
if ($route) {
    // Route exists
}
```

### Debug Locale Issues

Check current locale:

```php
app()->getLocale(); // Current locale
LaravelLocalization::getCurrentLocale(); // URL locale
session('locale'); // Session locale
```

## Common Issues

### 404 on Localized Routes

**Problem:** Routes work without locale but return 404 with locale prefix.

**Solution:**

1. Check `enable_translation` setting is true
2. Verify locale is in `config/wncms.php` supported locales
3. Clear route cache: `php artisan route:clear`

### Middleware Conflicts

**Problem:** Custom middleware conflicts with localization.

**Solution:** Order middleware correctly:

```php
Route::middleware(['localeSessionRedirect', 'custom'])->group(function () {
    // Routes
});
```

### Route Not Found

**Problem:** Named route doesn't exist.

**Solution:**

1. Check route is registered: `php artisan route:list`
2. Verify route file is included in web.php
3. Clear cache: `php artisan route:clear`

## Security Considerations

### CSRF Protection

All POST/PUT/PATCH/DELETE routes in web.php automatically have CSRF protection:

```blade
<form method="POST" action="{{ route('posts.store') }}">
    @csrf
    <!-- Form fields -->
</form>
```

### Rate Limiting

Apply rate limiting to prevent abuse:

```php
Route::middleware('throttle:60,1')->group(function () {
    // 60 requests per minute
});
```

### Route Authorization

Use middleware for access control:

```php
Route::middleware(['auth', 'can:view-admin'])->group(function () {
    // Admin routes
});
```

## Testing Routes

### Feature Tests

Test route responses:

```php
public function test_home_page_loads()
{
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertViewIs('frontend.pages.home');
}

public function test_localized_route()
{
    $response = $this->get('/en/blog');

    $response->assertStatus(200);
}
```

### Test Route Registration

```php
public function test_route_exists()
{
    $this->assertTrue(Route::has('frontend.posts.show'));
}
```

## See Also

- [Backend Routes](./backend.md) - Admin panel routes
- [Frontend Routes](./frontend.md) - Public routes
- [API Routes](./api.md) - API endpoints
- [Add Routes](./add-routes.md) - Creating custom routes
