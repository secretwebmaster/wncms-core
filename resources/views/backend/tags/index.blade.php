@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('tags.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('backend.common.default_toolbar_filters')

                {{-- tagType --}}
                @if(!empty($tagTypes))
                    <div class="col-6 col-md-auto mb-3 ms-0">
                        <select name="type" class="form-select form-select-sm">
                            <option value="all">@lang('word.tag_type')</option>
                            @foreach($tagTypes as $tagType)
                                <option value="{{ $tagType['slug'] }}" @if($tagType['slug'] == request()->type) selected @endif>{{ $tagType['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-6 col-md-auto mb-3 ms-0 ms-md-2">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('word.submit')">
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="d-flex flex-wrap">
                @foreach(['show_detail', 'hide_children_tags'] as $show)
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
                'model_prefix' => 'tags',
            ])

            <a href="{{ route('tags.create', ['type' => request()->type]) }}" class="btn btn-sm btn-info fw-bold mb-1">@lang('word.create_current_tag_type')</a>
            <a href="{{ route('tags.bulk_create') }}" class="btn btn-sm btn-info fw-bold mb-1">@lang('word.bulk_create_tag')</a>
            <a href="{{ route('tags.keywords.index') }}" class="btn btn-sm btn-dark fw-bold mb-1">@lang('word.bind_keywords')</a>
       
            {{-- Bulk sync parent --}}
            <button type="button" class="btn btn-sm btn-primary fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_bulk_sync_tag_parent">@lang('word.bulk_sync_tag_parent')</button>
            <div class="modal fade" tabindex="-1" id="modal_bulk_sync_tag_parent">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">@lang('word.bulk_sync_tag_parent')</h3>
                        </div>
            
                        <div class="modal-body">
                            <form id="form_bulk_sync_tag_parent">
                                <label for="" class="form-label">@lang('word.parent_tag')</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">@lang('word.please_select')</option>
                                    @foreach ($allParents as $parent)
                                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                        @include('backend.tags.recursive_children_options', ['children' => $parent->children, 'depth' => 1])
                                    @endforeach
                                </select>
                            </form>
                        </div>
            
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('word.close')</button>
                            <button type="button" class="btn btn-primary"
                                wncms-btn-ajax
                                wncms-get-model-ids
                                wncms-btn-swal
                                data-form="form_bulk_sync_tag_parent"
                                data-original-text="@lang('word.submit')"
                                data-loading-text="@lang('word.loading').."
                                data-success-text="@lang('word.submitted')"
                                data-fail-text="@lang('word.fail_to_submit')"
                                data-route="{{ route('tags.bulk_set_parent') }}"
                                data-method="POST"
                                data-param-column="column"
                            >@lang('word.submit')</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover align-middle text-nowrap mb-0 border border-2 border-dark">
                    <thead class="table-dark">
                        <tr class="text-start fw-bold gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom me-3">
                                    <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>@lang('word.action')</th>
                            <th class="ps-3">@lang('word.tag_id')</th>
                            <th>@lang('word.tag_type')</th>
                            <th>@lang('word.tag_name')</th>
                            <th>@lang('word.slug')</th>
                            <th>@lang('word.order_culumn')</th>
                            <th>@lang('word.model_count')</th>
                            <th>@lang('word.tag_parent')</th>
                            <th>@lang('word.tag_image')</th>
                            <th>@lang('word.tag_icon')</th>
                            <th>@lang('word.created_at')</th>
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($parents as $parent)
                        
                            <tr class="bg-gray-200 @if(request()->keyword && strpos($parent->name, request()->keyword) !== false) bg-light-info fw-bold text-info @endif">
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $parent->id }}"/>
                                    </div>
                                </td>
                                <td>
                                    <a class="btn btn-sm px-2 py-1 btn-primary fw-bold" href="{{ route('tags.create' , ['type' => $parent->type,'parent_id' => $parent->id]) }}">@lang('word.add_children_tag')</a>
                                    <a class="btn btn-sm px-2 py-1 btn-dark fw-bold" href="{{ route('tags.edit' , $parent) }}">@lang('word.edit')</a>
                                    @include('backend.parts.modal_delete' , ['model'=>$parent , 'route' => route('tags.destroy' , $parent)])
                                </td>
                                <td class="ps-3">{{ $parent->id }}</td>
                                <td>@lang('word.' . $parent->type)</td>
                                <td class="mw-200px text-truncate text-info fw-bold" title="{{ $parent->description }}">{{ $parent->name }}</td>
                                <td class="mw-200px text-truncate">{{ $parent->slug }}</td>
                                <td>{{ $parent->order_column }}</td>
                                <td>{{ $parent->models_count }}</td>
                                <td>{{ $parent->parent?->name }}</td>
                                <td>{{ $parent->getFirstMediaUrl('parent_image')}}</td>
                                <td>{{ $parent->icon }} <i class="{{ $parent->icon }}"></i></td>
                                <td>{{ $parent->created_at }}</td>
                            <tr>

                            {{-- Children --}}
                            {{-- 結構與Parent不同，所以由level = 1開始 --}}
                            @if(empty(request()->hide_children_tags))
                                @include('backend.tags.children_tags', ['children' => $parent->children, 'level' => 1])
                            @endif

                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $parents->withQueryString()->links() }}
    </div>

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