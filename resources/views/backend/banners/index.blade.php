@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('banners.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                <div class="col-6 col-md-auto mb-3 ms-0 ms-md-2">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('wncms::word.submit')">
                </div>
            </div>
        </form>
    </div>


    {{-- WNCMS toolbar buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">
            {{-- Create + Bilk Create + Clone + Bulk Delete --}}
            @include('wncms::backend.common.default_toolbar_buttons', [
                'model_prefix' => 'banners',
            ])
        </div>
    </div>

    {{-- Notice --}}
    <div class="alert alert-info">@lang('wncms::word.we_suggest_to_use_banner_only_on_self_events')</div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $banners])


    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-nowrap mb-0 border border-2 border-dark">
                    <thead class="table-dark">
                        <tr class="fw-bold gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>@lang('wncms::word.action')</th>
                            <th>@lang('wncms::word.id')</th>
                            <th>@lang('wncms::word.website')</th>
                            <th>@lang('wncms::word.status')</th>
                            <th>@lang('wncms::word.image')</th>
                            <th>@lang('wncms::word.url')</th>
                            <th>@lang('wncms::word.position')</th>
                            <th>@lang('wncms::word.order')</th>
                            {{-- <th>@lang('wncms::word.click')</th> --}}
                            <th>@lang('wncms::word.contact')</th>
                            <th>@lang('wncms::word.remark')</th>
                            <th>@lang('wncms::word.expired_at')</th>
                            <th>@lang('wncms::word.created_at')</th>
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($banners as $banner)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $banner->id }}"/>
                                </div>
                            </td>
                            <td>
                                <a class="btn btn-sm px-2 py-1 btn-dark fw-bold" href="{{ route('banners.edit' , $banner) }}">@lang('wncms::word.edit')</a>
                                <a class="btn btn-sm px-2 py-1 btn-info fw-bold" href="{{ route('banners.clone' , $banner) }}">@lang('wncms::word.clone')</a>
                                @include('wncms::backend.parts.modal_delete' , ['model'=>$banner , 'route' => route('banners.destroy' , $banner)])
                            </td>
                            <td>{{ $banner->id }}</td>
                            <td>{{ $banner->website->domain }}</td>
                            <td>
                                @if($banner->status == 'active')
                                <span class="badge bg-success">@lang('wncms::word.' . $banner->status)</span>
                                @elseif($banner->status == 'suspended')
                                <span class="badge bg-danger">@lang('wncms::word.' . $banner->status)</span>
                                @else
                                <span class="badge bg-warning">@lang('wncms::word.' . $banner->status)</span>
                                @endif
                            </td>
                            <td>
                                <img src="{{ $banner->thumbnail }}" class="mh-40px">
                            </td>
                            <td><a href="{{ wncms_add_https($banner->url) }}" target="_blank">{{ $banner->url }}</a></td>
                            <td>
                                @foreach($banner->positions as $position)
                                <span class="badge badge-info">@lang('wncms::word.' . $position)</span>
                                @endforeach
                            </td>
                            <td>{{ $banner->order }}</td>
                            {{-- <td>use visits</td> --}}
                            <td>{{ $banner->contact }}</td>
                            <td>{{ $banner->remark }}</td>
                            <td><span class="@if($banner->isExpired()) text-danger @endif">{{ !empty($banner->expired_at) ? $banner->expired_at->format('Y-m-d') : ''  }}</span></td>
                            <td>{{ $banner->created_at }}</td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $banners])

@endsection