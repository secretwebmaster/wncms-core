@foreach($page->getWidgets() ?? [] as $widget)
    @includeif("frontend.theme.{$website->theme}.pages.widgets.{$widget}", [
        'pageWidgetIndex' => $page->getWidgetIndex($loop->index, $widget),
    ])
@endforeach