@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    <div class="card card-flush">

        {{-- 工具欄 --}}
        <div class="card-header align-items-center pt-5 gap-2 gap-md-5">

            <div class="card-title">
                {{-- 搜索 --}}
                <form action="{{ route('analytics.traffic') }}">
                    <div class="d-flex align-items-center position-relative my-1">
                        <!-- path: icons/duotune/general/gen021. -->
                        <span class="svg-icon svg-icon-1 position-absolute ms-4">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
                                <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
                            </svg>
                        </span>
                        <input type="text" name="keyword" value="{{ request()->keyword }}" data-kt-ecommerce-order-filter="search" class="form-control form-control-sm form-control form-control-sm-solid w-250px ps-14" placeholder="@lang('word.search')" />

                        {{-- 網站列表 --}}
                        <div class="w-200px ms-2">
                            <select name="website" class="form-select form-select-sm form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Status" data-kt-ecommerce-order-filter="status">
                                <option value="">@lang('word.select_website')</option>
                                @foreach($websites as $website)
                                    <option value="{{ $website->id }}" @if($website->id == request()->website) selected @endif>{{ $website->domain }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="submit" class="btn btn-sm btn-primary ms-2 fw-bold" value="@lang('word.submit')">
                    </div>
                </form>

                <div id="kt_ecommerce_report_sales_export" class="d-none"></div>
            </div>

            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                {{-- 輸入框 --}}
                {{-- <input class="form-control form-control-sm form-control form-control-sm-solid w-100 mw-250px" placeholder="Pick date range" id="kt_ecommerce_report_sales_daterangepicker" /> --}}
                {{-- 下拉菜單 --}}
                {{-- <button type="button" class="btn btn-primary fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">下拉菜單</button>
                <div id="kt_ecommerce_report_sales_export_menu" class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4" data-kt-menu="true">
                    <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-kt-ecommerce-export="copy">子項目</a></div>
                    <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-kt-ecommerce-export="excel">子項目</a></div>
                    <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-kt-ecommerce-export="csv">子項目</a></div>
                    <div class="menu-item px-3"><a href="#" class="menu-link px-3" data-kt-ecommerce-export="pdf">子項目</a></div>
                </div> --}}
            </div>
            
  
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle text-nowrap mb-0" id="kt_ecommerce_report_sales_table">
                    <thead class="table-dark">
                        <tr class="fw-bold gs-0">
                            <th>@lang('word.id')</th>
                            <th>@lang('word.datetime')</th>
                            <th>@lang('word.website')</th>
                            <th>@lang('word.link')</th>
                            <th>@lang('word.ip')</th>
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($traffics as $traffic)
                        <tr>
                            <td>{{ $traffic->id }}</td>
                            <td>{{ $traffic->created_at }}</td>
                            <td>{{ $traffic->website->domain ?? '' }}</td>
                            <td>{{ $traffic->link->site_url ?? '' }}</td>
                            <td>{{ $traffic->ip }}</td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <div class="my-10">{{ $traffics->appends(request()->input())->links() }}</div>
@endsection