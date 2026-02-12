# Frontend Controller

`Wncms\Http\Controllers\Frontend\FrontendController` 是**由當前主題渲染的公開頁面**的基礎。它連接網站/主題上下文，可選載入主題 helpers，並在需要時提供基於名稱的 model 解析。

## 職責

- 載入當前網站並設定 `$this->theme`（如果缺少則預設為 `default`）。
- 如果存在則引入主題 helpers：
  `resources/views/frontend/theme/{theme}/system/helpers.php` 或 `wncms-core/.../helpers.php`。
- 為直接映射到 model 的 controllers 提供 `getModelClass()`/命名輔助方法。
- 透過 base `Controller::view()` 渲染視圖，以尊重主題/套件後備。

## 主題視圖慣例

主要 → `frontend.theme.{theme}.{domain}.{view}`
後備 → `{package}::frontend.{domain}.{view}`

## 範例：LinkController

一個簡單的 frontend controller，用於列出連結、查看單個連結和標籤檔案。

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

## 注意事項

- `archive()` 將 `$tagType` 正規化為 `link_*` 命名空間，並接受 slug 或翻譯名稱。
- 如果找不到 tag 或 link，重定向到首頁路由。
- 對於真實專案，當您需要篩選、分頁和快取時，請使用 Managers；此範例專注於 controller 結構。

## Route 模式

使用一致的 "frontend.\*" route 名稱以匹配 controller 重定向。

```php
use Wncms\Http\Controllers\Frontend\LinkController;

Route::prefix('link')->controller(LinkController::class)->group(function () {
    Route::get('/', 'index')->name('frontend.links.index');
    Route::get('{id}', 'single')->name('frontend.links.single');
    Route::get('{tagType}/{slug}', 'archive')->name('frontend.links.archive');
});
```

## 提示

- 保持 controllers 輕量；重度資料塑形應該放在 Managers/Resources 中。
- 始終提供套件後備視圖，以便主題可以逐步覆蓋。
- 如果您的 controller 映射到 model，可以依賴 `getModelClass()`；否則，透過 Managers 或明確類別獲取。
