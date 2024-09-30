{{-- keyword --}}
@if(empty($hideToolbarKeywordFiller))
    <div class="d-flex align-items-center col-12 col-md-auto mb-3 ms-0 me-1">
        <span class="svg-icon svg-icon-1 position-absolute ms-4">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
                <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
            </svg>
        </span>
        <input type="text" name="keyword" value="{{ request()->keyword }}" data-kt-ecommerce-order-filter="search" class="form-control form-control-sm ps-14" placeholder="@lang('word.search')" />
    </div>
@endif

{{-- statuses --}}
@if(empty($hideToolbarStatusFiller) && !empty($statuses))
    <div class="col-6 col-md-auto mb-3 ms-0">
        <select name="status" class="form-select form-select-sm">
            <option value="">@lang('word.select_status')</option>
            @foreach($statuses as $status)
                <option value="{{ $status }}" @if($status == request()->status) selected @endif>@lang('word.' . $status)</option>
            @endforeach
        </select>
    </div>
@endif

{{-- 網站列表 website --}}
{{-- user $_website to avoid override the globel $website --}}
@if(empty($hideToolbarWebsiteFiller) && !empty($websites))
    <div class="col-6 col-md-auto mb-3 ms-0">
        <select name="website" class="form-select form-select-sm">
            <option value="">@lang('word.select_website')</option>
            @foreach($websites as $_website)
                <option value="{{ $_website->id }}" @selected(wncms()->isSelectedWebsite($_website))>{{ $_website->domain }}</option>
            @endforeach
        </select>
    </div>
@endif

{{-- 排序依據 order --}}
@if(empty($hideToolbarOrderFiller) && !empty($orders))
    <div class="col-6 col-md-auto mb-3 ms-0">
        <select name="order" class="form-select form-select-sm">
            <option value="">@lang('word.select_order')</option>
            @foreach($orders as $order)
                <option value="{{ $order }}" @if($order == request()->order) selected @endif>@lang('word.' . $order)</option>
            @endforeach
        </select>
    </div>
@endif

{{-- 大小 sort --}}
@if(empty($hideToolbarOrderFiller) && empty($hideToolbarSortFiller) && !empty($orders))
    <div class="col-6 col-md-auto mb-3 ms-0">
        <select name="sort" class="form-select form-select-sm">
            <option value="">@lang('word.select_sort')</option>
            @foreach(['asc','desc'] as $sort)
                <option value="{{ $sort }}" @if($sort == request()->sort) selected @endif>@lang('word.sort_by_'. $sort)</option>
            @endforeach
        </select>
    </div>
@endif

{{-- 頁面大小 page_size --}}
@if(empty($hideToolbarPageSizeFiller))
    <div class="col-6 col-md-auto mb-3 ms-0">
        <select name="page_size" class="form-select form-select-sm">
            <option value="">@lang('word.select_count')</option>
            @foreach($page_siezes ?? [10,20,30,50,80,100,150,200,500] as $page_size)
                <option value="{{ $page_size }}" @if($page_size == request()->page_size) selected @endif>{{ $page_size }}</option>
            @endforeach
        </select>
    </div>
@endif