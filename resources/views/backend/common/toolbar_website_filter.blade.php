@php
    $showWebsiteFilter = ($forceShowToolbarWebsiteFilter ?? false) || gss('multi_website');
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
