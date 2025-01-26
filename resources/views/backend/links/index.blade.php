@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('links.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                {{-- Add custom toolbar item here --}}

                {{-- parentLinkCategory for link_category --}}
                @if(!empty($parentLinkCategories))
                    <div class="col-6 col-md-auto mb-3 ms-0">
                        <select name="link_category_id" class="form-select form-select-sm">
                            <option value="">@lang('wncms::word.select')@lang('wncms::word.link_category')</option>
                            @foreach($parentLinkCategories as $parentLinkCategory)
                                <option value="{{ $parentLinkCategory->id }}" @if($parentLinkCategory->id == request()->link_category_id) selected @endif>{{ $parentLinkCategory->name }}</option>
                                @foreach($parentLinkCategory->children as $childLinkCategory)
                                    <option value="{{ $childLinkCategory->id }}" @if($childLinkCategory->id == request()->link_category_id) selected @endif>├─ {{ $childLinkCategory->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold mb-1" value="@lang('wncms::word.submit')">
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="d-flex flex-wrap">
                @foreach(['show_detail'] as $show)
                    <div class="mb-3 ms-0">
                        <div class="form-check form-check-sm form-check-custom me-2">
                            <input class="form-check-input model_index_checkbox" name="{{ $show }}" type="checkbox" @if(request()->{$show}) checked @endif/>
                            <label class="form-check-label fw-bold ms-1">@lang('wncms::word.' . $show)</label>
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
            @include('wncms::backend.common.default_toolbar_buttons', [
                'model_prefix' => 'links',
            ])
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $links])

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-nowrap mb-0">

                    {{-- thead --}}
                    <thead class="table-dark">
                        <tr class="text-start fw-bold gs-0">
                            {{-- Checkbox --}}
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom me-3">
                                    <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>@lang('wncms::word.action')</th>
                            <th>@lang('wncms::word.id')</th>
                            <th>@lang('wncms::word.status')</th>
                            <th>@lang('wncms::word.thumbnail')</th>
                            <th>@lang('wncms::word.name')</th>
                            <th>@lang('wncms::word.category')</th>
                            <th>@lang('wncms::word.url')</th>
                            <th>@lang('wncms::word.clicks')</th>
                            <th>@lang('wncms::word.order')</th>
                            <th>@lang('wncms::word.remark')</th>
                            <th>@lang('wncms::word.expired_at')</th>
                            <th>@lang('wncms::word.created_at')</th>
                            
                            @if(request()->show_detail)
                            <th>@lang('wncms::word.slug')</th>
                            <th>@lang('wncms::word.tracking_code')</th>
                            <th>@lang('wncms::word.slogan')</th>
                            <th>@lang('wncms::word.description')</th>
                            <th>@lang('wncms::word.color')</th>
                            <th>@lang('wncms::word.background')</th>
                            <th>@lang('wncms::word.contact')</th>
                            <th>@lang('wncms::word.is_pinned')</th>
                            <th>@lang('wncms::word.is_recommended')</th>
                            <th>@lang('wncms::word.hit_at')</th>
                            <th>@lang('wncms::word.updated_at')</th>
                            @endif
                            
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($links as $link)
                            <tr>
                                {{-- Checkboxes --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $link->id }}"/>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('links.edit' , $link) }}">@lang('wncms::word.edit')</a>
                                    @include('wncms::backend.parts.modal_delete' , ['model'=>$link , 'route' => route('links.destroy' , $link), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $link->id }}</td>
                                <td>@include('wncms::common.table_status', ['model' => $link])</td>
                                <td>
                                    <img class="w-20px h-20px" src="{{ $link->thumbnail ?: asset('wncms/images/placeholders/upload.png') }}" alt="">
                                </td>
                                <td>{{ $link->name }}</td>
                                <td>{{ $link->tagsWithType('link_category')->implode('name', ',') }}</td>
                                <td>{{ $link->url }}</td>
                                <td>{{ $link->clicks }}</td>
                                <td>{{ $link->order }}</td>
                                <td>{{ $link->remark }}</td>
                                <td>{{ $link->expired_at }}</td>
                                <td>{{ $link->created_at }}</td>
                                
                                @if(request()->show_detail)
                                <td>{{ $link->slug }}</td>
                                <td>{{ $link->tracking_code }}</td>
                                <td>{{ $link->slogan }}</td>
                                <td>{{ $link->description }}</td>
                                <td><span style="color:{{ $link->color }};">{{ $link->color }}</span></td>
                                <td><span style="color:{{ $link->background }};">{{ $link->background }}</span></td>
                                <td>{{ $link->contact }}</td>
                                <td>@include('wncms::common.table_is_active', ['model'=>$link, 'active_column' => 'is_pinned'])</td>
                                <td>@include('wncms::common.table_is_active', ['model'=>$link, 'active_column' => 'is_recommended'])</td>
                                <td>{{ $link->hit_at }}</td>
                                <td>{{ $link->updated_at }}</td>
                                @endif
                                
                            <tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $links])

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $links->withQueryString()->links() }}
    </div>

@endsection

@push('foot_js')
    <script>
        //修改checkbox時直接提交
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