# Menus 事件

## 后台选单编辑器

#### wncms.backend.menus.sources.resolve

在后台选单编辑器渲染左侧 accordion 来源之前触发，允许插件或宿主专案注册额外的可选选单来源。

Core 现在会先根据继承 `Wncms\Models\BaseModel` 且声明 `public static bool $showInMenuEditor = true` 的模型，自动建立默认模型来源。
监听器仍可用于：

- 添加额外来源
- 移除默认来源
- 修改默认来源的标签、查询、数量限制或 resolver

参数：
- `&$sources`（array）
- `$request`（Request|null）

范例：

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
