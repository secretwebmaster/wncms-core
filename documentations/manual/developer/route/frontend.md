# Frontend Routes

## Overview

The `frontend.php` file defines all public-facing routes for WNCMS. These routes handle user-accessible content including pages, posts, user profiles, sitemaps, and other frontend features.

## File Location

```
wncms-core/routes/frontend.php
```

## Route Structure

### Main Route Group

All frontend routes are wrapped in a named group:

```php
Route::name('frontend.')
    ->middleware(['is_installed', 'has_website', 'full_page_cache'])
    ->group(function () {
        // Frontend routes
    });
```

**Middleware Breakdown:**

- **is_installed**: Ensures WNCMS is installed
- **has_website**: Verifies website exists
- **full_page_cache**: Enables full-page caching for performance

**Route Naming:**

All routes are prefixed with `frontend.`:

```
frontend.pages.home
frontend.posts.show
frontend.users.login
```

## Core Routes

### Home Page

```php
Route::get('/', [PageController::class, 'home'])->name('pages.home');
```

The homepage route, typically renders the theme's home.blade.php template.

### Blog Listing

```php
Route::get('blog', [PageController::class, 'blog'])->name('pages.blog');
```

Displays the blog index page with paginated posts.

## Pages

```php
Route::prefix('page')->name('pages.')->controller(PageController::class)->group(function () {
    Route::get('{slug}', 'show')->name('show');
});
```

**Usage:**

```
https://example.com/page/about-us
https://example.com/page/contact
https://example.com/page/privacy-policy
```

The `show` method handles:

- Template pages
- Plain pages
- Custom slug views
- Fallback redirects

## Posts

```php
Route::prefix('post')->name('posts.')->controller(PostController::class)->group(function () {
    // Post listing
    Route::get('/', 'index')->name('index');

    // Search
    Route::post('search', 'search')->name('search');
    Route::get('search/{keyword}', 'result')->name('search.result');

    // Rankings
    Route::get('rank', 'rank')->name('rank');
    Route::get('rank/{period}', 'rank')->name('rank.period');

    // Tag archives
    Route::get('{type}/{slug}', 'tag')
        ->where('type', wncms()->tag()->getTagTypesForRoute(wncms()->getModelClass('post')))
        ->name('tag');

    // Post lists (hot, new, liked, favorites)
    Route::get('list/{name?}/{period?}', 'post_list')
        ->where('name', 'hot|new|like|fav')
        ->where('period', 'today|yesterday|week|month')
        ->name('list');

    // Auth-required post routes
    Route::middleware('auth')->group(function () {
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('edit/{post}', 'edit')->name('edit');
        Route::post('update/{post}', 'update')->name('update');
    });

    // Single post (must be last)
    Route::get('{slug}', 'show')->name('show');
});
```

**Post Routes Examples:**

```
GET  /post                          → All posts
GET  /post/search/laravel           → Search results
POST /post/search                   → Search form
GET  /post/rank                     → Post rankings
GET  /post/rank/week                → Weekly rankings
GET  /post/category/laravel         → Posts by category
GET  /post/tag/php                  → Posts by tag
GET  /post/list/hot/week            → Hot posts this week
GET  /post/list/new                 → New posts
GET  /post/my-article               → Single post
```

### Tag Routes

Dynamic tag type routing:

```php
Route::get('{type}/{slug}', [PostController::class, 'tag'])
    ->where('type', wncms()->tag()->getTagTypesForRoute($model))
    ->name('posts.tag');
```

**Supported Tag Types:**

- `category`
- `tag`
- `keyword`
- Custom types defined in config

**Examples:**

```
/post/category/technology
/post/tag/laravel
/post/keyword/tutorial
```

### Post Lists

```php
Route::get('list/{name?}/{period?}', 'post_list')
    ->where('name', 'hot|new|like|fav')
    ->where('period', 'today|yesterday|week|month')
    ->name('list');
```

**List Types:**

- `hot`: Trending posts
- `new`: Latest posts
- `like`: Most liked
- `fav`: Most favorited

**Time Periods:**

- `today`: Today's posts
- `yesterday`: Yesterday's posts
- `week`: This week
- `month`: This month

## Links

```php
Route::prefix('link')->name('links.')->controller(LinkController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{id}', 'show')->name('show');
    Route::get('{type}/{slug}', 'tag')
        ->where('type', wncms()->tag()->getTagTypesForRoute(wncms()->getModelClass('link')))
        ->name('tag');
});
```

**Usage:**

```
GET /link                    → All links
GET /link/123                → Single link
GET /link/category/tools     → Links by category
```

## Users

```php
Route::prefix('user')->name('users.')->controller(UserController::class)->group(function () {
    // Public profile
    Route::get('{username}/posts', 'posts')->name('posts');

    // Authentication pages
    Route::get('/login', 'show_login')->name('login');
    Route::post('/login/submit', 'login')->name('login.submit');
    Route::post('/login/ajax', 'login_ajax')->name('login.ajax');

    Route::get('/register', 'show_register')->name('register');
    Route::post('/register/submit', 'register')->name('register.submit');

    Route::get('/password/forgot', 'show_password_forgot')->name('password.forgot');
    Route::post('/password/forgot/submit', 'password_forgot')->name('password.forgot.submit');

    // Profile management (auth required)
    Route::middleware('auth')->group(function () {
        Route::get('/profile', 'profile')->name('profile');
        Route::post('/profile/update', 'profile_update')->name('profile.update');
        Route::get('/settings', 'settings')->name('settings');
        Route::post('/settings/update', 'settings_update')->name('settings.update');
    });
});
```

**User Routes:**

```
GET  /user/john/posts               → User's posts
GET  /user/login                    → Login page
POST /user/login/submit             → Login form
GET  /user/register                 → Registration page
POST /user/register/submit          → Registration form
GET  /user/profile                  → User profile (auth)
POST /user/profile/update           → Update profile (auth)
```

## Comments

```php
Route::prefix('comments')->name('comments.')->controller(CommentController::class)->group(function () {
    Route::post('store', 'store')->name('store');
    Route::post('{id}/like', 'like')->name('like');
    Route::post('{id}/report', 'report')->name('report');
});
```

## Clicks (Analytics)

```php
Route::prefix('clicks')->name('clicks.')->controller(ClickController::class)->group(function () {
    Route::post('record', 'record')->name('record');
});
```

Used for tracking clicks on posts, links, and other content.

## Sitemaps

```php
Route::get('sitemap/posts', [SitemapController::class, 'posts'])->name('sitemaps.posts');
Route::get('sitemap/pages', [SitemapController::class, 'pages'])->name('sitemaps.pages');
Route::get('sitemap/tags/{model}/{type}', [SitemapController::class, 'tags'])->name('sitemaps.tags');
```

**Sitemap Routes:**

```
GET /sitemap/posts                      → Posts sitemap
GET /sitemap/pages                      → Pages sitemap
GET /sitemap/tags/post/category         → Category tags sitemap
```

## Full Page Caching

Frontend routes use full-page caching middleware for performance:

```php
Route::middleware('full_page_cache')->group(function () {
    // Cached routes
});
```

### Cache Behavior

- **Cached**: Static pages, post listings, single posts
- **Bypassed**: User-specific pages (profile, settings)
- **TTL**: Configurable in settings

### Clearing Cache

```php
// Clear specific page
wncms()->cache()->forget('page:about-us');

// Clear all frontend cache
wncms()->cache()->flushTag('frontend');
```

## Route Constraints

### Slug Validation

```php
Route::get('{slug}', [PostController::class, 'show'])
    ->where('slug', '[a-z0-9-]+');
```

### Period Validation

```php
Route::get('rank/{period}', [PostController::class, 'rank'])
    ->where('period', 'today|yesterday|week|month');
```

### Tag Type Validation

```php
Route::get('{type}/{slug}', [PostController::class, 'tag'])
    ->where('type', wncms()->tag()->getTagTypesForRoute($model));
```

This dynamically generates regex from configured tag types.

## SEO-Friendly URLs

### Post URLs

```
/post/my-article-title
/post/category/technology
/post/tag/laravel
```

### Page URLs

```
/page/about-us
/page/contact
/page/privacy-policy
```

### User URLs

```
/user/john/posts
```

## Generating URLs

### In Controllers

```php
// Redirect to home
return redirect()->route('frontend.pages.home');

// Redirect to post
return redirect()->route('frontend.posts.show', ['slug' => $post->slug]);

// Redirect to tag archive
return redirect()->route('frontend.posts.tag', [
    'type' => 'category',
    'slug' => $tag->slug,
]);
```

### In Views

```blade
{{-- Home link --}}
<a href="{{ route('frontend.pages.home') }}">Home</a>

{{-- Post link --}}
<a href="{{ route('frontend.posts.show', $post->slug) }}">
    {{ $post->title }}
</a>

{{-- Tag link --}}
<a href="{{ route('frontend.posts.tag', ['type' => 'category', 'slug' => $tag->slug]) }}">
    {{ $tag->name }}
</a>

{{-- Search form --}}
<form action="{{ route('frontend.posts.search') }}" method="POST">
    @csrf
    <input type="text" name="keyword">
    <button type="submit">Search</button>
</form>
```

## Authentication Check

Some routes require authentication:

```php
Route::middleware('auth')->group(function () {
    Route::get('profile', [UserController::class, 'profile']);
    Route::get('post/create', [PostController::class, 'create']);
});
```

**Redirect if Not Authenticated:**

```php
// In controller
public function create()
{
    if (!auth()->check()) {
        return redirect()->route('frontend.users.login');
    }

    return view('frontend.posts.create');
}
```

## Search Functionality

### POST Search

```php
Route::post('search', [PostController::class, 'search'])->name('search');
```

**Form:**

```blade
<form action="{{ route('frontend.posts.search') }}" method="POST">
    @csrf
    <input type="text" name="keyword" placeholder="Search...">
    <button type="submit">Search</button>
</form>
```

### GET Search Results

```php
Route::get('search/{keyword}', [PostController::class, 'result'])->name('search.result');
```

**URL:**

```
/post/search/laravel
/post/search/php+tutorial
```

## AJAX Routes

### AJAX Login

```php
Route::post('/login/ajax', [UserController::class, 'login_ajax'])->name('login.ajax');
```

**Usage:**

```javascript
$.ajax({
  url: '{{ route("frontend.users.login.ajax") }}',
  method: 'POST',
  data: {
    email: email,
    password: password,
    _token: '{{ csrf_token() }}',
  },
  success: function (response) {
    // Handle success
  },
})
```

## Best Practices

### 1. Use Named Routes

```blade
{{-- Good --}}
<a href="{{ route('frontend.posts.show', $post->slug) }}">{{ $post->title }}</a>

{{-- Avoid --}}
<a href="/post/{{ $post->slug }}">{{ $post->title }}</a>
```

### 2. Cache Static Content

Enable full-page caching for static pages:

```php
Route::middleware('full_page_cache')->get('about', [PageController::class, 'about']);
```

### 3. Validate User Input

```php
public function search(Request $request)
{
    $request->validate([
        'keyword' => 'required|string|min:2|max:100',
    ]);

    // Search logic
}
```

### 4. Use Route Model Binding

```php
Route::get('post/{post}', [PostController::class, 'show']);

// In controller
public function show(Post $post)
{
    // $post is auto-loaded
}
```

### 5. Handle 404s Gracefully

```php
public function show($slug)
{
    $post = Post::where('slug', $slug)->first();

    if (!$post) {
        abort(404, 'Post not found');
    }

    return view('frontend.posts.show', compact('post'));
}
```

## See Also

- [Backend Routes](./backend.md) - Admin routes
- [API Routes](./api.md) - API endpoints
- [Frontend Controller](../controller/frontend-controller.md)
- [Add Routes](./add-routes.md) - Creating custom routes
