@php
    $routeName = request()->route()?->getName() ?? '';
    $routePrefix = (string) str($routeName)->before('.');
    $modelKey = (string) str($routePrefix)->singular()->snake();
    $websiteMode = 'global';
    try {
        $modelClass = wncms()->getModelClass($modelKey);
        if (method_exists($modelClass, 'getMultiWebsiteMode')) {
            $websiteMode = $modelClass::getMultiWebsiteMode();
        } elseif (method_exists($modelClass, 'getWebsiteMode')) {
            $websiteMode = $modelClass::getWebsiteMode();
        }
    } catch (\Throwable $e) {
    }

    $isModelWebsiteScoped = in_array($websiteMode, ['single', 'multi'], true);
    $showWebsiteFilter = ($forceShowToolbarWebsiteFilter ?? false) || (gss('multi_website') && $isModelWebsiteScoped);
    $websiteFilterName = $websiteFilterName ?? 'website_id';
    $websites = $websites ?? wncms()->website()->getList();
    $selectedWebsiteId = request()->input($websiteFilterName, request()->input('website_id', request()->input('website')));
@endphp

@if ($showWebsiteFilter && !empty($websites))
    <div class="col-6 col-md-auto mb-3 ms-0">
        <select name="{{ $websiteFilterName }}" class="form-select form-select-sm">
            <option value="">@lang('wncms::word.select_item', ['item_name' => __('wncms::word.website')])</option>
            @foreach ($websites as $_website)
                <option value="{{ $_website->id }}" @selected((string) $_website->id === (string) $selectedWebsiteId)>{{ $_website->domain }}</option>
            @endforeach
        </select>
    </div>
@endif
