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
                @if (!empty($parentLinkCategories))
                    <div class="col-6 col-md-auto mb-3 ms-0">
                        <select name="link_category_id" class="form-select form-select-sm">
                            <option value="">@lang('wncms::word.select')@lang('wncms::word.link_category')</option>
                            @foreach ($parentLinkCategories as $parentLinkCategory)
                                <option value="{{ $parentLinkCategory->id }}" @if ($parentLinkCategory->id == request()->link_category_id) selected @endif>{{ $parentLinkCategory->name }}</option>
                                @foreach ($parentLinkCategory->children as $childLinkCategory)
                                    <option value="{{ $childLinkCategory->id }}" @if ($childLinkCategory->id == request()->link_category_id) selected @endif>├─ {{ $childLinkCategory->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('wncms::word.submit')">
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="d-flex flex-wrap">
                @foreach (['show_detail'] as $show)
                    <div class="mb-3 ms-0">
                        <div class="form-check form-check-sm form-check-custom me-2">
                            <input class="form-check-input model_index_checkbox" name="{{ $show }}" type="checkbox" @if (request()->{$show}) checked @endif />
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

            <button id="btn-bulk-update-link" class="btn btn-sm btn-dark fw-bold mb-1">@lang('wncms::word.bulk_edit_order')</button>

            @include('wncms::backend.common.btn_bulk_update_model', [
                'model' => 'Link',
                'btnText' => __('wncms::word.set_active'),
                'swal' => true,
                'fieldColumn' => 'status',
                'fieldValue' => 'active',
            ])

            @include('wncms::backend.common.btn_bulk_update_model', [
                'model' => 'Link',
                'btnText' => __('wncms::word.set_inactive'),
                'swal' => true,
                'fieldColumn' => 'status',
                'fieldValue' => 'inactive',
            ])

            @include('wncms::backend.common.btn_bulk_update_model', [
                'model' => 'Link',
                'btnText' => __('wncms::word.set_is_pinned'),
                'swal' => true,
                'fieldColumn' => 'is_pinned',
                'fieldValue' => '1',
            ])

            @include('wncms::backend.common.btn_bulk_update_model', [
                'model' => 'Link',
                'btnText' => __('wncms::word.set_not_pinned'),
                'swal' => true,
                'fieldColumn' => 'is_pinned',
                'fieldValue' => '0',
            ])

            @include('wncms::backend.common.btn_bulk_update_model', [
                'model' => 'Link',
                'btnText' => __('wncms::word.set_is_recommended'),
                'swal' => true,
                'fieldColumn' => 'is_recommended',
                'fieldValue' => '1',
            ])

            @include('wncms::backend.common.btn_bulk_update_model', [
                'model' => 'Link',
                'btnText' => __('wncms::word.set_is_not_recommended'),
                'swal' => true,
                'fieldColumn' => 'is_recommended',
                'fieldValue' => '0',
            ])

            {{-- bulk update link tags --}}
            <button type="button" class="btn btn-sm btn-info fw-bold mb-1" data-bs-toggle="modal" data-bs-target="#modal_update_link_tag">@lang('wncms::word.handling_link_tags')</button>
            <div class="modal fade" tabindex="-1" id="modal_update_link_tag">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="form_bulk_sync_tags" action="{{ route('links.bulk_sync_tags') }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h3 class="modal-title">@lang('wncms::word.handling_link_tags')</h3>
                            </div>

                            <div class="modal-body">
                                <div class="form-item mb-3">
                                    <label for="" class="form-label">@lang('wncms::word.action')</label>
                                    <select class="form-select" name="action">
                                        @foreach (['attach', 'detach', 'sync'] as $action)
                                            <option value="{{ $action }}">@lang('wncms::word.' . $action)</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Category --}}
                                <div class="form-item mb-3">
                                    <label class="form-label">@lang('wncms::word.category')</label>
                                    <input id="tagify_link_category" class="form-control form-control-sm p-0" name="link_categories" />

                                    @php
                                        $categories = wncms()
                                            ->tag()
                                            ->getList(['tag_type' => 'link_category'])
                                            ->map(function ($tag) {
                                                return ['value' => $tag->id, 'name' => $tag->name];
                                            })
                                            ->toArray();
                                    @endphp

                                    @push('foot_js')
                                        <script type="text/javascript">
                                            window.addEventListener('DOMContentLoaded', (event) => {
                                                var input = document.querySelector("#tagify_link_category");
                                                var whitelist = @json($categories);

                                                // Initialize Tagify
                                                tagify = new Tagify(input, {
                                                    whitelist: whitelist,
                                                    enforceWhitelist: false,
                                                    skipInvalid: true,
                                                    duplicates: false,
                                                    tagTextProp: 'name',
                                                    maxTags: 999,
                                                    dropdown: {
                                                        maxItems: 100,
                                                        mapValueTo: 'name',
                                                        classname: "tagify__inline__suggestions",
                                                        enabled: 0,
                                                        closeOnSelect: false,
                                                        searchKeys: ['name', 'value'],
                                                    },
                                                });
                                            });
                                        </script>
                                    @endpush
                                </div>

                                {{-- Tag --}}
                                <div class="form-item mb-3">
                                    <label class="form-label">@lang('wncms::word.tag')</label>
                                    <input id="tagify_link_tag" class="form-control form-control-sm p-0" name="link_tags" />

                                    @php
                                        $tags = wncms()
                                            ->tag()
                                            ->getList(['tag_type' => 'link_tag'])
                                            ->map(function ($tag) {
                                                return ['value' => $tag->id, 'name' => $tag->name];
                                            })
                                            ->toArray();
                                    @endphp

                                    @push('foot_js')
                                        <script type="text/javascript">
                                            window.addEventListener('DOMContentLoaded', (event) => {
                                                var input = document.querySelector("#tagify_link_tag");
                                                var whitelist = @json($tags);

                                                // Initialize Tagify
                                                tagify = new Tagify(input, {
                                                    whitelist: whitelist,
                                                    enforceWhitelist: false,
                                                    skipInvalid: true,
                                                    duplicates: false,
                                                    tagTextProp: 'name',
                                                    maxTags: 999,
                                                    dropdown: {
                                                        maxItems: 100,
                                                        mapValueTo: 'name',
                                                        classname: "tagify__inline__suggestions",
                                                        enabled: 0,
                                                        closeOnSelect: false,
                                                        searchKeys: ['name', 'value'],
                                                    },
                                                });
                                            });
                                        </script>
                                    @endpush
                                </div>

                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
                                <button type="button" class="btn btn-primary"
                                    wncms-btn-ajax
                                    wncsm-btn-swal
                                    wncms-get-model-ids
                                    data-model="post"
                                    data-route="{{ route('links.bulk_sync_tags') }}"
                                    data-method="post"
                                    data-form="form_bulk_sync_tags"
                                    date-original-text="@lang('wncms::word.submit')"
                                    data-submitted-btn-text="@lang('wncms::word.loading')">@lang('wncms::word.submit')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $links])

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-xs table-hover table-bordered align-middle text-nowrap mb-0">

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
                            <th>@lang('wncms::word.is_recommended')</th>
                            <th>@lang('wncms::word.icon')</th>
                            <th>@lang('wncms::word.thumbnail')</th>
                            <th>@lang('wncms::word.name')</th>
                            <th>@lang('wncms::word.category')</th>
                            <th>@lang('wncms::word.clicks')</th>
                            <th>@lang('wncms::word.order')</th>
                            <th>@lang('wncms::word.url')</th>
                            <th>@lang('wncms::word.remark')</th>
                            <th>@lang('wncms::word.expired_at')</th>
                            <th>@lang('wncms::word.created_at')</th>

                            @if (request()->show_detail)
                                <th>@lang('wncms::word.slug')</th>
                                <th>@lang('wncms::word.tracking_code')</th>
                                <th>@lang('wncms::word.slogan')</th>
                                <th>@lang('wncms::word.description')</th>
                                <th>@lang('wncms::word.color')</th>
                                <th>@lang('wncms::word.background')</th>
                                <th>@lang('wncms::word.contact')</th>
                                <th>@lang('wncms::word.is_pinned')</th>
                                <th>@lang('wncms::word.hit_at')</th>
                                <th>@lang('wncms::word.updated_at')</th>
                            @endif

                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach ($links as $link)
                            <tr data-link-id="{{ $link->id }}">
                                {{-- Checkboxes --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $link->id }}" />
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('links.edit', $link) }}">@lang('wncms::word.edit')</a>
                                    @include('wncms::backend.parts.modal_delete', ['model' => $link, 'route' => route('links.destroy', $link), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $link->id }}</td>
                                <td>@include('wncms::common.table_status', ['model' => $link])</td>
                                <td>@include('wncms::common.table_is_active', ['model' => $link, 'active_column' => 'is_recommended'])</td>
                                <td>
                                    <img class="w-20px h-20px" src="{{ $link->icon ?: asset('wncms/images/placeholders/upload.png') }}" alt="">
                                </td>
                                <td>
                                    <img class="h-20px" src="{{ $link->thumbnail ?: asset('wncms/images/placeholders/upload.png') }}" alt="">
                                </td>
                                <td>{{ $link->name }}</td>
                                <td>{{ $link->tagsWithType('link_category')->implode('name', ',') }}</td>
                                <td>
                                    @if (!empty($clickModel))
                                        <a href="{{ route('clicks.index', ['link_id' => $link->id]) }}">{{ $link->clicks_count }}</a>
                                        <span>({{ $link->clicks ?? 0 }})</span>
                                    @else
                                        <span>{{ $link->clicks ?? 0 }}</span>
                                    @endif
                                </td>
                                <td><input type="number" class="link-sort-input" value="{{ $link->sort }}"></td>
                                {{-- url --}}
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="text" class="link-url-input min-w-200px" value="{{ $link->url }}">
                                        <a href="{{ $link->url }}" target="_blank" class="text-primary" title="{{ $link->url }}"><i class="fa fa-external-link-alt"></i></a>
                                    </div>
                                </td>
                                <td>{{ $link->remark }}</td>
                                <td>{{ $link->expired_at }}</td>
                                <td>{{ $link->created_at }}</td>
                                @if (request()->show_detail)
                                    <td>{{ $link->slug }}</td>
                                    <td>{{ $link->tracking_code }}</td>
                                    <td>{{ $link->slogan }}</td>
                                    <td>{{ $link->description }}</td>
                                    <td><span style="color:{{ $link->color }};">{{ $link->color }}</span></td>
                                    <td><span style="color:{{ $link->background }};">{{ $link->background }}</span></td>
                                    <td>{{ $link->contact }}</td>
                                    <td>@include('wncms::common.table_is_active', ['model' => $link, 'active_column' => 'is_pinned'])</td>
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
        $('.model_index_checkbox').on('change', function() {
            if ($(this).is(':checked')) {
                $(this).val('1');
            } else {
                $(this).val('0');
            }
            $(this).closest('form').submit();
        })
    </script>

    {{-- update sort and url --}}
    <script>
        $('#btn-bulk-update-link').click(function() {
            let linkUpdates = [];

            $('tr[data-link-id]').each(function() {
                let linkId = $(this).data('link-id');
                let sortValue = $(this).find('.link-sort-input').val();
                let urlValue = $(this).find('.link-url-input').val();

                if (linkId) {
                    linkUpdates.push({
                        id: linkId,
                        sort: sortValue,
                        url: urlValue
                    });
                }
            });

            $.ajax({
                url: "{{ route('links.bulk_update') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    data: linkUpdates
                },
                success: function(response) {
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });
    </script>
@endpush
