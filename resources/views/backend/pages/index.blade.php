@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('pages.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('backend.common.default_toolbar_filters')

                {{-- Add custom toolbar item here --}}

                {{-- Example --}}
                {{-- @if(!empty($example_toolbar_items))
                    <div class="col-6 col-md-auto mb-3 ms-0">
                        <select name="website" class="form-select form-select-sm">
                            <option value="">@lang('word.select_website')</option>
                            @foreach($example_toolbar_items as $example_toolbar_item)
                                <option value="{{ $example_toolbar_item->id }}" @if($example_toolbar_item->id == request()->example_toolbar_item_id) selected @endif>{{ $example_toolbar_item->name }}</option>
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
                @foreach(['show_detail'] as $show)
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
                'model_prefix' => 'pages',
            ])

            <button type="button" class="btn btn-sm btn-primary fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_one_click_create_theme_pages">@lang('word.one_click_create_theme_pages')</button>
            <div class="modal fade" tabindex="-1" id="modal_one_click_create_theme_pages">
                <div class="modal-dialog">
                    <form action="{{ route('pages.create_theme_pages') }}" method="POST">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title">@lang('word.one_click_create_theme_pages')</h3>
                            </div>
                
                            <div class="modal-body">
                                <div class="form-item">
                                    <label for="website_id" class="form-label">@lang('word.website')</label>
                                    <select name="website_id" class="form-select">
                                        <option value="">@lang('word.please_select')</option>
                                        @foreach($websites as $w)
                                        <option value="{{ $w->id }}">{{ $w->domain }}</option>
                                        @endforeach    
                                    </select>
                                </div>
                            </div>
                
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('word.close')</button>
                                <button type="submit" class="btn btn-primary fw-bold">@lang('word.submit')</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-nowrap mb-0">
                    <thead class="table-dark">
                        <tr class="fw-bold gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>@lang('word.action')</th>
                            <th>@lang('word.id')</th>
                            <th>@lang('word.slug')</th>
                            <th>@lang('word.user')</th>
                            <th>@lang('word.website')</th>
                            <th>@lang('word.status')</th>
                            <th>@lang('word.thumbnail')</th>
                            <th>@lang('word.title')</th>
                            <th>@lang('word.visibility')</th>
                            <th>@lang('word.template')</th>
                            <th>@lang('word.attribute')</th>
                            <th>@lang('word.remark')</th>
                            <th>@lang('word.is_locked')</th>
                            <th>@lang('word.created_at')</th>

                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($pages as $page)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $page->id }}"/>
                                </div>
                            </td>
                            <td>
                                <a class="btn btn-sm px-2 py-1 btn-dark fw-bold" href="{{ route('pages.edit' , $page) }}">@lang('word.edit')</a>
                                <a class="btn btn-sm px-2 py-1 btn-info fw-bold" href="{{ route('pages.clone' , $page) }}">@lang('word.clone')</a>
                                @include('backend.parts.modal_delete' , ['model'=>$page , 'route' => route('pages.destroy' , $page)])
                            </td>
                            <td>{{ $page->id }}</td>
                            <td>{{ $page->slug }}</td>
                            <td>{{ $page->user?->username }}</td>
                            <td>{{ $page->website?->domain }}</td>
                            <td>@include('common.table_status', ['model' => $page])</td>
                            <td><img class="lazyload mw-100px rounded" src="{{ $page->thumbnail }}" alt=""></td>
                            <td class="mw-400px text-truncate"><a href="{{ $wncms->getRoute('frontend.pages.single', ['slug' => $page->slug], false, $page->website->domain) }}" target="_blank" title="{{ $page->title }}">{{ $page->title }}</a></td>
                            <td>{{ $page->visibility }}</td>
                            <td>{{ $page->template }}</td>
                            <th title="@foreach(json_decode($page->attribute, true) ?? [] as $key => $value){{ $key }}: {{ $value }}&#10;@endforeach">@if($page->attribute && $page->attribute != "[]")@lang('word.hover_to_view')@endif</td>
                            <td>{{ $page->remark }}</td>
                            <td>{{ $page->is_locked }}</td>
                            <td>{{ $page->created_at }}</td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>


            </div>
        </div>
    </div>

    {{-- Pagination --}}
    {{-- <div class="mt-5">
        {{ $pages->withQueryString()->links() }}
    </div> --}}

@endsection

@push('foot_js')
    <script>
        $('.model_index_checkbox').on('change', function(){
            if($(this).is(':checked')){
                $(this).val('1');
            } else {
                $(this).val('0');
            }
            $(this).closest('form').submit();
        })
    </script>
@endpush