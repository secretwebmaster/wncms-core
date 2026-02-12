# Theme Blades 中的 Pagination

本文件说明如何在你的 theme Blade 档案中为 WNCMS 建立 pagination UI，包括：

- 你可以使用的 paginator methods
- 资料必须是 paginated 的要求
- 预设 Bootstrap paginator 的运作方式
- Bootstrap 生成的 HTML 具体范例
- Tailwind-based 自订 paginator 范例（用于 xnovel theme）

## 要求

Pagination helpers 仅在你传递的变数是 Laravel paginator classes 的实例时才有效，通常为：

- `Illuminate\Pagination\LengthAwarePaginator`
- `Illuminate\Pagination\Paginator`

实际上这意味着：

- 你的 manager 呼叫（例如 `$manager->getList([...])`）必须设定为回传 paginated 结果（例如透过传递 `page_size` 选项）。
- 若你传递普通的 `Collection`，像 `links()`、`currentPage()`、`lastPage()` 等方法将**不存在**并会导致错误。

务必确保当你需要 pagination 时，传递 paginated 物件（例如 `$novels`）给你的 Blade。

## 常用 Paginator Methods

当你收到 paginated 变数（例如 `$items`）时，通常会使用以下方法：

- `hasPages()`  
  若有多于一页则回传 `true`。

- `onFirstPage()`  
  若当前页是第一页则回传 `true`。

- `previousPageUrl()`  
  回传上一页的 URL，若无则回传 `null`。

- `nextPageUrl()`  
  回传下一页的 URL，若无则回传 `null`。

- `currentPage()`  
  回传当前页码（整数）。

- `lastPage()`  
  回传最后页码（整数）。

- `getUrlRange($start, $end)`  
  回传指定页面范围的 page => url 阵列。

- `hasMorePages()`  
  若当前页之后还有更多页则回传 `true`。

- `links($view = null)`  
  渲染现成的 pagination view。预设 view 可以是 Bootstrap 或 Tailwind，取决于你的全域 paginator 设定。

## 预设 Bootstrap Paginator

预设情况下（或若你这样设定），Laravel 可以生成 **Bootstrap-styled** paginator。

在 Blade 中的使用范例：

```blade
@if ($items->hasPages())
    <div class="mt-3">
        {{ $items->links('pagination::bootstrap-4') }}
    </div>
@endif
```

若你的 app 设定为使用 Bootstrap pagination，你也可以省略 view 名称：

```blade
@if ($items->hasPages())
    <div class="mt-3">
        {{ $items->links() }}
    </div>
@endif
```

### Bootstrap Paginator HTML 范例

生成的 HTML（简化版）看起来像：

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

你可以覆写预设 view 或使用自己的 CSS 来样式化它，但这是典型的 Bootstrap 结构。

## Tailwind-Based 自订 Paginator (Responsive)

对于自订 themes（例如 xnovel），你可能想要一个完全由你控制的 Tailwind-based paginator。

以下是 responsive 范例：

- 在 **mobile**：显示 `Prev`、`current / last`、`Next`。
- 在 **desktop**：显示完整页码。

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

### 开发者注意事项

- 将 `$novels` 替换为你自己的 paginated 变数（例如 `$posts`、`$videos`）。
- 确保它是 **paginated** 的，而非普通 collection。
- 你可以调整 classes 与布局以符合你 theme 的设计。
