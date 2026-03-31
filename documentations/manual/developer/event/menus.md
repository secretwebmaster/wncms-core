# Menus Events

## Backend Menu Editor

#### wncms.backend.menus.sources.resolve

Triggered before backend menu editor accordion sources are rendered, allowing plugins or host projects to register additional selectable menu sources.

Core now prebuilds default model sources from models extending `Wncms\Models\BaseModel` where `public static bool $showInMenuEditor = true`.
Listeners can still:

- add extra sources
- remove default sources
- modify labels, queries, limits, or resolvers for default sources

Parameters:
- `&$sources` (array)
- `$request` (Request|null)

Example:

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
