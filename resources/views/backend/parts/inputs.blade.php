@php
    $randomIdSuffix ??= md5(microtime(true) . rand(10000,99999));

    $inputNameKey ??= $option['input_name_key'] ?? 'inputs';
    $optionName = $option['name'] ?? 'input_name_is_not_set';

    if(!empty($has_translation) && !empty($locale_key)){
        $inputName = "translations[{$inputNameKey}][{$locale_key}][$optionName]";
        $inputNameRemove = "translations[{$inputNameKey}][{$locale_key}][{$optionName}_remove]";
        //TODO:: get translation value
        $currentValue = !empty($option['name']) ? ($current_options[$option['name']] ?? '') : '';
    }else{
        $inputName = "{$inputNameKey}[{$optionName}]";
        $inputNameRemove = "{$inputNameKey}[{$optionName}_remove]";
        $currentValue = !empty($option['name']) ? ($current_options[$option['name']] ?? '') : '';
    }

    if(!empty($isPageTemplateValue) && !empty($pageWidgetId) && !empty($option['name'])){

        if($option['name'] == 'widget_key'){
            $currentValue = $current_options[$pageWidgetId]['widget_key'] ?? null;
        }else{
            $currentValue = $current_options[$pageWidgetId]['fields'][$option['name']] ?? null;
        }
    }

@endphp

@if($option['type'] == 'heading' || $option['type'] == 'sub_heading')

    @if($option['type'] == 'heading')

        <div id="{{ $option['label'] ?? ''}}" class="row mb-3 bg-dark rounded mx-0 @if(($option_index??null) !== 0) mt-20 @endif">
            <h2 class="col-lg-4 col-form-label fw-bold fs-3 text-gray-100 d-inline-block">{{ $option['label'] ?? ''}}</h2>
            @if(!empty($option['description']))<h6 class="text-muted">{!! $option['description'] !!}</h6>@endif
        </div>

    @elseif($option['type'] == 'sub_heading')

        <div id="{{ $option['label'] ?? ''}}" class="row rounded mw-100 mx-0 mb-3 mt-10">
            <h3 class="col-lg-4 col-form-label fw-bold fs-2 text-gray-700 text-decoration-underline">{{ $option['label'] ?? ''}}</h3>
            @if(!empty($option['description']))<h6 class="text-gray-900">{!! $option['description'] !!}</h6>@endif
        </div>
        
    @endif

@elseif($option['type'] == 'display_image')

    <img class="rounded my-3" src="{{ asset($option['path'] ?? 'wncms/images/placeholders/upload.png') }}"  style="max-width: 100%;width:{{ $option['width'] ?? '' }};height:{{ $option['height'] ?? '' }}">

@elseif($option['type'] == 'hidden')

    <input type="hidden" name="{{ $inputName }}" value="{{ $currentValue }}">

@elseif($option['type'] == 'inline')

    @if(!empty($option['repeat']))
        @for($i=1;$i<=$option['repeat'];$i++)
            @php
                $suffix = !empty($option['repeat']) ? "_{$i}" : '';
                $newOption = $option;
                if(!empty($newOption['sub_items']) && !empty($option['repeat'])){
                    foreach($newOption['sub_items'] as &$newOptionSubItem){
                        $newOptionSubItem['name'] .= $suffix; 
                    }
                }
            @endphp

            <div class="row mb-3 mw-100 mx-0">
                @foreach($newOption['sub_items'] ?? [] as $sub_item)
                    {{-- <div class="col-lg-{{ floor(12 / (count($option['sub_items'] ?? []) ?: 1)) }}"> --}}
                    <div class="col">
                        @include('wncms::backend.parts.inputs', ['option' => $sub_item])
                    </div>
                @endforeach
            </div>
        @endfor
    @else
        <div class="row mb-3 mw-100 mx-0">
            @foreach($option['sub_items'] ?? [] as $sub_item)
                {{-- <div class="col-lg-{{ floor(12 / (count($option['sub_items'] ?? []) ?: 1)) }}"> --}}
                <div class="col">
                    @include('wncms::backend.parts.inputs', ['option' => $sub_item])
                </div>
            @endforeach
        </div>
    @endif

@else

    <div class="row mb-3 mw-100 mx-0 @if(!empty($option['align_items_center'])) align-items-center @endif">
        <label class="col-lg-3 col-form-label fw-bold fs-6 text-nowrap text-truncate @required(!empty($option['required']))" title="{{ $option['label'] ?? $option['name'] }}">
            {{ $option['label'] ?? $option['name'] }}
            @if(gss('show_developer_hints')) <br><span class="text-secondary small">{{ $option['name'] ?? '' }}</span>@endif
        </label>

        <div class="col-lg-9 @if($option['type'] == 'boolean') d-flex align-items-center @endif">

            @if($option['type'] == 'text' || $option['type'] == 'number')

                <input type="{{ $option['type'] }}" 
                    name="{{ $inputName }}" 
                    class="form-control form-control-sm" 
                    value="{{ $currentValue }}"
                    @disabled(!empty($option['disabled']) || !empty($disabled))
                    @required(!empty($option['required']))
                    @if(!empty($option['placeholder']) || !empty($disabled)) placeholder="{{ $option['placeholder'] }}" @endif/>

            @elseif($option['type'] == 'image')

                <div class="image-input image-input-outline mw-100 {{ !empty($currentValue) ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url('{{ asset('wncms/images/placeholders/upload.png') }}');background-position:center">
                    <div class="image-input-wrapper w-400px h-125px mw-100" style="background-image: {{!empty($currentValue) ? 'url("'. $currentValue .'")' : 'none' }};background-size: 100% 100%;width:{{ $option['width'] ?? 400 }}px !important;height:{{ $option['height'] ?? 125 }}px !important;"></div>

                    @if(empty($option['disabled']) && empty($disabled))
                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                            <i class="fa fa-pencil fs-7"></i>
                            <input type="file" name="{{ $inputName }}" accept="image/*"/>
                            <input type="hidden" @if(!empty($has_translation) && !empty($locale_key)) class="{{ $locale_key }}_{{ $option['name'] }}_remove" @endif name="{{ $inputNameRemove }}"/>
                        </label>
                    @endif

                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="@lang('wncms::word.edit')">
                        <i class="fa fa-times"></i>
                    </span>

                    @if(empty($option['disabled']) && empty($disabled))
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow @if(!empty($has_translation) && !empty($locale_key)){{ $locale_key }}_{{ $option['name'] }}_remove @endif" 
                            @if(!empty($has_translation) && !empty($locale_key)) data-wncms-action="remove_translated_image" @endif 
                            data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="@lang('wncms::word.remove')">
                            <i class="fa fa-times"></i>
                        </span>

                        @if(!empty($has_translation) && !empty($locale_key))
                            @push('foot_js')
                                <script>
                                    $(document).ready(function() {
                                        $(".{{ $locale_key }}_{{ $option['name'] }}_remove [data-wncms-action='remove_translated_image']").click(function() {
                                            $("input[name='translations[inputs][{{ $locale_key }}][{{ $option['name'] }}_remove]']").val(1);
                                        });
                                    });
                                </script>
                            @endpush
                        @endif
                    @endif
                </div>

            @elseif($option['type'] == 'select')

                @if($option['options'] == 'pages')
        
                    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
                        @if(empty($option['required'])) <option value="">@lang('wncms::word.please_select')</option> @endif
                        @foreach(wncms_get_pages($website ?? null) as $page)
                            <option value="{{ $page->id }}" @if($currentValue == $page->id) selected @endif>{{ $page->title }}</option>
                        @endforeach
                    </select>
            
                @elseif($option['options'] == 'menus')

                    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
                        @if(empty($option['required'])) <option value="">@lang('wncms::word.please_select')</option> @endif
                        @foreach(wncms_get_menus(null, $website ?? null) as $menu)
                            <option value="{{ $menu->id }}" @if($currentValue == $menu->id) selected @endif>{{ $menu->name }}</option>
                        @endforeach
                    </select>
      
                @elseif($option['options'] == 'positions')

                    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
                        @if(empty($option['required'])) <option value="">@lang('wncms::word.please_select')</option> @endif
                        @foreach(\Wncms\Models\Advertisement::POSITIONS ?? [] as $option_key => $option_value)
                            <option value="{{ $option_value }}" @if(($current_options[$option['name']] ?? '') == $option_value) selected @endif>
                                @if(isset($option['translate_option']) && $option['translate_option'] === false) {{ $option_value }} @else @lang('wncms::word.' . $option_value) @endif
                            </option>
                        @endforeach
                    </select>

                @elseif($option['options'] == 'contact_forms')
                    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
                        @if(empty($option['required'])) <option value="">@lang('wncms::word.please_select')</option> @endif
                        @foreach($wncms->contact_form()->getList() as $contact_form)
                            <option value="{{ $contact_form->id }}" @if($currentValue == $contact_form->id) selected @endif>{{ $contact_form->name }}</option>
                        @endforeach
                    </select>
                @else
                    
                    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
                        @if(empty($option['required'])) <option value="">@lang('wncms::word.please_select')</option> @endif
                        @foreach($option['options'] ?? [] as $option_key => $option_value)
                            <option value="{{ $option_value }}" @if(($current_options[$option['name']] ?? '') == $option_value) selected @endif>
                                @if(isset($option['translate_option']) && $option['translate_option'] === false) {{ $option_value }} @else @lang('wncms::word.' . $option_value) @endif
                            </option>
                        @endforeach
                    </select>

                @endif

            @elseif($option['type'] == 'boolean')

                <div class="form-check form-check-solid form-check-custom form-switch">
                    <input type="hidden"
                        name="{{ $inputName }}"
                        value="0">
                    <input class="form-check-input w-35px h-20px" type="checkbox" id="{{ $option['name'] }}"
                        name="{{ $inputName }}"
                        value="1" {{ ($currentValue ?? false) ? 'checked' : '' }}/>
                    <label class="form-check-label" for="{{ $option['name'] }}"></label>
                </div>

            @elseif($option['type'] == 'editor')

                <textarea id="editor_{{ $option['name'] }}" name="{{ $inputName }}" class="tox-target">{{ $currentValue }}</textarea>
      
                <script>
                    window.addEventListener('DOMContentLoaded', (event) => {
                        var options = {
                            selector: "#editor_{{ $option['name'] }}", 
                            height:480,
                            menubar: true,
                            promotion: false,
                            language: 'zh_TW',
                            toolbar: [
                                "styles fontsize fontsizeinput forecolor backcolor bold italic underline lineheight alignleft aligncenter alignright alignjustify wordcount accordion anchor undo redo link unlink bullist numlist outdent indent blockquote image emoticons fullscreen insertdatetime searchreplace table code"
                            ],
                            plugins : "lists advlist code image table wordcount link emoticons fullscreen insertdatetime searchreplace accordion anchor",
                            images_file_types: 'jpg,svg,webp,png',
                            images_upload_url: '{{ route("uploads.image") }}',
                            // images_upload_base_path: '/some/basepath',
                            image_class_list: [
                                { title: 'img-fluid', value: 'img-fluid' },


                            ],
                            image_title: true,
                            automatic_uploads: true,
                            toolbar_sticky: true,
                            toolbar_sticky_offset: 0,
                            content_style: 'img { max-width: 100%; height: auto; }' ,
                        }


                        if ( KTThemeMode.getMode() === "dark" ) {
                            options["skin"] = "oxide-dark";
                            options["content_css"] = "dark";
                        }

                        tinymce.init(options);
                    });

                </script>

            @elseif($option['type'] == 'textarea')

                <textarea  name="{{ $inputName }}" class="form-control" rows="6"  @disabled(!empty($option['disabled']) || !empty($disabled)) >{{ $currentValue }}</textarea>
       
            @elseif($option['type'] == 'color')

                <div class="input-group mb-5">
                    <input type="text" 
                        name="{{ $inputName }}"
                        class="form-control form-control-sm"
                        value="{{ $currentValue }}"
                        @disabled(!empty($option['disabled']) || !empty($disabled))
                    />
                    <div class="colorpicker-input" data-input="inputs[{{ $option['name'] }}]" data-current="{{ $current_options[$option['name']] ?? '#ccc' }}"></div>
                </div>

            @elseif($option['type'] == 'tagify')

                @if($option['options'] == 'tags')
                    @php
                        $tags = wncms()->tag()->getList(tagType: $option['tag_type'] ?? null)->map(function ($tag) {
                            return ['value' => $tag->id, 'name' => $tag->name];
                        })->toArray();
                    @endphp
             
                    <input id="tagify_{{ $option['name'] }}" class="form-control form-control-sm p-0" 
                        name="{{ $inputName }}" 
                        value="{{ $currentValue }}"
                        @if(!empty($option['required'])) required @endif
                        @disabled(!empty($option['disabled']) || !empty($disabled))
                    />

                    @push('foot_js')
                        <script type="text/javascript">
                            window.addEventListener('DOMContentLoaded', (event) => {
                                var input = document.querySelector("#tagify_{{ $option['name'] }}");
                                var tags = @json($tags);

                                // Initialize Tagify
                                tagify = new Tagify(input, {
                                    whitelist: tags,
                                    enforceWhitelist:{{ isset($option['whitelist_tag_only']) && $option['whitelist_tag_only'] == false  ? "false" : "true"}},
                                    skipInvalid: true,
                                    duplicates: false,
                                    tagTextProp: 'name',
                                    maxTags: {{ $option['limit'] ?? 999 }},
                                    dropdown: {
                                        maxItems: 100,
                                        mapValueTo : 'name',
                                        classname: "tagify__inline__suggestions",
                                        enabled: 0,
                                        closeOnSelect: false,
                                        searchKeys: ['name', 'value'],
                                    },
                                });
                        
                                // handle value changes
                                input.addEventListener('change', function onChange(e){
                                    console.log(e.target.value);
                                });

                                // using 3-party script "dragsort"
                                // https://github.com/yairEO/dragsort
                                var dragsort = new DragSort(tagify.DOM.scope, {
                                    selector:'.'+tagify.settings.classNames.tag,
                                    callbacks: {
                                        dragEnd: onDragEnd
                                    }
                                })

                                function onDragEnd(elm){
                                    tagify.updateValueByDOMTags()
                                }
                            });
                        </script>
                    @endpush

                @elseif($option['options'] == 'pages')

                    @php
                        $pages = wncms()->page()->getList( websiteId:$website->id)->map(function ($page) {
                            return ['value' => $page->id, 'name' => $page->title];
                        })->toArray();

                        $currentPages =  wncms()->page()->getList(ids: explode("," , $currentValue), websiteId:$website->id)
                            ->pluck('title','id')
                            ->toArray();

                        $current_options[$option['name']] = implode(",", $currentPages);

                    @endphp

                    <input id="tagify_{{ $option_index }}" 
                        class="form-control form-control-solid" 
                        name="{{ $inputName }}"
                        value="{{ $currentValue }}"
                        @if(!empty($option['required'])) required @endif
                        @if(!empty($option['disabled'])) disabled @endif
                    />

                    <script type="text/javascript">
                        window.addEventListener('DOMContentLoaded', (event) => {
                            var input = document.querySelector("#tagify_{{ $option_index }}");
                            var pages = @json($pages);

                            // Initialize Tagify
                            tagify = new Tagify(input, {
                                whitelist: pages,
                                enforceWhitelist:{{ isset($option['whitelist_tag_only']) && $option['whitelist_tag_only'] == false  ? "false" : "true"}},
                                // skipInvalid: true,
                                // duplicates: false,
                                tagTextProp: 'name',
                                maxTags: {{ $option['limit'] ?? 999 }},
                                dropdown: {
                                    maxItems: 100,
                                    mapValueTo : 'name',
                                    classname: "tagify__inline__suggestions",
                                    enabled: 0,
                                    closeOnSelect: false,
                                    searchKeys: ['name', 'value'],
                                },
                            });

                            // handle value changes
                            input.addEventListener('change', function onChange(e){
                                console.log(e.target.value);
                            });

                            // using 3-party script "dragsort"
                            // https://github.com/yairEO/dragsort
                            var dragsort = new DragSort(tagify.DOM.scope, {
                                selector:'.'+tagify.settings.classNames.tag,
                                callbacks: {
                                    dragEnd: onDragEnd
                                }
                            })

                            function onDragEnd(elm){
                                tagify.updateValueByDOMTags()
                            }
                        });


                    </script>

                @elseif($option['options'] == 'posts')
                    @php
                        $posts = wncms()->post()->getList(websiteId:$website->id)->map(function ($post) {
                            return ['value' => $post->id, 'name' => $post->title];
                        })->toArray();

                        $currentPosts =  wncms()->post()->getList(ids: explode("," , $currentValue), websiteId:$website->id)
                            ->pluck('title','id')
                            ->toArray();

                        $current_options[$option['name']] = implode(",", $currentPosts);

                    @endphp

                    <input id="tagify_{{ $option_index }}" 
                        class="form-control form-control-lg p-0" 
                        name="{{ $inputName }}"
                        value="{{ $currentValue }}"
                        @if(!empty($option['required'])) required @endif
                        @disabled(!empty($option['disabled']) || !empty($disabled))
                    />

                    <script type="text/javascript">
                        window.addEventListener('DOMContentLoaded', (event) => {
                            var input = document.querySelector("#tagify_{{ $option_index }}");
                            var posts = @json($posts);

                            // Initialize Tagify
                            tagify = new Tagify(input, {
                                whitelist: posts,
                                enforceWhitelist:{{ isset($option['whitelist_tag_only']) && $option['whitelist_tag_only'] == false  ? "false" : "true"}},
                                // skipInvalid: true,
                                // duplicates: false,
                                tagTextProp: 'name',
                                maxTags: {{ $option['limit'] ?? 999 }},
                                dropdown: {
                                    maxItems: 100,
                                    mapValueTo : 'name',
                                    classname: "tagify__inline__suggestions",
                                    enabled: 0,
                                    closeOnSelect: false,
                                    searchKeys: ['name', 'value'],
                                },
                            });

                            // handle value changes
                            input.addEventListener('change', function onChange(e){
                                console.log(e.target.value);
                            });

                            // using 3-party script "dragsort"
                            // https://github.com/yairEO/dragsort
                            var dragsort = new DragSort(tagify.DOM.scope, {
                                selector:'.'+tagify.settings.classNames.tag,
                                callbacks: {
                                    dragEnd: onDragEnd
                                }
                            })

                            function onDragEnd(elm){
                                tagify.updateValueByDOMTags()
                            }
                        });


                    </script>

                @elseif($option['options'] == 'menus')

                    <input id="tagify_{{ $option_index }}" 
                        class="form-control form-control-solid" 
                        name="{{ $inputName }}"
                        value="@if(!empty($current_options[$option['name']]) && $current_options[$option['name']]){{ wncms_get_menus($current_options[$option['name']], $website)->pluck('name')->implode(',') ?? '' }}@endif"
                        @if(!empty($option['required'])) required @endif
                        @if(!empty($option['disabled'])) disabled @endif
                    />

                    <script type="text/javascript">
                        window.addEventListener('DOMContentLoaded', (event) => {
                            //Tagify
                            var input = document.querySelector("#tagify_{{ $option_index }}");

                            //生成列表
                            var whitelist = @json(wncms_get_menus($website)->map(function($menu) {
                                    return ['id' => $menu->id, 'name' => $menu->name];
                                })->toArray());

                            // Initialize Tagify script on the above inputs
                            new Tagify(input, {
                                whitelist: whitelist.map(function(menu) {
                                    return menu.name;
                                }),
                                enforceWhitelist: true,
                                maxTags: {{ $option['limit'] ?? 999 }},
                                valueProperty: "id",
                                originalInputValueFormat: function(valuesArr) {
                                    return valuesArr.map(function(item) {
                                        for (var i = 0; i < whitelist.length; i++) {
                                            if (whitelist[i].name == item.value) {
                                                return whitelist[i].id;
                                            }
                                        }
                                        return null;
                                    }).join(',');
                                },
                                dropdown: {
                                    maxItems: 100,           // <- mixumum allowed rendered suggestions
                                    classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                                    enabled: 0,            // <- show suggestions on focus
                                    closeOnSelect: false,    // <- do not hide the suggestions dropdown once an item has been selected
                                },
                            });


                            input.addEventListener('change', onChange)

                            function onChange(e){
                            // outputs a String
                            console.log(e.target.value)
                            }
                        });
                    </script>
                @else

                    <input id="tagify_{{ $option_index }}" 
                        class="form-control form-control-sm p-0" 
                        name="{{ $inputName }}"
                        value="{{ $currentValue }}"
                        @if(!empty($option['required'])) required @endif
                        @disabled(!empty($option['disabled']) || !empty($disabled))
                    />

                    <script type="text/javascript">
                        window.addEventListener('DOMContentLoaded', (event) => {
                            var input = document.querySelector("#tagify_{{ $option_index }}");
                            var whitelist = @json($option['options']);

                            // Initialize Tagify
                            tagify = new Tagify(input, {
                                whitelist: whitelist,
                                enforceWhitelist:{{ isset($option['whitelist_tag_only']) && $option['whitelist_tag_only'] == false  ? "false" : "true"}},
                                // skipInvalid: true,
                                // duplicates: false,
                                tagTextProp: 'name',
                                maxTags: {{ $option['limit'] ?? 999 }},
                                dropdown: {
                                    maxItems: 100,
                                    mapValueTo : 'name',
                                    classname: "tagify__inline__suggestions",
                                    enabled: 0,
                                    closeOnSelect: false,
                                    searchKeys: ['name', 'value'],
                                },
                            });

                            // handle value changes
                            input.addEventListener('change', function onChange(e){
                                console.log(e.target.value);
                            });

                            // using 3-party script "dragsort"
                            // https://github.com/yairEO/dragsort
                            var dragsort = new DragSort(tagify.DOM.scope, {
                                selector:'.'+tagify.settings.classNames.tag,
                                callbacks: {
                                    dragEnd: onDragEnd
                                }
                            })

                            function onDragEnd(elm){
                                tagify.updateValueByDOMTags()
                            }
                        });


                    </script>

                @endif

            @elseif($option['type'] == 'accordion')
                
                @if(!empty($option['repeat']) && $option['repeat'] > 1)
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-dark fw-bold expand-all-accordion-items" data-target=".accordion_{{ $option['id'] . $randomIdSuffix }}">@lang('wncms::word.expand_all')</button>
                        <button type="button" class="btn btn-sm btn-dark fw-bold collapse-all-accordion-items" data-target=".accordion_{{ $option['id'] . $randomIdSuffix }}">@lang('wncms::word.collapse_all')</button>
                    </div>
                @endif

                <div class="accordion accordion_{{ $option['id'] . $randomIdSuffix }} mb-1" id="{{ $option['id'] . $randomIdSuffix }}">

                    @for($i = 1; $i <= ($option['repeat'] ?? 1); $i++)
                    
                        @php
                            $suffix = !empty($option['repeat']) ? "_{$i}" : '';
                            $labelSuffix = !empty($option['repeat']) ? $i : '';
                        @endphp

                        <div class="accordion-item">

                            @if(!empty($option['sortable']))
                                <input class="item-order-input" type="hidden" name="{{ $inputNameKey }}[{{ $option['id'] }}_item_order_{{ $i }}]" value="{{ $i }}">
                            @endif

                            <h2 class="accordion-header" id="{{ $option['id'] . $randomIdSuffix . $suffix }}_header_1">
                                <button class="accordion-button accordion-handle fs-4 text-gray-800 fw-bold p-3 bg-gray-300" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $option['id'] . $randomIdSuffix . $suffix }}_body_1" aria-expanded="true" aria-controls="{{ $option['id'] . $randomIdSuffix . $suffix }}_body_1">
                                    @if(!empty($option['sortable']))<i class="fa-solid fa-bars me-2"></i>@endif {{ $option['label'] . $labelSuffix }}
                                </button>
                            </h2>

                            <div id="{{ $option['id'] . $randomIdSuffix . $suffix }}_body_1" class="accordion-collapse collapse" aria-labelledby="{{ $option['id'] . $randomIdSuffix . $suffix }}_header_1" data-bs-parent="#{{ $option['id'] . $randomIdSuffix }}">
                                <div class="accordion-body p-3">
                                    @foreach($option['content'] as $tabIndex => $tabConten)
                                        @php
                                            //accordion content
                                            $indexedTabContent = $tabConten;

                                            if(!empty($indexedTabContent['name'])){
                                                $indexedTabContent['name'] .= $suffix;
                                            }

                                            if(!empty($indexedTabContent['sub_items']) && !empty($option['repeat'])){
                                                foreach($indexedTabContent['sub_items'] as &$subItem){
                                                    $subItem['name'] .= $suffix; 
                                                    // dd($subItem['name']);
                                                }
                                            }

                                            $indexedTabContent['input_name_key'] = $inputNameKey;
                                        @endphp
                                        
                                        @include('wncms::backend.parts.inputs', [
                                            'option' => $indexedTabContent,
                                            'isPageTemplateValue' => $isPageTemplateValue ?? false,
                                            'pageWidgetId' => $pageWidgetId ?? null,
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endfor

                    @if(!empty($option['sortable']))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                // Initialize Sortable for the accordion
                                new Sortable(document.getElementById('{{ $option['id'] . $randomIdSuffix }}'), {
                                    handle: '.accordion-header', // Class of the handle for dragging
                                    animation: 150, // Animation duration in milliseconds
                                    onEnd: function (evt) {
                                        
                                        var itemOrderInput = $(evt.item).find('.item-order-input');
                                        // console.log("itemOrderInput.length = " + itemOrderInput.length);

                                        if(itemOrderInput.length){
                                            // console.log('hidden input is found')
                                            updateHiddenInputValues(evt.from.children);
                                            // console.log('updated hidden value')
                                        }

                                        // evt.oldIndex is the old index of the dragged item
                                        // evt.newIndex is the new index of the dragged item

                                        // Perform actions on change here
                                        // console.log('Accordion item order changed!');
                                        // console.log('Old Index:', evt.oldIndex);
                                        // console.log('New Index:', evt.newIndex);
                                    },
                                });
                            });

                            function updateHiddenInputValues(items) {
                                console.log(items)
                                // Update the hidden input values based on the new order
                                items.forEach(function (item, index) {
                                    const hiddenInput = item.querySelector('.item-order-input');
                                    if (hiddenInput) {
                                        // Update the value of the hidden input based on the new order
                                        hiddenInput.value = index + 1;
                                    }
                                });
                            }
                        </script>
                    @endif
                </div>

            @endif

            @if(!empty($option['description']))<div class="text-muted p-2">{{ $option['description'] }}</div>@endif

        </div>
    </div>

@endif