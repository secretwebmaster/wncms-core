# Pagination in Theme Blades

This document explains how to build pagination UIs in your theme Blade files for WNCMS, including:

- The paginator methods you can use
- The requirement that the data is paginated
- How the default Bootstrap paginator works
- A concrete example of Bootstrap-generated HTML
- A Tailwind-based custom paginator example (used in xnovel theme)

## Requirements

Pagination helpers only work when the variable you pass is an instance of Laravel’s paginator classes, typically:

- `Illuminate\Pagination\LengthAwarePaginator`
- `Illuminate\Pagination\Paginator`

In practice this means:

- Your manager call (for example `$manager->getList([...])`) must be configured to return a paginated result (e.g. by passing a `page_size` option).
- If you pass a plain `Collection`, methods like `links()`, `currentPage()`, `lastPage()` will **not** exist and will cause errors.

Always make sure you pass a paginated object (for example `$novels`) to your Blade when you need pagination.

## Common Paginator Methods

When you receive a paginated variable (e.g. `$items`), the following methods are commonly used:

- `hasPages()`  
  Returns `true` if there is more than one page.

- `onFirstPage()`  
  Returns `true` if the current page is the first page.

- `previousPageUrl()`  
  Returns the URL for the previous page or `null` if there is none.

- `nextPageUrl()`  
  Returns the URL for the next page or `null` if there is none.

- `currentPage()`  
  Returns the current page number (integer).

- `lastPage()`  
  Returns the last page number (integer).

- `getUrlRange($start, $end)`  
  Returns an array of page => url for the given page range.

- `hasMorePages()`  
  Returns `true` if there are more pages after the current page.

- `links($view = null)`  
  Renders a ready-made pagination view. The default view can be Bootstrap or Tailwind, depending on your global paginator configuration.

## Default Bootstrap Paginator

By default (or if you configure it), Laravel can generate a **Bootstrap-styled** paginator.

Example usage in Blade:

```blade
@if ($items->hasPages())
    <div class="mt-3">
        {{ $items->links('pagination::bootstrap-4') }}
    </div>
@endif
```

If your app is configured to use Bootstrap pagination, you can also omit the view name:

```blade
@if ($items->hasPages())
    <div class="mt-3">
        {{ $items->links() }}
    </div>
@endif
```

### Example of Bootstrap Paginator HTML

The generated HTML (simplified) looks like:

```html
<nav>
  <ul class="pagination">
    <li class="page-item disabled" aria-disabled="true" aria-label="« Previous">
      <span class="page-link" aria-hidden="true">‹</span>
    </li>

    <li class="page-item active" aria-current="page">
      <span class="page-link">1</span>
    </li>
    <li class="page-item">
      <a class="page-link" href="https://example.com?page=2">2</a>
    </li>
    <li class="page-item">
      <a class="page-link" href="https://example.com?page=3">3</a>
    </li>

    <li class="page-item">
      <a class="page-link" href="https://example.com?page=2" rel="next" aria-label="Next »">›</a>
    </li>
  </ul>
</nav>
```

You can override the default view or style it with your own CSS, but this is the typical Bootstrap structure.

## Tailwind-Based Custom Paginator (Responsive)

For custom themes (for example xnovel), you may want a Tailwind-based paginator that you fully control.

Below is a responsive example:

- On **mobile**: shows `Prev`, `current / last`, `Next`.
- On **desktop**: shows full page numbers.

```blade
@if (method_exists($novels, 'links') && $novels->hasPages())
    <div class="mt-8 flex justify-center">
        <nav class="flex items-center space-x-1 text-sm">

            {{-- Previous --}}
            @if ($novels->onFirstPage())
                <span class="px-3 py-1 border rounded bg-gray-100 text-gray-400 cursor-default">
                    @lang('xnovel::word.prev')
                </span>
            @else
                <a href="{{ $novels->previousPageUrl() }}" class="px-3 py-1 border rounded bg-white hover:bg-gray-100">
                    @lang('xnovel::word.prev')
                </a>
            @endif

            {{-- Mobile: X / Y --}}
            <span class="px-2 py-1 text-gray-500 sm:hidden">
                {{ $novels->currentPage() }} / {{ $novels->lastPage() }}
            </span>

            {{-- Desktop: full page numbers --}}
            <div class="hidden sm:flex items-center space-x-1">
                @foreach ($novels->getUrlRange(1, $novels->lastPage()) as $page => $url)
                    @if ($page == $novels->currentPage())
                        <span class="px-3 py-1 border rounded bg-blue-600 text-white font-semibold">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-1 border rounded bg-white hover:bg-gray-100">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            </div>

            {{-- Next --}}
            @if ($novels->hasMorePages())
                <a href="{{ $novels->nextPageUrl() }}" class="px-3 py-1 border rounded bg-white hover:bg-gray-100">
                    @lang('xnovel::word.next')
                </a>
            @else
                <span class="px-3 py-1 border rounded bg-gray-100 text-gray-400 cursor-default">
                    @lang('xnovel::word.next')
                </span>
            @endif

        </nav>
    </div>
@endif
```

### Notes for Developers

- Replace `$novels` with your own paginated variable (e.g. `$posts`, `$videos`).
- Make sure it is **paginated**, not a plain collection.
- You can adjust classes and layout to match your theme’s design.
- Translation keys (`prev`, `next`) should exist in your theme language files (for example `xnovel::word.prev` and `xnovel::word.next`).
