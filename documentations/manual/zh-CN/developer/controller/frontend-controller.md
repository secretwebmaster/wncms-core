# Frontend Controller

`Wncms\Http\Controllers\Frontend\FrontendController` 是**由当前主题渲染的公开页面**的基础。它连接网站/主题上下文，可选载入主题 helpers，并在需要时提供基于名称的 model 解析。

## 职责

- 载入当前网站并设定 `$this->theme`（如果缺少则预设为 `default`）。
- 如果存在则引入主题 helpers：
  `resources/views/frontend/theme/{theme}/system/helpers.php` 或 `wncms-core/.../helpers.php`。
- 为直接映射到 model 的 controllers 提供 `getModelClass()`/命名辅助方法。
- 透过 base `Controller::view()` 渲染视图，以尊重主题/套件后备。

## 主题视图惯例

主要 → `frontend.theme.{theme}.{domain}.{view}`
后备 → `{package}::frontend.{domain}.{view}`

## 范例：LinkController

一个简单的 frontend controller，用于列出连结、查看单个连结和标签档案。

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

## 注意事项

- `archive()` 将 `$tagType` 正规化为 `link_*` 命名空间，并接受 slug 或翻译名称。
- 如果找不到 tag 或 link，重定向到首页路由。
- 对于真实专案，当您需要筛选、分页和快取时，请使用 Managers；此范例专注于 controller 结构。

## Route 模式

使用一致的 "frontend.\*" route 名称以匹配 controller 重定向。

```php
use Wncms\Http\Controllers\Frontend\LinkController;

Route::prefix('link')->controller(LinkController::class)->group(function () {
    Route::get('/', 'index')->name('frontend.links.index');
    Route::get('{id}', 'single')->name('frontend.links.single');
    Route::get('{tagType}/{slug}', 'archive')->name('frontend.links.archive');
});
```

## 提示

- 保持 controllers 轻量；重度资料塑形应该放在 Managers/Resources 中。
- 始终提供套件后备视图，以便主题可以逐步覆盖。
- 如果您的 controller 映射到 model，可以依赖 `getModelClass()`；否则，透过 Managers 或明确类别获取。
