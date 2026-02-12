# Frontend Controller

`Wncms\Http\Controllers\Frontend\FrontendController` is the base for **public pages rendered by the active theme**. It wires website/theme context, optionally loads theme helpers, and offers name-based model resolution when needed.

## Responsibilities

- Load current website and set `$this->theme` (defaults to `default` if missing).
- Require theme helpers if present:
  `resources/views/frontend/theme/{theme}/system/helpers.php` or `wncms-core/.../helpers.php`.
- Provide `getModelClass()`/naming helpers for controllers that map directly to a model.
- Render views via the base `Controller::view()` so theme/package fallbacks are respected.

## Theme view convention

Primary → `frontend.theme.{theme}.{domain}.{view}`
Fallback → `{package}::frontend.{domain}.{view}`

## Example: LinkController

A simple frontend controller for listing links, viewing a single link, and tag archives.

```php
<?php

namespace Wncms\Http\Controllers\Frontend;

use Illuminate\Http\Request;

class LinkController extends FrontendController
{
    public function index()
    {
        return $this->view("frontend.theme.{$this->theme}.links.index");
    }

    public function archive($tagType, $slug)
    {
        if (str()->startsWith($tagType, 'link_')) {
            return redirect()->route('frontend.links.archive', [
                'tagType' => str_replace('link_', '', $tagType),
                'slug' => $slug
            ]);
        }

        $tagType = 'link_' . $tagType;

        $tag = wncms()->getModel('tag')::where('type', $tagType)
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)
                    ->orWhere('name', $slug)
                    ->orWhereHas('translations', function ($subq) use ($slug) {
                        $subq->where('name', $slug);
                    });
            })
            ->first();

        if (!$tag) {
            return redirect()->route('frontend.pages.home');
        }

        return $this->view("frontend.theme.{$this->theme}.links.archive", [
            'tag' => $tag,
        ]);
    }

    public function single($id)
    {
        $link = wncms()->getModelClass('link')::find($id);
        if (!$link) {
            return redirect()->route('frontend.pages.home');
        }

        return $this->view("frontend.theme.{$this->theme}.links.single", [
            'link' => $link,
        ]);
    }
}
```

Notes

- `archive()` normalizes `$tagType` to the `link_*` namespace and accepts slug or translated name.
- Redirects to a home route if the tag or link is not found.
- Use Managers for real projects when you need filtering, pagination, and caching; this example focuses on controller structure.

## Route patterns

Use consistent “frontend.\*” route names to match controller redirects.

```php
use Wncms\Http\Controllers\Frontend\LinkController;

Route::prefix('link')->controller(LinkController::class)->group(function () {
    Route::get('/', 'index')->name('frontend.links.index');
    Route::get('{id}', 'single')->name('frontend.links.single');
    Route::get('{tagType}/{slug}', 'archive')->name('frontend.links.archive');
});
```

## Tips

- Keep controllers light; heavy data shaping should live in Managers/Resources.
- Always provide a package fallback view so themes can override progressively.
- If your controller maps to a model, you can rely on `getModelClass()`; otherwise, fetch via Managers or explicit classes.
