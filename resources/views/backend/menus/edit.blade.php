@extends('wncms::layouts.backend')
@push('head_css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.css" />

    <style>
        /* button.dd-collapse {
                display: inline-block;
                height: 35px;
                background: #000000;
                margin: 0;
                width: 5px;
            } */
        .accordion-button::after {
            background-color: #fff;
            /* icon color */
            -webkit-mask: var(--wncms-accordion-button-active-icon) center / contain no-repeat;
            mask: var(--wncms-accordion-button-active-icon) center / contain no-repeat;
            background-image: none !important;
        }

        .dd-handle {
            display: flex;
            height: 30px;
            margin: 5px 0;
            padding: 5px 10px;
            color: #333;
            text-decoration: none;
            font-weight: 700;
            border: 1px solid #ccc;
            background: #fafafa;
            border-radius: 3px;
            box-sizing: border-box;
            align-items: center;
            justify-content: space-between;
        }
    </style>
@endpush

@section('content')
    @include('wncms::backend.parts.message')

    <div class="row">
        <div class="col-12 col-md-4">
            <div class="accordion mb-5" id="wncms_accordion">

                {{-- Websites --}}
                {{-- <div class="mb-3">
                <select name="website" class="form-select" disabled>
                    @foreach ($websites as $w)
                    <option value="{{ $w->id }}" @if ($menu->website?->id == $w->id) selected @endif>{{ $w->domain }}</option>
                    @endforeach
                </select>
            </div> --}}

                {{-- Tag groups --}}
                @foreach ($tagTypeArr as $tagTypeName => $meta)
                    @php
                        $modelClass = $meta['model'];
                        $packageId = $meta['package'];
                        $tags = $meta['tags'];

                        // tag type label (商品標籤)
                        $typeLabel = wncms()->tag()->getTagTypeLabel($modelClass, $tagTypeName);
                    @endphp

                    <div class="accordion-item" id="accordion_{{ $tagTypeName }}">

                        {{-- Accordion title --}}
                        <h2 class="accordion-header" id="wncmsAccordionHeader{{ $tagTypeName }}">
                            <button class="accordion-button collapsed fw-bold text-gray-100 bg-dark py-3"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#menuOption{{ $tagTypeName }}"
                                aria-expanded="true"
                                aria-controls="menuOption{{ $tagTypeName }}">
                                {{ $typeLabel }}
                            </button>
                        </h2>

                        {{-- Accordion content --}}
                        <div id="menuOption{{ $tagTypeName }}"
                            class="menu-options accordion-collapse collapse"
                            aria-labelledby="wncmsAccordionHeader{{ $tagTypeName }}"
                            data-bs-parent="#wncms_accordion">

                            <div class="accordion-body white-space-nowrap">

                                {{-- Add to menu --}}
                                <div class="mb-3">
                                    <button class="btn btn-sm btn-secondary w-100 fw-bold add_to_menu">
                                        @lang('wncms::word.add_to_menu')
                                    </button>
                                </div>

                                {{-- Tag items --}}
                                <div class="form-group mh-500px overflow-scroll text-truncate">
                                    <div class="row mw-100">
                                        @include('wncms::backend.menus.children_tags', [
                                            'children' => $tags,
                                            'level' => 0,
                                        ])
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>
                @endforeach

                {{-- Pages --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="wncms_accordion_page">
                        <button class="accordion-button collapsed fw-bold text-gray-100 bg-dark py-3" type="button" data-bs-toggle="collapse" data-bs-target="#menu_option_page" aria-expanded="true" aria-controls="menu_option_page">
                            @lang('wncms::word.custom_page')
                        </button>
                    </h2>
                    <div id="menu_option_page" class="menu_options accordion-collapse collapse" aria-labelledby="wncms_accordion_page" data-bs-parent="#wncms_accordion">
                        <div class="accordion-body mh-500px overflow-scroll white-space-nowrap">
                            <div class="form-group">
                                <div class="row">

                                    {{-- From Page model --}}
                                    @foreach (wncms()->page()->getList() as $page)
                                        <div class="col-6">
                                            <div class="form-check form-check-sm form-check-custom form-check-solid mb-2">
                                                <input class="form-check-input" type="checkbox" data-id="{{ $page->id }}" data-name="{{ $page->title }}" data-type="page" data-model-type="page" data-model-id="{{ $page->id }}" id="checkbox_page_{{ $page->id }}">
                                                <label class="form-check-label small" for="checkbox_page_{{ $page->id }}">{{ $page->title }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div><button class="btn btn-sm btn-secondary w-100 mt-3 fw-bold add_to_menu">@lang('wncms::word.add_to_menu')</button></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Theme pages --}}
                @php
                    // Get all themes
                    $themeIds = wncms()->theme()->getThemes();
                    $themeInfos = wncms()->theme()->getThemeMetas();
                    $themePages = [];
                    foreach ($themeIds as $themeId) {
                        $themePages[$themeId] = wncms()->theme()->getThemePages($themeId);
                    }
                @endphp

                <div class="accordion-item">
                    <h2 class="accordion-header" id="wncms_accordion_theme_page">
                        <button class="accordion-button collapsed fw-bold text-gray-100 bg-dark py-3" type="button" data-bs-toggle="collapse" data-bs-target="#menu_option_theme_page" aria-expanded="true" aria-controls="menu_option_theme_page">
                            @lang('wncms::word.theme_page')
                        </button>
                    </h2>
                    <div id="menu_option_theme_page" class="menu_options accordion-collapse collapse" aria-labelledby="wncms_accordion_theme_page" data-bs-parent="#wncms_accordion">
                        <div class="accordion-body mh-500px overflow-scroll white-space-nowrap">

                            <div class="form-group">
                                <div class="row">
                                    @foreach ($themePages ?? [] as $themeId => $themePageList)
                                        @if (!empty($themePageList))
                                            <div>
                                                <h4>{{ $themeInfos[$themeId]['name'] ?? $themeId }}</h4>
                                            </div>
                                            @foreach ($themePageList as $pageKey => $pageMeta)
                                                @if (!isset($pageMeta['key']) || !isset($pageMeta['route']))
                                                    @continue
                                                @endif
                                                <div class="col-6">
                                                    <div class="form-check form-check-sm form-check-custom form-check-solid mb-2">
                                                        <input class="form-check-input" type="checkbox" data-type="theme_page" data-name="{{ $pageMeta['label'] ??wncms()->theme()->translate($themeId, $pageMeta['key'] ?? '') }}" data-url="{{ $pageMeta['url'] ?? '#' }}" id="checkbox_page_{{ $pageKey }}">
                                                        <label class="form-check-label small" for="checkbox_page_{{ $pageKey }}">{{ $pageMeta['label'] ?? __("{$themeId}::word.{$pageKey}") }} {{ $pageMeta['url'] ?? '' }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <button class="btn btn-sm btn-secondary w-100 mt-3 fw-bold add_theme_page_to_menu">@lang('wncms::word.add_to_menu')</button>
                        </div>
                    </div>
                </div>

                {{-- External Link --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="wncms_accordion_external_link">
                        <button class="accordion-button collapsed fw-bold text-gray-100 bg-dark py-3" type="button" data-bs-toggle="collapse" data-bs-target="#menu_option_external_link" aria-expanded="true" aria-controls="menu_option_external_link">
                            @lang('wncms::word.external_link')
                        </button>
                    </h2>
                    <div id="menu_option_external_link" class="menu_options accordion-collapse collapse show" aria-labelledby="wncms_accordion_external_link" data-bs-parent="#wncms_accordion">
                        <div class="accordion-body mh-500px overflow-scroll white-space-nowrap">
                            <div class="form-group mb-3">
                                <label class="form-label">@lang('wncms::word.title')</label>
                                <input type="text" class="form-control form-control-sm" name="external_link_name" value="">
                            </div>
                            <div class="form-group">
                                <label class="form-label">@lang('wncms::word.url')</label>
                                <input type="text" class="form-control form-control-sm" name="external_link_url" value="" placeholder="https://example.com">
                            </div>
                            <button class="btn btn-sm btn-secondary w-100 mt-3 fw-bold add_external_link_to_menu">@lang('wncms::word.add_to_menu')</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="col-12 col-md-8">
            <form id="form_menu_update" action="{{ route('menus.update', $menu) }}" method="POST">
                @method('PATCH')
                @csrf

                <div class="card">
                    <div class="card-body p-2 p-md-5">
                        {{-- <div class="row">
                        <div class="col-6 mb-5">
                            <label class="form-label fw-bold text-dark">@lang('wncms::word.website')</label>
                            <input type="text" class="form-control form-control-sm" value="{{ $menu->website?->domain }}" disabled>
                        </div>

                        <div class="col-6 mb-5">
                            <label class="form-label fw-bold text-dark">@lang('wncms::word.menu_id')</label>
                            <input type="text" class="form-control form-control-sm" value="{{ $menu->id }}" disabled>
                        </div>
                    </div> --}}

                        <div class="mb-5">
                            <label class="form-label fw-bold text-info">@lang('wncms::word.menu_name')</label>
                            <input type="text" class="form-control form-control-sm" name="name" value="{{ $menu->name }}" required>
                        </div>

                        <label class="form-label fw-bold text-info">@lang('wncms::word.menu_items')</label>
                        <div class="d-flex">
                            <button type="button" class="btn btn-sm btn-dark fw-bold dd_expand_all">@lang('wncms::word.expand_all')</button>
                            <button type="button" class="btn btn-sm btn-dark fw-bold dd_collapse_all ms-1">@lang('wncms::word.collapse_all')</button>
                        </div>
                        <div class="dd" id="nestable-json"></div>
                    </div>
                </div>

                <button class="btn btn-dark w-100 mt-5 update_menu" wncms-btn-loading>@lang('wncms::word.update')</button>
            </form>
        </div>
    </div>

    {{-- Model edit menu item --}}
    <div class="modal fade" tabindex="-1" id="modal_edit_menu_item">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">@lang('wncms::word.edit_menu_item')</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-danger ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <span class="fa fa-times"></span>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <form id="form_edit_menu_item" action="" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="menu_item_name" class="form-label">@lang('wncms::word.menu_id')</label>
                                    <input type="text" class="form-control form-control-solid form-control-sm" id="modal_edit_menu_item_id" name="menu_item_id" readonly>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="menu_item_url" class="form-label">@lang('wncms::word.menu_item_type')</label>
                                    <input type="text" class="form-control form-control-solid form-control-sm" id="modal_edit_menu_item_type" name="menu_item_type" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="menu_item_url" class="form-label">@lang('wncms::word.menu_item_model_type')</label>
                                    <input type="text" class="form-control form-control-solid form-control-sm" id="modal_edit_menu_item_model_type" name="menu_item_model_type" readonly>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="menu_item_url" class="form-label">@lang('wncms::word.menu_item_model_id')</label>
                                    <input type="text" class="form-control form-control-solid form-control-sm" id="modal_edit_menu_item_model_id" name="menu_item_model_id" readonly>
                                </div>
                            </div>
                        </div>

                        {{-- Lang --}}
                        <div class="row">
                            @foreach (wncms()->locale()->getSupportedLocales() as $locale_key => $locale)
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="menu_item_name" class="form-label">@lang('wncms::word.menu_item_name') <span class="text-muted">({{ $locale['native'] }})</span></label>
                                        <input type="text" class="form-control form-control-sm modal_edit_menu_item_name" id="modal_edit_menu_item_name" name="menu_item_name[{{ $locale_key }}]">
                                    </div>
                                </div>
                            @endforeach
                            <div class="col-12 mb-3">
                                <button class="btn btn-sm btn-dark fw-bold w-100 btn-fetch-tag-languages" type="button" data-tag-id style="display: none">@lang('wncms::word.fetch_tag_languages')</button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-5">
                                    <label for="menu_item_url" class="form-label">@lang('wncms::word.menu_item_url')</label>
                                    <input type="text" class="form-control form-control-sm" id="modal_edit_menu_item_url" name="menu_item_url">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-5">
                                    <label for="menu_item_order" class="form-label">@lang('wncms::word.order')</label>
                                    <input type="text" class="form-control form-control-sm" id="modal_edit_menu_item_order" name="menu_item_order" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-5">
                                    <label for="menu_item_description" class="form-label">@lang('wncms::word.description')</label>
                                    <input type="text" class="form-control form-control-sm" id="modal_edit_menu_item_description" name="menu_item_description">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-5">
                                    <label for="menu_item_icon" class="form-label">@lang('wncms::word.menu_item_icon')</label>
                                    <input type="text" class="form-control form-control-sm" id="modal_edit_menu_item_icon" name="menu_item_icon">
                                </div>
                                <div class="mb-5">
                                    <label for="menu_item_thumbnail" class="form-label">@lang('wncms::word.thumbnail')</label>
                                    <input type="file" class="form-control" id="modal_edit_menu_item_thumbnail" name="menu_item_thumbnail">
                                </div>
                            </div>

                            <div class="col-6">
                                <label class="form-label">@lang('wncms::word.current_thumbnail')</label>
                                <img id="current_menu_item_thumbnail" class="w-100" src="{{ asset('wncms/images/placeholders/upload.png') }}" alt="">
                            </div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="modal_edit_menu_item_is_new_window" name="menu_item_new_window" value="1">
                            <label class="form-check-label" for="modal_edit_menu_item_is_new_window">@lang('wncms::word.new_window')</label>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('wncms::word.cancel')</button>
                    <button type="button" class="btn btn-primary" id="modal_submit_form_edit_menu_item" wncms-btn-loading>@lang('wncms::word.edit')</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('foot_js')
    <script src="//cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.js"></script>
    <script src="{{ asset('wncms/js/menu.js') . wncms()->addVersion('js') }}"></script>
    <script>
        // Pass PHP data to JavaScript
        window.wncmsMenuRoutes = {
            editMenuItem: '{{ route('menus.edit_menu_item') }}',
            getMenuItem: '{{ route('menus.get_menu_item') }}',
            getTagLanguages: '{{ route('tags.get_languages') }}',
            checkTagsExist: '{{ route('api.v1.tags.exist') }}'
        };

        window.wncmsMenuTranslations = {
            newWindow: '{{ __('wncms::word.new_window') }}',
            failed: '{{ __('wncms::word.failed') }}'
        };

        $(document).ready(function() {
            // Get menu items as JSON
            var current_menu = @json($menu->menu_items()->whereNull('parent_id')->with('children', 'children.children')->orderBy('sort', 'asc')->get()->append('thumbnail'));

            // Initialize menu editor
            initMenuEditor(
                current_menu,
                '{{ wncms()->locale()->getCurrentLocale() }}',
                '{{ wncms()->locale()->getDefaultLocale() }}'
            );
        });
    </script>
@endpush
