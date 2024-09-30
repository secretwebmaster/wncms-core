<div class="row">
    {{-- Main --}}
    <div class="col-12 col-md-9">

        {{-- Nav tabs --}}
        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold active" id="pills-basic-tab" data-bs-toggle="pill" data-bs-target="#pills-basic" type="button" role="tab" aria-controls="pills-basic" aria-selected="true">@lang('word.basic')</button>
            </li>

            @if($page->type == 'template' && $page->template_info && !empty($page->template_info['widgets']))
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="pills-template-tab" data-bs-toggle="pill" data-bs-target="#pills-template" type="button" role="tab" aria-controls="pills-template" aria-selected="false">@lang('word.theme_template_options')</button>
                </li>
            @endif

            @if($page->type == 'builder')
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="pills-builder-tab" data-bs-toggle="pill" data-bs-target="#pills-builder" type="button" role="tab" aria-controls="pills-builder" aria-selected="false">@lang('word.builder')</button>
                </li>
            @endif
        </ul>

        {{-- Nav content --}}
        <div class="tab-content" id="pills-tabContent">

            {{-- Basic page options --}}
            <div class="tab-pane fade show active" id="pills-basic" role="tabpanel" aria-labelledby="pills-basic-tab">
                <div class="card">
                    <div class="card-header border-0 cursor-pointer px-3 px-md-5">
                        <div class="card-title m-0">
                            <h3 class="fw-bolder m-0">{{ wncms_model_word('page', 'edit') }}</h3>
                        </div>
                    </div>

                    <div class="card-body p-2 p-md-5">
                        {{-- title --}}
                        <div class="form-item mb-3">
                            <label class="form-label required fw-bold fs-6">@lang('word.title')</label>
                            <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title', $page->title) }}" required />
                        </div>

                        {{-- slug --}}
                        <div class="form-item mb-3">
                            <label class="form-label required fw-bold fs-6">@lang('word.slug') (@lang('word.show_in_url'))</label>
                            <input type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $page->slug) }}" />
                        </div>

                        {{-- type --}}
                        <div class="form-item mb-3">
                            <label class="form-label required fw-bold fs-6">@lang('word.type')</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="" @lang('word.please_select')> @lang('word.type')</option>
                                @foreach($types ?? [] as $type)
                                <option value="{{ $type }}" {{ $type===old('type', $page->type ?? null) ? 'selected' :'' }}>@lang('word.' . $type)</option>
                                @endforeach
                            </select>
                            <div class="text-muted">@lang('word.theme_template_list_will_be_shown_after_saving')</div>
                        </div>

                        {{-- blade_name --}}
                        @if($page->type == 'template' && !empty($available_templates))
                            <div class="form-item mb-3">
                                <label class="form-label fw-bold fs-6">@lang('word.available_theme_template')</label>
                                <select name="blade_name" class="form-select form-select-sm" required>
                                    <option value="">@lang('word.not_using_theme_template')</option>
                                    @foreach($available_templates as $available_template)
                                    <option value="{{ $available_template['blade_name'] }}" {{ $available_template['blade_name']==old('blade_name', $page->blade_name) ? 'selected' :'' }}><b>{{ $available_template['blade_name'] }}</b></option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                        <input type="hidden" name="blade_name" value="{{ $page->blade_name }}">
                        @endif

                        {{-- remark --}}
                        <div class="form-item mb-3">
                            <label class="form-label fw-bold fs-6">@lang('word.remark')</label>
                            <input type="text" name="remark" class="form-control form-control-sm" value="{{ old('remark', $page->remark) }}" />
                        </div>

                        {{-- order --}}
                        <div class="form-item mb-3">
                            <label class="form-label fw-bold fs-6">@lang('word.order')</label>
                            <input type="number" name="order" class="form-control form-control-sm" value="{{ old('order', $page->order) }}" />
                        </div>

                        {{-- content --}}
                        <div class="form-item mb-3">
                            <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('word.content')</label>
                            <textarea id="kt_docs_tinymce_basic" name="content" class="tox-target">{{ old('content', $page->content ?? null) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Theme template option --}}
            @if($page->type == 'template' && $page->template_info && !empty($page->template_info['widgets']))
                <div class="tab-pane fade" id="pills-template" role="tabpanel" aria-labelledby="pills-template-tab">
                    <div class="card mt-5">
                        
                        <div class="card-header border-0 cursor-pointer px-3 px-md-5">
                            <div class="card-title m-0">
                                <h3 class="fw-bolder m-0">@lang('word.theme_template_options')</h3>
                            </div>
                        </div>

                        {{-- Theme template widget list --}}
                        <div class="card-body p-2 p-md-5">
                            <div class="row">

                                <div class="col-12">
                                    <div class="accordion list-group col min-h-200px border border-1 border-secondary border-dashed">
                                        @foreach(collect(config('theme.' . $page->website->theme . '.templates'))->where('blade_name', $page->blade_name)->first()['widgets'] ?? [] as $index => $widget)
  
                                            @php 
                                                $pageWidgetId = "widget_{$index}_{$widget}";
                                                $widgetInfo = config('theme.' . $page->website?->theme . '.widgets.' . $widget);
                                                if(empty($widgetInfo)) continue;
                                                // dd(
                                                //     'theme.' . $page->website?->theme . '.widgets.' . $widget,
                                                //     $widgetInfo
                                                // );
                                            @endphp
                                            {{-- Accordion item --}}
                                            <div class="accordion-item widget-list-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button text-gray-100 fw-bold bg-dark py-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $pageWidgetId }}" aria-expanded="true" aria-controls="{{ $pageWidgetId }}">
                                                        {{ $widgetInfo['name'] }}
                                                    </button>
                                                </h2>
                                                <div id="{{ $pageWidgetId }}" class="accordion-collapse collapse @if($loop->iteration == 1) show @endif" data-bs-parent="#widget-options">
                                                    <div class="accordion-body">
                                                        @foreach(config("theme." . $page->website->theme . '.widgets.' . $widget . '.fields') as $option_index => $option)

                                                        {{-- @dd([
                                                            'option' => $option,
                                                            'current_options' => $page->options,
                                                            'inputNameKey' => "inputs[" . $pageWidgetId . "]",
                                                            'isPageTemplateValue' => true,
                                                            'pageWidgetId' => $pageWidgetId,
                                                        ]) --}}
                                                            @include('backend.parts.inputs', [
                                                                'option' => $option,
                                                                'current_options' => $page->options,
                                                                'inputNameKey' => "inputs[" . $pageWidgetId . "]",
                                                                'isPageTemplateValue' => true,
                                                                'pageWidgetId' => $pageWidgetId,
                                                            ])
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
           
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Wncms builder --}}
            @if($page->type == 'builder')
                <div class="tab-pane fade" id="pills-template" role="tabpanel" aria-labelledby="pills-template-tab">
                    <div class="card mt-5">
                        
                        <div class="card-header border-0 cursor-pointer px-3 px-md-5">
                            <div class="card-title m-0">
                                <h3 class="fw-bolder m-0">@lang('word.theme_template_options')</h3>
                            </div>
                        </div>

                        {{-- Theme template widget list --}}
                        <div class="card-body p-2 p-md-5">

                            <div class="row">
                                <div class="col-12 col-md-3">
                                    <h4>@lang('word.widgets')</h4>

                                    <div id="widget-list" class="row g-2">
                                        @foreach(config('theme.' . $page->website?->theme . '.widgets') ?? [] as $widget)
                                        <div class="col-6 accordion-item widget-list-item" data-widget="{{ $widget['key'] ?? 'undefined_widget_key' }}" data-name="@lang($page->website?->theme . '.' . ($widget['key'] ?? 'undefined_widget_key'))">
                                            <div class="widget-info-wrapper bg-light">
                                                <i class="{{ $widget['icon'] ?? 'fa-solid fa-cube' }}"></i>
                                                <div class="widget-name">{{ $widget['name'] ?? 'undefined_widget_name' }}</div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-12 col-md-9">
                                    <h4>@lang('word.options')</h4>

                                    <div id="widget-options" class="accordion list-group col min-h-200px border border-1 border-secondary border-dashed">

                                        @if(!empty($page->options))
                                            @foreach($page->options as $groupId => $valueArray)
                                                @php
                                                    if(empty($valueArray['widget_key'])) continue;
                                                    $widget =  config("theme." . $page->website?->theme . ".widgets." .  ($valueArray['widget_key']));
                                                    if(empty($widget)) continue;
                                                    
                                                    $options = $widget['fields'] ?? [];
                                                    $options[] = ['type' => 'hidden', 'name' => 'widget_key'];
                                                    $current_options = $page->options;
                                                @endphp

                                                {{-- Accordion item --}}
                                                <div class="accordion-item widget-list-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button text-gray-100 fw-bold bg-dark py-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}" aria-expanded="true" aria-controls="{{ $groupId }}">
                                                            {{ $widget['name'] }}
                                                        </button>
                                                    </h2>
                                                    <div id="{{ $groupId }}" class="accordion-collapse collapse show" data-bs-parent="#widget-options">
                                                        <div class="accordion-body">
                                                            @foreach($options as $option)
                                                            {{-- @dd($options, $current_options) --}}
                                                                @include('backend.parts.inputs', [
                                                                    'option' => $option,
                                                                    'current_options' => $current_options,
                                                                    'inputNameKey' => "inputs[" . $groupId . "]",
                                                                    'isPageTemplateValue' => true,
                                                                    'groupId' => $groupId,
                                                                ])
                                                            @endforeach

                                                            <button class="btn btn-sm btn-danger fw-bold w-100 remove-widget-option" type="button">@lang("word.delete")</button>
                                                        </div>
                                                    </div>
                                                </div>

                                            @endforeach
                                        @endif
                                        {{-- @dd($page->options) --}}
                                    </div>
                                </div>
                            </div>

                            @push('foot_js')
                                <script>
                                    widgetList = document.getElementById('widget-list');
                                    new Sortable(widgetList, {
                                        group: {
                                            name: 'shared',
                                            pull: 'clone', // To clone: set pull to 'clone',
                                            put: false, // Disable putting on the widget-list
                                            disabled: true,
                                        },
                                        animation: 150,
                                        sort: false, // Disable sorting for widget-list
                                        onClone: function (evt) {
                                            // Your custom logic when a widget is cloned from widget-list
                                            console.log('Widget cloned:', evt.clone);
                                            // evt.clone.style.backgroundColor = 'lime';
                                        }
                                    });

                                    widgetOptions = document.getElementById('widget-options');
                                    new Sortable(widgetOptions, {
                                        group: {
                                            name: 'shared',
                                            pull: 'clone',
                                        },
                                        animation: 150,
                                        handle: ".widget-list-item > .accordion-header",
                                        onAdd: function (evt) {

                                            // Change the background color of the added item
                                            $('#widget-options .widget-list-item').css("background-color", "transparent");

                                            var item = $(evt.item);
                                            item.html('');
                                            item.removeClass('col-6').addClass('accordion-item');
                                            var name = item.data('name');
                                            var tempId = name + (Math.random() + 1).toString(36).substring(7);

                                            var accordionHeader = $('<h2 class="accordion-header" id="headingOne"></h2>');
                                            var accordionButton = $('<button class="accordion-button text-gray-100 fw-bold bg-dark py-3" type="button" data-bs-toggle="collapse" aria-expanded="true"></button>');
                                            accordionButton.text(name);
                                            var accordionCollapse = $('<div class="accordion-collapse collapse show"></div>');
                                            var accordionBody = $('<div class="accordion-body"></div>');

                                            // Set attributes and append elements
                                            accordionButton.attr({
                                                'data-bs-target': '#' + tempId,
                                                'aria-controls': tempId
                                            });

                                            accordionHeader.append(accordionButton);
                                            accordionCollapse.attr('id', tempId).append(accordionBody);
                                            item.append(accordionHeader, accordionCollapse);

                                            var widgetId = item.data('widget');
                                            var theme = '{{ $page->website?->theme }}';

                                            //fetch widget options
                                            $.ajax({
                                                headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                                                url:"{{ route('pages.widget') }}",
                                                data:{
                                                    widgetId:widgetId,
                                                    theme:theme,
                                                },
                                                type:"POST",
                                                success:function(response){
                                                    console.log(response)
                                                    //append html to it
                                                    item.find('.accordion-body').append(response.html);

                                                    var deleteButton = $('<button class="btn btn-sm btn-danger fw-bold w-100 remove-widget-option" type="button">@lang("word.delete")</button>');
                                                    item.find('.accordion-body').append(deleteButton);

                                                    bindButtonFunctions();

                                                    if(response.sortableIds.length){
                                                        console.log(response.sortableIds);
                                                        response.sortableIds.forEach(function(sortableId){
                    
                                                            // Initialize Sortable for the accordion
                                                            console.log('init newly added sortable')
                                                            console.log(sortableId);
                                                            new Sortable(document.getElementById(sortableId), {
                                                                handle: '.accordion-header', // Class of the handle for dragging
                                                                animation: 150, // Animation duration in milliseconds
                                                                onEnd: function (evt) {
                                                                    var itemOrderInput = $(evt.item).find('.item-order-input');
                                                                    console.log("itemOrderInput.length = " + itemOrderInput.length);
                                                                    if(itemOrderInput.length){
                                                                        console.log('hidden input is found')
                                                                        updateHiddenInputValues(evt.from.children);
                                                                        console.log('updated hidden value')
                                                                    }

                                                                    // evt.oldIndex is the old index of the dragged item
                                                                    // evt.newIndex is the new index of the dragged item

                                                                    // Perform actions on change here
                                                                    console.log('Accordion item order changed!');
                                                                    console.log('Old Index:', evt.oldIndex);
                                                                    console.log('New Index:', evt.newIndex);
                                                                },
                                                            });
                                        
                                                            function updateHiddenInputValues(items) {
                                                                // Update the hidden input values based on the new order
                                                                console.log("item is ");
                                                                consoel.log(items);
                                                                items.forEach(function (item, index) {
                                                                    const hiddenInput = item.querySelector('.item-order-input');
                                                                    if (hiddenInput) {
                                                                        // Update the value of the hidden input based on the new order
                                                                        hiddenInput.value = index + 1;
                                                                    }
                                                                });
                                                            }

                                                        })
                                                    }
                                                }
                                            });
                                        }
                                    });

                                    function bindButtonFunctions(){
                                        $('.remove-widget-option').off().on('click', function(){
                                            $(this).closest('.widget-list-item').remove();
                                        })
                                    }

                                    bindButtonFunctions();

                                </script>
                            @endpush

                        </div>
                    </div>
                </div>
            @endif
                
            {{-- // TODO: 可視化編輯器--}}
            @if(false && $page->type == 'visual-builder')
                <div class="tab-pane fade" id="pills-builder" role="tabpanel" aria-labelledby="pills-builder-tab">
                    <div class="card mt-5">
                        <div class="card-header border-0 cursor-pointer px-3 px-md-5">
                            <div class="card-title m-0">
                                <h3 class="fw-bolder m-0">@lang('word.page_builder')</h3>
                            </div>
                        </div>

                        {{-- <div class="card-body p-2 p-md-5">
                            @dd($available_templates)
                            @if(!empty($available_templates))
                            @foreach($available_templates as $available_template)
                            {{ $available_template }}
                            @endforeach
                            @else
                            <div class="alert alert-secondary">@lang('word.no_template_is_available_for_this_theme')</div>
                            @endif
                        </div> --}}

                        {{-- <div class="card-body p-2 p-md-5 row">

                            <div class="col-12 col-md-4 row g-2 mt-0 align-items-start flex-column">
                                <div>
                                    <button class="btn btn-dark w-100" type="button">@lang('word.add_to_page')</button>
                                </div>
                                @foreach($available_templates as $template_name => $template_data)
                                <div>
                                    <input type="radio" class="btn-check" name="radio_buttons_2" value="{{ $template_name }}" id="{{ $template_name }}" />
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary p-2 d-flex align-items-center mb-1" for="{{ $template_name }}">
                                        <i class="fa-solid fa-{{ $template_data['icon'] }}"><span class="path1"></span><span class="path2"></span></i>
                                        <span class="d-block fw-semibold text-start">
                                            <span class="text-dark d-block fs-5 ms-1">{{ $template_data['label'] }}</span>
                                        </span>
                                    </label>
                                </div>
                                @endforeach
                            </div>

                            <div class="col-12 col-md-8">
                                <div class="border border-1 border-dotted border-dark h-100 rounded p-2">
                                    @foreach($page->templates as $page_template)
                                    @php
                                    $current_template = $available_templates[$page_template->template_id];
                                    @endphp
                                    <div class="card border border-1 border-dotted border-dark rounded mb-2">
                                        <div class="card-header bg-dark align-items-center">
                                            <h4 class="text-gray-100 mb-0">{{ $current_template['label'] }}</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="page-template-option">
                                                @foreach($current_template['options'] as $option_index => $option)
                                                @php
                                                dd(
                                                $page_template,
                                                $current_template,
                                                $option,
                                                $page_template->value[$option['name']],
                                                );
                                                @endphp
                                                @include('backend.parts.inputs' , [
                                                'website'=> $website,
                                                'option_index'=>$option_index,
                                                'option'=>$option,
                                                'current_options'=>$current_options,
                                                ])

                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                        </div> --}}
                    </div>
                </div>
            @endif
        </div>

    </div>

    {{-- Sizebar --}}
    <div class="col-12 col-md-3">

        <div class="card">
            <div class="card-header border-0 cursor-pointer p-2 p-md-5">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">@lang('word.publish_related')</h3>
                </div>
            </div>

            <div class="card-body p-2 p-md-5">

                {{-- status --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.status')</label>
                    <select name="status" class="form-select form-select-sm" required>
                        <option value="">@lang('word.please_select')</option>
                        @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ ($status===old('status', $page->status) || empty(old('status')) && $status == 'published') ? 'selected' :'' }}><b>@lang('word.' . $status)</b></option>
                        @endforeach
                    </select>
                </div>

                {{-- visibility --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.visibility')</label>
                    <select name="visibility" class="form-select form-select-sm" required>
                        <option value="">@lang('word.please_select')</option>
                        @foreach($visibilities as $visibility)
                        <option value="{{ $visibility }}" {{ ($visibility===old('visibility', $page->visibility) || empty(old('visibility')) && $visibility == 'public') ? 'selected' :'' }}><b>@lang('word.' . $visibility)</b></option>
                        @endforeach
                    </select>
                </div>

                {{-- Publish --}}
                <div class="mb-3">
                    <button type="submit" wncms-btn-loading class="btn btn-primary w-100 wncms-submit">
                        @include('backend.parts.submit', ['label' => $submitLabelText ?? __('word.save_all')])
                    </button>
                </div>

                {{-- Preview --}}
                @if(!empty($page->website))
                    <div class="mb-3">
                        <a href="{{ $wncms->getRoute('frontend.pages', ['slug' => $page->slug], false, $page->website->domain) }}" target="_blank" class="btn btn-dark fw-bold w-100">@lang('word.preview')</a>
                        {{-- <a href="{{ route('frontend.pages', ['slug' => $page->slug]) }}" target="_blank" class="btn btn-dark w-100">@lang('word.preview')</a> --}}
                    </div>
                @endif

            </div>
        </div>

        {{-- Relationship --}}
        <div class="card mt-5">
            <div class="card-body p-2 p-md-5">
                {{-- website_id --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.website')</label>
                    <select name="website_id" class="form-select form-select-sm" required>
                        @foreach($websites as $_website)
                        <option value="{{ $_website->id }}" @if($_website->id == $page->website?->id) selected @elseif(($_website->id != $page->website?->id) && wncms()->isSelectedWebsite($_website)) selected @endif>#{{ $_website->id }} {{ $_website->domain }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- user_id --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.author')</label>
                    <select name="user_id" class="form-select form-select-sm" required>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" @if($user->id == $page->user?->id) selected @endif>#{{ $user->id }} {{ $user->username }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Image --}}
        <div class="card mt-5">
            <div class="card-header border-0 cursor-pointer p-2 p-md-5">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">@lang('word.images')</h3>
                </div>
            </div>

            <div class="card-body p-2 p-md-5">
                {{--thumbnail --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.thumbnail')</label>

                    <div class="image-input image-input-outline w-100 {{ !empty($page->getFirstMediaUrl('page_thumbnail')) ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ !empty($page->getFirstMediaUrl('page_thumbnail')) ?: asset('wncms/images/placeholders/upload.png') }});background-position: center;">
                        <div class="image-input-wrapper w-100 h-100" style="background-image:{{ !empty($page->getFirstMediaUrl('page_thumbnail')) ? 'url('. $page->getFirstMediaUrl('page_thumbnail') .')' : 'none' }};aspect-ratio:16/10;background-size: 100% 100%;"></div>
                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                            <i class="fa fa-pencil fs-7"></i>
                            <input type="file" name="page_thumbnail" accept="image/*" />
                            @if(!empty($page->exists) && request()->routeIs('pages.clone'))
                            <input type="hidden" name="page_thumbnail_clone_id" value="{{ $page->getFirstMediaUrl('page_thumbnail') ? $page->getMedia('page_thumbnail')->value('id') : '' }}" />
                            @endif
                            <input type="hidden" name="page_thumbnail_remove" />
                        </label>

                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                            <i class="fa fa-rotate-left"></i>
                        </span>

                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                            <i class="fa fa-times"></i>
                        </span>
                    </div>

                    <div class="form-text">@lang('word.allow_image_type')</div>
                </div>

                {{-- external_thumbnail --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.external_thumbnail')</label>
                    <input type="text" name="external_thumbnail" class="form-control form-control-sm" value="{{ old('external_thumbnail', $page->external_thumbnail) }}" />
                </div>

                {{-- Publish --}}
                <div class="mb-3">
                    <button type="submit" wncms-btn-loading class="btn btn-primary w-100 wncms-submit">
                        @include('backend.parts.submit', ['label' => $submitLabelText ?? __('word.save_all')])
                    </button>
                </div>
            </div>
        </div>

        {{-- Switches --}}
        <div class="card mt-5">
            <div class="card-header border-0 cursor-pointer p-2 p-md-5">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">@lang('word.page_attribute')</h3>
                </div>
            </div>

            <div class="card-body p-2 p-md-5">

                @foreach([
                    // 'extend_theme_layout',
                    // 'include_header',
                    // 'include_footer',
                    'hide_title',
                ] as $option)

                    {{-- option --}}
                    <div class="row mb-1">
                        <div class="col d-flex align-items-center">
                            <div class="form-check form-check-custom form-switch fv-row">
                                <input type="hidden" name="{{ $option }}" value="0">
                                <input class="form-check-input w-35px h-20px" type="checkbox" name="model_attributes[{{ $option }}]" value="1" {{ old($option, $page->getExtraAttribute($option)) ? 'checked' : '' }}/>
                                <label class="form-check-label"></label>
                            </div>
                        </div>
                        <label class="col-auto col-form-label fw-bold fs-6 py-1">@lang('word.' . $option)</label>
                    </div>

                @endforeach


                {{-- Publish --}}
                <div class="mb-3">
                    <button type="submit" wncms-btn-loading class="btn btn-primary w-100 wncms-submit">
                        @include('backend.parts.submit', ['label' => $submitLabelText ?? __('word.save_all')])
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

@push('foot_js')
    <script>
        $(document).ready(function () {
            // Expand all accordions
            $('.expand-all-accordion-items').click(function () {
                const target = $(this).data('target');
                $(target).find('.accordion-collapse:not(.show)').addClass('show');
            });

            // Collapse all accordions
            $('.collapse-all-accordion-items').click(function () {
                const target = $(this).data('target');
                $(target).find('.accordion-collapse.show').removeClass('show');
            });
        });
    </script>
@endpush

@push('foot_css')
    <style>
        #widget-list{
            display:flex;
            flex-wrap:wrap;
        }

        #widget-list .widget-list-item{
            aspect-ratio: 16/11;
        }

        #widget-list .widget-list-item .widget-info-wrapper{
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            border: 1px solid black;
            padding:15px;
        }
        #widget-list .widget-list-item .widget-info-wrapper i{
            font-size:26px;
        }
        #widget-list .widget-list-item .widget-name{
            font-weight: bold;
            margin-top:5px;
        }
    </style>
@endpush