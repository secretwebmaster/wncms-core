@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('analytics.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('backend.common.default_toolbar_filters', [
                    'hideToolbarWebsiteFiller' => true,
                    'hideToolbarPageSizeFiller' => true,
                ])

                {{-- Add custom toolbar item here --}}

                {{-- exampleItem for example_item --}}
                {{-- @if(!empty($exampleItems))
                    <div class="col-6 col-md-auto mb-3 ms-0">
                        <select name="example_item_id" class="form-select form-select-sm">
                            <option value="">@lang('word.select')@lang('word.example_item')</option>
                            @foreach($exampleItems as $exampleItem)
                                <option value="{{ $exampleItem->id }}" @if($exampleItem->id == request()->example_item_id) selected @endif>{{ $exampleItem->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif --}}

                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('word.submit')">
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="d-flex flex-wrap">
                @foreach([] as $show)
                    <div class="mb-3 ms-0">
                        <div class="form-check form-check-sm form-check-custom me-2">
                            <input class="form-check-input model_index_checkbox" name="{{ $show }}" type="checkbox" @if(request()->{$show}) checked @endif/>
                            <label class="form-check-label fw-bold ms-1">@lang('word.' . $show)</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    </div>

    {{-- WNCMS toolbar buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">
            {{-- Create + Bilk Create + Clone + Bulk Delete --}}
            @include('backend.common.default_toolbar_buttons', [
                'model_prefix' => 'analytics',
            ])
        </div>
    </div>

    {{-- Website traffic --}}
    <div class="card card-flush rounded overflow-hidden mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-nowrap mb-0">

                    {{-- thead --}}
                    <thead class="table-dark">
                        <tr class="fw-bold gs-0">
                            {{-- <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th> --}}
                            <th>@lang('word.id')</th>
                            <td>@lang('word.domain')</td>
                            <td>@lang('word.today')</td>
                            <td>@lang('word.yesterday')</td>
                            <td>@lang('word.recent_week')</td>
                            <td>@lang('word.recent_month')</td>
                            <td>@lang('word.recent_year')</td>
                            <td>@lang('word.total')</td>
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($websites as $website)
                            @if(!empty($websiteAnalyticsDataSets[$website->domain]))
                                <tr>
                                    {{-- <td>
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $website->id }}"/>
                                        </div>
                                    </td> --}}
                                    <td>{{ $website->id }}</td>
                                    <td>@include('common.table_url', ['url' => $website->domain])</td>
                                    <td>{{ $websiteAnalyticsDataSets[$website->domain]['today'] ?? 0 }}</td>
                                    <td>{{ $websiteAnalyticsDataSets[$website->domain]['yesterday'] ?? 0 }}</td>
                                    <td>{{ $websiteAnalyticsDataSets[$website->domain]['recent_week'] ?? 0 }}</td>
                                    <td>{{ $websiteAnalyticsDataSets[$website->domain]['recent_month'] ?? 0 }}</td>
                                    <td>{{ $websiteAnalyticsDataSets[$website->domain]['recent_year'] ?? 0 }}</td>
                                    <td>{{ $websiteAnalyticsDataSets[$website->domain]['total'] ?? 0 }}</td>
                                <tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
@endsection