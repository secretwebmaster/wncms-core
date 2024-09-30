@extends('layouts.backend')
@push('head_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.css" />
@endpush


@section('content')

@include('backend.parts.message')

<div class="row">
    <div class="col-12 col-md-4">
        <div class="accordion mb-5" id="wncms_accordion">

            {{-- Websites --}}
            {{-- <div class="mb-3">
                <select name="website" class="form-select" disabled>
                    @foreach($websites as $w)
                    <option value="{{ $w->id }}" @if($menu->website?->id == $w->id) selected @endif>{{ $w->domain }}</option>
                    @endforeach
                </select>
            </div> --}}

            {{-- Tags --}}
            @foreach($tag_type_arr as $tag_type_name => $tags)
                <div class="accordion-item" id="accordion_{{ $tag_type_name }}">
                    {{-- Accordion title --}}
                    <h2 class="accordion-header" id="wncms_accordion_header_{{ $tag_type_name }}">
                        <button class="accordion-button fw-bold text-white bg-dark py-3" type="button" data-bs-toggle="collapse" data-bs-target="#menu_option_{{ $tag_type_name }}" aria-expanded="true" aria-controls="menu_option_{{ $tag_type_name }}">
                            @lang('word.'.$tag_type_name)
                        </button>
                    </h2>

                    {{-- Accordion Content --}}
                    <div id="menu_option_{{ $tag_type_name }}" class="menu_options accordion-collapse collapse" aria-labelledby="wncms_accordion_header_{{ $tag_type_name }}" data-bs-parent="#wncms_accordion_header_{{ $tag_type_name }}">
                        
                        <div class="accordion-body white-space-nowrap">
                            {{-- Add to menu button --}}
                            <div class="mb-3">
                                <button class="btn btn-sm btn-secondary w-100 fw-bold add_to_menu">@lang('word.add_to_menu')</button>
                            </div>

                            {{-- <div class="mb-3">
                                <input class="form-control" type="text" name="test" value="test" placeholder="@lang('word.enter_keyword_to_search')">
                            </div> --}}

                            {{-- Tag items --}}
                            <div class="form-group mh-500px overflow-scroll text-truncate">
                                <div class="row mw-100">
                                    @include('backend.menus.children_tags', ['children' => $tags, 'level' => 0])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Pages --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="wncms_accordion_page">
                    <button class="accordion-button fw-bold text-white bg-dark py-3" type="button" data-bs-toggle="collapse" data-bs-target="#menu_option_page" aria-expanded="true" aria-controls="menu_option_page">
                        @lang('word.custom_page')
                    </button>
                </h2>
                <div id="menu_option_page" class="menu_options accordion-collapse collapse" aria-labelledby="wncms_accordion_page" data-bs-parent="#wncms_accordion">
                    <div class="accordion-body mh-500px overflow-scroll white-space-nowrap">
                        <div class="form-group">
                            <div class="row">
                      
                                {{-- From Page model --}}
                                @foreach($menu->website->pages as $page)
                                <div class="col-6">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid mb-2">
                                        <input class="form-check-input" type="checkbox" data-id="{{ $page->id }}" data-name="{{ $page->title }}" data-type="page" data-model-type="page" data-model-id="{{ $page->id }}" id="checkbox_page_{{ $page->id }}">
                                        <label class="form-check-label small" for="checkbox_page_{{ $page->id }}">{{ $page->title }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div><button class="btn btn-sm btn-secondary w-100 mt-3 fw-bold add_to_menu">@lang('word.add_to_menu')</button></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Theme pages --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="wncms_accordion_theme_page">
                    <button class="accordion-button fw-bold text-white bg-dark py-3" type="button" data-bs-toggle="collapse" data-bs-target="#menu_option_theme_page" aria-expanded="true" aria-controls="menu_option_theme_page">
                        @lang('word.theme_page')
                    </button>
                </h2>
                <div id="menu_option_theme_page" class="menu_options accordion-collapse collapse" aria-labelledby="wncms_accordion_theme_page" data-bs-parent="#wncms_accordion">
                    <div class="accordion-body mh-500px overflow-scroll white-space-nowrap">

                        <div class="form-group">
                            <div class="row">
                                @foreach(config('theme.' . $menu->website?->theme . '.pages') ?? [] as $themePageName => $themePageUrl)
                                    <div class="col-6">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid mb-2">
                                            <input class="form-check-input" type="checkbox" data-type="theme_page" data-name="{{ $themePageName }}" data-url="{{ $themePageUrl }}">
                                            <label class="form-check-label small" for="checkbox_page_aaa">{{ $themePageName }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <button class="btn btn-sm btn-secondary w-100 mt-3 fw-bold add_theme_page_to_menu">@lang('word.add_to_menu')</button>
                    </div>
                </div>
            </div>

            {{-- External Link --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="wncms_accordion_external_link">
                    <button class="accordion-button fw-bold text-white bg-dark py-3" type="button" data-bs-toggle="collapse" data-bs-target="#menu_option_external_link" aria-expanded="true" aria-controls="menu_option_external_link">
                        @lang('word.external_link')
                    </button>
                </h2>
                <div id="menu_option_external_link" class="menu_options accordion-collapse collapse show" aria-labelledby="wncms_accordion_external_link" data-bs-parent="#wncms_accordion">
                    <div class="accordion-body mh-500px overflow-scroll white-space-nowrap">
                        <div class="form-group mb-3">
                            <label class="form-label">@lang('word.title')</label>
                            <input type="text" class="form-control form-control-sm" name="external_link_name" value="">
                        </div>
                        <div class="form-group">
                            <label class="form-label">@lang('word.url')</label>
                            <input type="text" class="form-control form-control-sm" name="external_link_url" value="" placeholder="https://example.com">
                        </div>
                        <button class="btn btn-sm btn-secondary w-100 mt-3 fw-bold add_external_link_to_menu">@lang('word.add_to_menu')</button>
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
                    <div class="row">
                        <div class="col-6 mb-5">
                            <label class="form-label fw-bold text-dark">@lang('word.website')</label>
                            <input type="text" class="form-control form-control-sm" value="{{ $menu->website?->domain }}" disabled>
                        </div>
    
                        <div class="col-6 mb-5">
                            <label class="form-label fw-bold text-dark">@lang('word.menu_id')</label>
                            <input type="text" class="form-control form-control-sm" value="{{ $menu->id }}" disabled>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-bold text-info">@lang('word.menu_name')</label>
                        <input type="text" class="form-control form-control-sm" name="name" value="{{ $menu->name }}" required>
                    </div>

                    <label class="form-label fw-bold text-info">@lang('word.menu_items')</label>
                    <div class="d-flex">
                        <button type="button" class="btn btn-sm btn-dark fw-bold dd_expand_all">@lang('word.expand_all')</button>
                        <button type="button" class="btn btn-sm btn-dark fw-bold dd_collapse_all ms-1">@lang('word.collapse_all')</button>
                    </div>
                    <div class="dd" id="nestable-json"></div>
                </div>
            </div>

            <button class="btn btn-dark w-100 mt-5 update_menu" wncms-btn-loading>@lang('word.update')</button>

        </form>
    </div>
</div>

{{-- Model edit menu item --}}
<div class="modal fade" tabindex="-1" id="modal_edit_menu_item">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">@lang('word.edit_menu_item')</h3>

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
                                <label for="menu_item_name" class="form-label">@lang('word.menu_id')</label>
                                <input type="text" class="form-control form-control-solid form-control-sm" id="modal_edit_menu_item_id" name="menu_item_id" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="menu_item_url" class="form-label">@lang('word.menu_item_type')</label>
                                <input type="text" class="form-control form-control-solid form-control-sm" id="modal_edit_menu_item_type" name="menu_item_type" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="menu_item_url" class="form-label">@lang('word.menu_item_model_type')</label>
                                <input type="text" class="form-control form-control-solid form-control-sm" id="modal_edit_menu_item_model_type" name="menu_item_model_type" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="menu_item_url" class="form-label">@lang('word.menu_item_model_id')</label>
                                <input type="text" class="form-control form-control-solid form-control-sm" id="modal_edit_menu_item_model_id" name="menu_item_model_id" readonly>
                            </div>
                        </div>
                    </div>
                  
                    {{-- Lang --}}
                    <div class="row">
                        @foreach(LaravelLocalization::getSupportedLocales() as $locale_key => $locale)
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="menu_item_name" class="form-label">@lang('word.menu_item_name') <span class="text-muted">({{ $locale['native'] }})</span></label>
                                    <input type="text" class="form-control form-control-sm modal_edit_menu_item_name" id="modal_edit_menu_item_name" name="menu_item_name[{{ $locale_key }}]">
                                </div>
                            </div>
                        @endforeach
                        <div class="col-12 mb-3">
                            <button class="btn btn-sm btn-dark fw-bold w-100 btn-fetch-tag-languages" type="button" data-tag-id style="display: none">@lang('word.fetch_tag_languages')</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="mb-5">
                                <label for="menu_item_url" class="form-label">@lang('word.menu_item_url')</label>
                                <input type="text" class="form-control form-control-sm" id="modal_edit_menu_item_url" name="menu_item_url">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-5">
                                <label for="menu_item_order" class="form-label">@lang('word.order')</label>
                                <input type="text" class="form-control form-control-sm" id="modal_edit_menu_item_order" name="menu_item_order">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-5">
                                <label for="menu_item_description" class="form-label">@lang('word.description')</label>
                                <input type="text" class="form-control form-control-sm" id="modal_edit_menu_item_description" name="menu_item_description">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="mb-5">
                                <label for="menu_item_icon" class="form-label">@lang('word.menu_item_icon')</label>
                                <input type="text" class="form-control form-control-sm" id="modal_edit_menu_item_icon" name="menu_item_icon">
                            </div>
                            <div class="mb-5">
                                <label for="menu_item_thumbnail" class="form-label">@lang('word.thumbnail')</label>
                                <input type="file" class="form-control" id="modal_edit_menu_item_thumbnail" name="menu_item_thumbnail">
                            </div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">@lang('word.current_thumbnail')</label>
                            <img id="current_menu_item_thumbnail" class="w-100" src="{{ asset('wncms/images/placeholders/upload.png') }}" alt="">
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="modal_edit_menu_item_is_new_window" name="menu_item_new_window">
                        <label class="form-check-label" for="modal_edit_menu_item_is_new_window">@lang('word.new_window')</label>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('word.cancel')</button>
                <button type="button" class="btn btn-primary" id="modal_submit_form_edit_menu_item" wncms-btn-loading>@lang('word.edit')</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('foot_js')
    <script src="//cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.js"></script>
    <script>
        $(document).ready(function(){

            //獲取menu_items轉為json
            var current_menu = @json($menu->menu_items()->whereNull('parent_id')->with('children','children.children')->orderBy('order','asc')->get()->append('thumbnail'));

            //Initialize Nestable
            $('#nestable-json').nestable({
                json: current_menu
            });
            
            //生成Menu列表
            fill_menu(current_menu);

            //綁定展開/折疊事件
            $('.toggle-children').on('click', function() {
                $(this).toggleClass('collapsed');
                $(this).closest('.dd-item').children('.dd-list').toggleClass('d-none');
            });

            //新增自訂連結
            $('.add_external_link_to_menu').on('click', function() {

                var name = $("#menu_option_external_link").find('input[name="external_link_name"]').val();
                var url = $("#menu_option_external_link").find('input[name="external_link_url"]').val();
                var type = 'external_link';

                //檢查 name 和 type 是否為 undefined，如果是，就不添加這個項目
                if (name !== undefined && name) {
                    var newItem = {
                        name: name,
                        url: url,
                        type: type,
                    };
                    $('#nestable-json').nestable('add', newItem);
                }

                var json = $('#nestable-json').nestable('serialize');
                fill_menu(json);
            });

            //新增主題頁面
            $('.add_theme_page_to_menu').on('click', function() {

                //find all checkbox
                var checkedCheckboxes = $("#menu_option_theme_page input[data-type='theme_page']:checked");

                //foreach checkbox
                checkedCheckboxes.each(function(){
                    var name = $(this).data("name");
                    var url = $(this).data("url");
                    var type = $(this).data("type");

                    //generate item object
                    if (name !== undefined && name) {
                        var newItem = {
                            name: name,
                            url: url,
                            type: type,
                        };
                        $('#nestable-json').nestable('add', newItem);
                    }

                })

                //item objects to json
                var json = $('#nestable-json').nestable('serialize');

                //add new items to menu
                fill_menu(json);
            });

            //将已勾选的项目添加到 Nestable 列表中
            $('.update_menu').off().on('click', function(e) {
                e.preventDefault();
                $(this).prop('disabled', true)
                var json = $('#nestable-json').nestable('serialize');
                var input = $("<input>").attr("type", "hidden").attr("name", "new_menu").val(JSON.stringify(json));
                $('#form_menu_update').append(input);
                $('#form_menu_update').submit();
            });

            //collapse菜單
            $('.dd_expand_all').on('click', function(){
                $('.dd-item').removeClass('dd-collapsed');
            })

            //expand菜單
            $('.dd_collapse_all').on('click', function(){
                $('.dd-item').addClass('dd-collapsed');
            })

            //在Modal中save menu item
            $("#modal_submit_form_edit_menu_item").click(function(e) {
                e.preventDefault()
                var btn = $(this);

                //獲取表單數據
                // var form_data = $('#form_edit_menu_item').serialize();
                var form = $('#form_edit_menu_item')[0];
                var form_data = new FormData(form)
                // console.log(form_data)

                //通過 AJAX 請求將表單數據發送到服務器端
                $.ajax({
                    url: '{{ route("menus.edit_menu_item") }}',
                    type: 'POST',
                    processData: false,
                    contentType: false,
                    data: form_data,
                    success: function(data) {
                        console.log(data);
                        // console.log(data.menu_item);
                        if(data.status == 'success'){

                            //Initialize Nestable
                            $('#nestable-json').nestable({
                                json: data.menu
                            });
                            
                            //% 生成Menu列表 %% 不需要生成，會導致未保存的排序丟失 
                            //但不使用在更新完又不懂得刷新列表中的數據，不會顯示最新數據
                                //A. 最好的方法是，ajax 保存菜單一次，重新獲取最新狀態
                                //B. Ajax保存一次，直接刷新
                            // fill_menu(data.menu);

                            form.reset();
                            btn.prop('disabled', false);

                            if(data.hide_modal){
                                //隱藏modal
                                $("#modal_edit_menu_item").modal("hide");
                            }

                        }else{
                            Swal.fire({
                                icon:success,
                                title: '{{ __("word.failed") }}',
                            })
                        }
                    }
                });

                //reder menu item on sucess
            });

            //check if Tag still exists
            checkTagIds();

            // TODO: check if post still exists
            
        });

        //填充menu
        function fill_menu(data, parent) {
            console.log(data);

            var html = '';
            $.each(data, function (index, item) {
                // console.log('============')
                console.log(item);
                // console.log('============')
                var item_name = item.name.{{ Laravellocalization::getCurrentLocale() }} != undefined ? item.name.{{ Laravellocalization::getCurrentLocale() }}
                                : (item.name.{{ Laravellocalization::getDefaultLocale() }} != undefined 
                                    ? item.name.{{ Laravellocalization::getDefaultLocale() }} 
                                    : item.name);


                var item_description = '';
                if(item.description != undefined){
                    if(item.description.{{ Laravellocalization::getCurrentLocale() }} != undefined){
                        item_description = item.description.{{ Laravellocalization::getCurrentLocale() }};
                        // console.log('found');
                    }else{
                        // console.log('description is not undefined but locale is empty')
                    }
                }else{
                    // console.log('undefind value found');
                }
                // if( item.description.{{ Laravellocalization::getCurrentLocale() }} != undefined )
                // var item_description = item.description.{{ Laravellocalization::getCurrentLocale() }} != undefined ? item.description.{{ Laravellocalization::getCurrentLocale() }}
                //             : (item.description.{{ Laravellocalization::getDefaultLocale() }} != undefined 
                //                 ? item.description.{{ Laravellocalization::getDefaultLocale() }} 
                //                 : item.description);
                // var item_description = item.description.{{ Laravellocalization::getCurrentLocale() }} || item.description;




                //render menu item data to the list
                html += '<li class="dd-item" data-id="' + (item.id ? item.id : generateRandomId()) 
                    + '" data-model-type="' + (item.model_type ?? item.modelType) 
                    + '" data-model-id="' + (item.model_id ?? item.modelId) 
                    + '" data-name="' + item_name 
                    + '" data-url="' + item.url 
                    + '" data-description="' + item_description 
                    + '" data-type="' + item.type 
                    + '" data-thumbnail="' + item.thumbnail 
                    + '" data-new-window="'+ item.is_new_window +'">';
                    //collapse/expand
                    html += '<button class="dd-collapse" data-action="collapse" type="button">Collapse</button><button class="dd-expand" data-action="expand" type="button">Expand</button>';
                    //title
                        
                        html += '<div class="dd-handle h-35px"><span class="dd-handle-name">' + item_name + '</span><span class="small text-muted fw-normal d-none d-inline">(' + item.type + ')</span>';
                        //url
                        if(item.type == 'external_link'){
                            html += '<div class="d-inline-flex align-items-center dd-nodrag ms-3"><input class="menu_item_url" type="text" value="'+ item.url +'"></div>';
                        }
                        //delete icon
                        html += '<div class="float-end dd-nodrag ms-3"><i class="fa fa-trash text-danger remove_menu_item"></i></div>';

                        //edit icon
                        if(item.id){
                            html += '<div class="float-end dd-nodrag ms-3"><i class="fa fa-edit text-info show_modal_edit_menu_item"></i></div>';
                        }
                        //new window
                        html += '<div class="form-check form-check-sm form-check-custom form-check-solid mb-0 float-end dd-nodrag d-inline check_new_window">';
                            html += '<span class="me-2 text-gray-400 d-none d-sm-inline">' + item.type + '</span>'
                            html += '<input class="form-check-input" type="checkbox" title="{{ __("word.new_window") }}"'+ ((item.is_new_window || item.newWindow) && item.newWindow != 'undefined' ? 'checked' : '') +'>';
                            html += '<label class="form-check-label small d-none d-inline">{{ __("word.new_window") }}</label>';
                        html += '</div>';
                    html += '</div>';
                    
                    if (item.children && item.children.length > 0) {
                        html += '<ol class="dd-list">';
                        html += fill_menu(item.children, item.id);
                        html += '</ol>';
                    }
                html += '</li>';
            });
            if (parent) {
                return html;
                $('[data-id="'+ parent +'"]').children('.dd-list').html(html);
            } else {
                $('.dd-list').html(html);
            }
            bind_click_events();
        }

        //每次生成項目，都需要重新綁定Event Listener
        function bind_click_events(){
            //移除項目
            $('.remove_menu_item').on('click', function(e) {
                e.preventDefault();
                var menu_item_id = $(this).closest('.dd-item').data('id')
                $('.dd').nestable('remove', menu_item_id)
                var input = $("<input>")
                    .attr("type", "hidden")
                    .attr("name", "removes[]")
                    .val(menu_item_id);
                $('#form_menu_update').append(input);
            });

            //将已勾选的项目添加到 Nestable 列表中
            $('.add_to_menu').off().on('click', function() {
                var checkedItems = $(this).closest('.accordion-body').find('input:checked');
                var newItems = [];

                checkedItems.each(function() {
                    //檢查 name 和 type 是否為 undefined，如果是，就不添加這個項目
                    if ($(this).data('name') !== undefined && $(this).data('type') !== undefined) {
                        var newItem = {
                            id: null,
                            name: $(this).data('name'),
                            type: $(this).data('type'),
                            model_type: $(this).data('model-type'),
                            model_id: $(this).data('model-id'),
                            url: $(this).data('url') ? $(this).data('url') : null,
                            newWindow: $(this).data('new-window') ? $(this).data('new-window') : 0,
                        };
                        newItems.push(newItem); //將新項目添加到新數組中
                        $('#nestable-json').nestable('add', newItem);
                    }
                });
                var json = $('#nestable-json').nestable('serialize');
                //! serialize了，model_type to modelType
                //console.log(json)
                fill_menu(json);
                checkedItems.prop( "checked", false )
            });

            $('.menu_item_url').off().on('change', function() {
                var new_url = $(this).val();
                var item = $(this).closest('.dd-item');
                item.attr('data-url', new_url);
                item.attr('data-id', item.data('id').toString()); //將 id 屬性轉換為字符串
            });

            //勾選在新視窗打開
            $('.check_new_window input[type="checkbox"]').on('change', function() {
                var new_window = $(this).closest('.dd-item');
                if(new_window.attr('data-new-window') == 1){
                    new_window.attr('data-new-window', 0);
                }else{
                    new_window.attr('data-new-window', 1);
                }
            });

            //打開編輯菜單項目
            $(".show_modal_edit_menu_item").off().click(function() {
                //load current data
                //Get the dd-item element
                var dd_item = $(this).closest('.dd-item');
                //Extract the data attributes
                var id = dd_item.data('id');

                //% 也用ajax的數據?
                var url = dd_item.data('url');
                var description = dd_item.data('description');
                var thumbnail = dd_item.data('thumbnail');
                var type = dd_item.data('type');
                var model_type = dd_item.data('model-type');

                var model_id = dd_item.data('model-id');
                var is_new_window = dd_item.data('new-window');

                //Populate the form fields
                $('#modal_edit_menu_item_id').val(id);
                $('#modal_edit_menu_item_name').val(name);
                $('#modal_edit_menu_item_url').val(url);
                $('#modal_edit_menu_item_description').val(description);
                $('#current_menu_item_thumbnail').attr('src',thumbnail);
                $('#modal_edit_menu_item_type').val(type);
                if(model_type != "undefined")$('#modal_edit_menu_item_model_type').val(model_type);
                if(model_id != "undefined")$('#modal_edit_menu_item_model_id').val(model_id);
                $('#modal_edit_menu_item_is_new_window').prop('checked', is_new_window);

                //Activate try to load lang button
                if(model_type == 'Tag'){
                    $('.btn-fetch-tag-languages').show();
                    $('.btn-fetch-tag-languages').attr('data-tag-id', model_id);
                    $('.btn-fetch-tag-languages').off().on('click', function(){
                        console.log('loaded tag lang');
                        console.log(model_id);
                        $.ajax({
                            headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            url:"{{ route('tags.get_languages') }}",
                            data:{
                                model_id:model_id,
                            },
                            type:"POST",
                            success:function(response){
                                console.log(response);
                                const modal_edit_menu_item_name_inputs = document.querySelectorAll('.modal_edit_menu_item_name');
                                modal_edit_menu_item_name_inputs.forEach(input => {
                                    const localeKey = input.getAttribute('name').match(/\[(.*?)\]/)[1];
                                    // console.log(localeKey);
                                    if (response.translations.name[localeKey]) {
                                        input.value = response.translations.name[localeKey];
                                    }
                                });
                            }
                        });
                    })
                    
                }

                //更新modal中的項目
                $.ajax({
                    headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    url:"{{ route('menus.get_menu_item') }}",
                    data:{
                        menu_item_id:id,
                    },
                    type:"POST",
                    success:function(data){
                        // console.log(data);
                        // console.log(data.name)
                        $('#modal_edit_menu_item .modal_edit_menu_item_name').val('')
                        Object.keys(data.name).forEach(function(locale_key){
                            // console.log(data)
                            // 在这里使用 data.name[locale_key] 来访问每个语言的菜单项名称
                            $('#modal_edit_menu_item input[name="menu_item_name['+locale_key+']"]').val(data.name[locale_key])
                            $('#form_edit_menu_item input[name="menu_item_icon').val(data.icon)
                        })
        
    
                    }
                });

                //show modal
                $("#modal_edit_menu_item").modal("show");


            });

        
        }

        function generateRandomId(length = 16) {
            const characters = '0123456789';
            let randomString = '';

            for (let i = 0; i < length; i++) {
                const randomIndex = Math.floor(Math.random() * characters.length);
                randomString += characters.charAt(randomIndex);
            }

            return randomString;
        }

        //check if tag still exists
        function checkTagIds()
        {
            var tagIds = [];
            $("#nestable-json li[data-model-type='Tag'][data-model-id!='']").each(function () {
                tagIds.push($(this).data('model-id'));
            });

            $.ajax({
                url: "{{ route('api.v1.tags.exist') }}",
                type: "POST",
                data: { tagIds: tagIds },
                success: function (response) {
                    // console.log(response);
                    var existingTagIds = response.ids;
                    $("#nestable-json li[data-model-type='Tag']").each(function () {
                        var tagId = $(this).data('model-id');
                        if (!tagId || !existingTagIds.includes(tagId)) {
                            var handleName = $(this).find('.dd-handle>.dd-handle-name');
                            handleName.css('color', 'red');
                        }
                    });
                }
            });
        }
    </script>
@endpush