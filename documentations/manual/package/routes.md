# Routes in WNCMS Packages

WNCMS packages commonly include four route files:

- `routes/web.php`
- `routes/backend.php`
- `routes/frontend.php`
- `routes/api.php`

This document explains **how to create these files**, **what goes inside them**, and **how to load them through a Service Provider**.

## 1. Web Routes

The `web.php` file is your **entry point** for all browser-based routes.
WNCMS recommends wrapping backend + frontend routes inside the localization group:

```php
<?php

use Illuminate\Support\Facades\Route;
use Secretwebmaster\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['web', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
], function () {

    // Backend routes
    require __DIR__ . '/backend.php';

    // Frontend routes
    require __DIR__ . '/frontend.php';
});
```

### Why this structure?

- Automatically generates localized URLs like `/en/novel`, `/zh_TW/novel`
- Keeps your package clean and modular
- Allows users to override backend or frontend easily

## 2. Backend Routes

Backend routes (`routes/backend.php`) are used for **admin panel CRUD**, protected by WNCMS middlewares and permissions.

Example:

```php
<?php

use Illuminate\Support\Facades\Route;
use Secretwebmaster\WncmsNovels\Http\Controllers\Backend\NovelController;
use Secretwebmaster\WncmsNovels\Http\Controllers\Backend\NovelChapterController;

Route::prefix('panel')
    ->middleware(['web', 'auth', 'is_installed', 'has_website'])
    ->group(function () {

        // Novels CRUD
        Route::prefix('novels')->controller(NovelController::class)->group(function () {
            Route::get('/', 'index')->name('novels.index')->middleware('can:novel_index');
            Route::get('/create', 'create')->name('novels.create')->middleware('can:novel_create');
            Route::get('/create/{id}', 'create')->name('novels.clone')->middleware('can:novel_create');
            Route::post('/', 'store')->name('novels.store')->middleware('can:novel_create');
            Route::get('/{id}/edit', 'edit')->name('novels.edit')->middleware('can:novel_edit');
            Route::patch('/{id}', 'update')->name('novels.update')->middleware('can:novel_edit');
            Route::delete('/{id}', 'destroy')->name('novels.destroy')->middleware('can:novel_delete');
        });

        // Chapters CRUD
        Route::prefix('novel-chapters')->controller(NovelChapterController::class)->group(function () {
            Route::get('/', 'index')->name('novel_chapters.index')->middleware('can:novel_chapter_index');
            Route::get('/create', 'create')->name('novel_chapters.create')->middleware('can:novel_chapter_create');
            Route::get('/create/{id}', 'create')->name('novel_chapters.clone')->middleware('can:novel_chapter_create');
            Route::post('/', 'store')->name('novel_chapters.store')->middleware('can:novel_chapter_create');
            Route::get('/{id}/edit', 'edit')->name('novel_chapters.edit')->middleware('can:novel_chapter_edit');
            Route::patch('/{id}', 'update')->name('novel_chapters.update')->middleware('can:novel_chapter_edit');
            Route::delete('/{id}', 'destroy')->name('novel_chapters.destroy')->middleware('can:novel_chapter_delete');
        });
    });
```

### Notes

- Routes use WNCMS permissions (e.g., `novel_index`, `novel_create`)
- Loaded inside localization group via `routes/web.php`
- Accessible under: `/locale/panel/...`

## 3. Frontend Routes

Frontend routes (`routes/frontend.php`) provide **public-facing** pages for visitors.

Example:

```php
<?php

use Illuminate\Support\Facades\Route;
use Secretwebmaster\WncmsNovels\Http\Controllers\Frontend\NovelController;
use Secretwebmaster\WncmsNovels\Http\Controllers\Frontend\NovelChapterController;

// Novels
Route::prefix('novel')->controller(NovelController::class)->group(function () {
    Route::get('/search', 'search')->name('frontend.novels.search');
    Route::get('/', 'index')->name('frontend.novels.index');
    Route::get('/{type}/{slug}', [NovelController::class, 'tag'])
        ->where('type', wncms()->tag()->getTagTypesForRoute(wncms()->getModelClass('novel')))
        ->name('frontend.novels.tag');

    Route::get('/{slug}', 'show')->name('frontend.novels.show');
    Route::get('search/{keyword}', 'result')->name('frontend.novels.search.result');
    Route::post('search', 'search')->name('frontend.novels.search');
});

// Chapters
Route::prefix('novel/{novelSlug}chapter')->controller(NovelChapterController::class)->group(function () {
    Route::get('/{chapterSlug}', 'show')->name('frontend.novels.chapters.show');
});
```

### Notes

- Fully localized because loaded inside the `web.php` group
- URLs look like `/en/novel/...`

## 4. API Routes

API routes (`routes/api.php`) use `Route::apiResource()` to expose REST endpoints.

Example:

```php
<?php

use Illuminate\Support\Facades\Route;
use Secretwebmaster\WncmsNovels\Http\Controllers\Api\V1\NovelController;
use Secretwebmaster\WncmsNovels\Http\Controllers\Api\V1\NovelChapterController;

Route::prefix('api/v1')
    ->middleware(['api', 'is_installed', 'has_website'])
    ->group(function () {

        // Novels CRUD
        Route::apiResource('novels', NovelController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy'])
            ->names('api.v1.novels');

        // Chapters CRUD
        Route::apiResource('chapters', NovelChapterController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy'])
            ->names('api.v1.chapters');
    });
```

### Notes

- No localization for API routes
- Protected by `is_installed` & `has_website`
- Common examples:

  - `GET /api/v1/novels`
  - `POST /api/v1/chapters`

## 5. Loading Route Files

Your package’s Service Provider should load the route files using:

```php
foreach (['web', 'api'] as $file) {
    $path = __DIR__ . "/../../routes/{$file}.php";
    if (file_exists($path)) {
        $this->loadRoutesFrom($path);
    }
}
```

This automatically loads:

- `routes/web.php`

  - which itself includes `backend.php` and `frontend.php`

- `routes/api.php`

### TIP

**Always load routes _after_ calling `wncms()->registerPackage()`**.

If routes are loaded **before** the package is registered:

- Tag metadata is not registered yet
- Tag route regex (for `{type}`) cannot be resolved
- Frontend routes like:

```
/novel/{type}/{slug}
```

will not match correctly because WNCMS has not defined the allowed tag short keys.

### Correct Order Example

```php
public function boot(): void
{
    // 1. Register package metadata, models, controllers, tag types
    wncms()->registerPackage('wncms-novels', [
        'base' => __DIR__ . '/../../',
        // ...
    ]);

    // 2. Load route files (tag types now available)
    foreach (['web', 'api'] as $file) {
        $path = __DIR__ . "/../../routes/{$file}.php";
        if (file_exists($path)) {
            $this->loadRoutesFrom($path);
        }
    }
}
```

This ensures:

- Tag route constraints work
- `{type}` validation is correct
- All backend and frontend routes operate as expected

## 6. Summary

| File                  | Purpose                                                | Loaded By             |
| --------------------- | ------------------------------------------------------ | --------------------- |
| `routes/web.php`      | Web routes with localization, loads backend + frontend | Service Provider      |
| `routes/backend.php`  | Admin panel CRUD                                       | Included in `web.php` |
| `routes/frontend.php` | Visitor-facing frontend routes                         | Included in `web.php` |
| `routes/api.php`      | JSON API endpoints                                     | Service Provider      |

WNCMS packages **should always follow this structure** to stay consistent with the CMS ecosystem.

## 7. Recommended Folder Structure

```
routes/
│── web.php
│── api.php
│── backend.php
└── frontend.php
```
