# Menus 事件

## 後台選單編輯器

#### wncms.backend.menus.sources.resolve

在後台選單編輯器渲染左側 accordion 來源之前觸發，允許插件或宿主專案註冊額外的可選選單來源。

Core 現在會先根據繼承 `Wncms\Models\BaseModel` 且宣告 `public static bool $showInMenuEditor = true` 的模型，自動建立預設模型來源。
監聽器仍可用於：

- 新增額外來源
- 移除預設來源
- 修改預設來源的標籤、查詢、數量限制或 resolver

參數：
- `&$sources`（array）
- `$request`（Request|null）

範例：

```php
Event::listen('wncms.backend.menus.sources.resolve', function (&$sources, $request) {
    $sources = collect($sources)
        ->reject(fn ($source) => ($source['key'] ?? null) === 'page')
        ->values()
        ->all();

    $sources[] = [
        'key' => 'projects',
        'label' => 'Projects',
        'type' => 'model_search',
        'model_class' => \App\Models\Project::class,
        'model_key' => 'project',
        'search_fields' => ['title', 'slug'],
        'result_limit' => 20,
        'url_resolver' => fn ($project) => route('frontend.projects.show', ['slug' => $project->slug]),
    ];
});
```
