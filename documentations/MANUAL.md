### 插入Event
```
app/Providers/AppServiceProvider.php
```
```
public function boot(): void
{
    // 綁定Relationship
    MacroableModels::addMacro(Link::class, 'clicks', function() {
        return $this->hasMany(Click::class);
    });

    // 加載 view
    $this->loadViewsFrom(resource_path('views/vendor/wncms-ext'), 'wncms-ext');

    // 監聽 Event
    Event::listen('wncms.link.update', function (Link $link, Request $request) {
        if ($request->hasFile('link_contents')) {
            foreach ($request->file('link_contents') as $image) {
                $link->addMedia($image)->toMediaCollection('link_content');
            }
        }
    });

    Event::listen('wncms.link.store', function (Link $link, Request $request) {
        if ($request->hasFile('link_contents')) {
            foreach ($request->file('link_contents') as $image) {
                $link->addMedia($image)->toMediaCollection('link_content');
            }
        }
    });
}
```