# Theme Blades 中的 Pagination

本文件說明如何在你的 theme Blade 檔案中為 WNCMS 建立 pagination UI，包括：

- 你可以使用的 paginator methods
- 資料必須是 paginated 的要求
- 預設 Bootstrap paginator 的運作方式
- Bootstrap 生成的 HTML 具體範例
- Tailwind-based 自訂 paginator 範例（用於 xnovel theme）

## 要求

Pagination helpers 僅在你傳遞的變數是 Laravel paginator classes 的實例時才有效，通常為：

- `Illuminate\Pagination\LengthAwarePaginator`
- `Illuminate\Pagination\Paginator`

實際上這意味著：

- 你的 manager 呼叫（例如 `$manager->getList([...])`）必須設定為回傳 paginated 結果（例如透過傳遞 `page_size` 選項）。
- 若你傳遞普通的 `Collection`，像 `links()`、`currentPage()`、`lastPage()` 等方法將**不存在**並會導致錯誤。

務必確保當你需要 pagination 時，傳遞 paginated 物件（例如 `$novels`）給你的 Blade。

## 常用 Paginator Methods

當你收到 paginated 變數（例如 `$items`）時，通常會使用以下方法：

- `hasPages()`  
  若有多於一頁則回傳 `true`。

- `onFirstPage()`  
  若當前頁是第一頁則回傳 `true`。

- `previousPageUrl()`  
  回傳上一頁的 URL，若無則回傳 `null`。

- `nextPageUrl()`  
  回傳下一頁的 URL，若無則回傳 `null`。

- `currentPage()`  
  回傳當前頁碼（整數）。

- `lastPage()`  
  回傳最後頁碼（整數）。

- `getUrlRange($start, $end)`  
  回傳指定頁面範圍的 page => url 陣列。

- `hasMorePages()`  
  若當前頁之後還有更多頁則回傳 `true`。

- `links($view = null)`  
  渲染現成的 pagination view。預設 view 可以是 Bootstrap 或 Tailwind，取決於你的全域 paginator 設定。

## 預設 Bootstrap Paginator

預設情況下（或若你這樣設定），Laravel 可以生成 **Bootstrap-styled** paginator。

在 Blade 中的使用範例：

```blade
@if ($items->hasPages())
    <div class="mt-3">
        {{ $items->links('pagination::bootstrap-4') }}
    </div>
@endif
```

若你的 app 設定為使用 Bootstrap pagination，你也可以省略 view 名稱：

```blade
@if ($items->hasPages())
    <div class="mt-3">
        {{ $items->links() }}
    </div>
@endif
```

### Bootstrap Paginator HTML 範例

生成的 HTML（簡化版）看起來像：

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

你可以覆寫預設 view 或使用自己的 CSS 來樣式化它，但這是典型的 Bootstrap 結構。

## Tailwind-Based 自訂 Paginator (Responsive)

對於自訂 themes（例如 xnovel），你可能想要一個完全由你控制的 Tailwind-based paginator。

以下是 responsive 範例：

- 在 **mobile**：顯示 `Prev`、`current / last`、`Next`。
- 在 **desktop**：顯示完整頁碼。

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

### 開發者注意事項

- 將 `$novels` 替換為你自己的 paginated 變數（例如 `$posts`、`$videos`）。
- 確保它是 **paginated** 的，而非普通 collection。
- 你可以調整 classes 與佈局以符合你 theme 的設計。
