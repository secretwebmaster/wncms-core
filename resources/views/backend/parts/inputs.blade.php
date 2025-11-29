@php
    $randomIdSuffix ??= md5(microtime(true) . rand(10000, 99999));

    $inputNameKey ??= $option['input_name_key'] ?? 'inputs';
    $optionName = $option['name'] ?? 'input_name_is_not_set';

    if (!empty($has_translation) && !empty($locale_key)) {
        $inputName = "translations[{$inputNameKey}][{$locale_key}][$optionName]";
        $inputNameRemove = "translations[{$inputNameKey}][{$locale_key}][{$optionName}_remove]";
        //TODO:: get translation value
        $currentValue = !empty($option['name']) ? $current_options[$option['name']] ?? '' : '';
    } else {
        $inputName = "{$inputNameKey}[{$optionName}]";
        $inputNameRemove = "{$inputNameKey}[{$optionName}_remove]";
        $currentValue = !empty($option['name']) ? $current_options[$option['name']] ?? '' : '';
    }

    if (!empty($isPageTemplateValue) && !empty($pageWidgetId) && !empty($option['name'])) {
        if ($option['name'] == 'widget_key') {
            $currentValue = $current_options[$pageWidgetId]['widget_key'] ?? null;
        } else {
            $currentValue = $current_options[$pageWidgetId]['fields'][$option['name']] ?? null;
        }
    }
@endphp

@if ($option['type'] == 'heading')
    <div id="{{ $option['label'] ?? '' }}" class="row mb-3 bg-dark rounded mx-0 @if (($option_index ?? null) !== 0) mt-20 @endif">
        <h2 class="col-lg-4 col-form-label fw-bold fs-3 text-gray-100 d-inline-block">{{ $option['label'] ?? '' }}</h2>
        @if (!empty($option['description']))
            <h6 class="text-muted">{!! $option['description'] !!}</h6>
        @endif
    </div>
@elseif($option['type'] == 'sub_heading')
    <div id="{{ $option['label'] ?? '' }}" class="row rounded mw-100 mx-0 mb-3 mt-10">
        <h3 class="col-lg-4 col-form-label fw-bold fs-2 text-gray-700 text-decoration-underline">{{ $option['label'] ?? '' }}</h3>
        @if (!empty($option['description']))
            <h6 class="text-gray-900">{!! $option['description'] !!}</h6>
        @endif
    </div>
@elseif($option['type'] == 'display_image')
    <div class="row mb-3 mw-100 mx-0">
        @if (!empty($option['col']))
            <div class="col-{{ $option['col'] }}">
                <img class="rounded my-3" src="{{ asset($option['path'] ?? 'wncms/images/placeholders/upload.png') }}" style="max-width: 100%;width:{{ $option['width'] ?? '' }}px;height:{{ $option['height'] ?? '' }}px;">
            </div>
        @elseif(!empty($option['width']) || !empty($option['height']))
            <div>
                <img class="rounded my-3" src="{{ asset($option['path'] ?? 'wncms/images/placeholders/upload.png') }}" style="width:{{ $option['width'] ?? '' }}px;height:{{ $option['height'] ?? '' }}px;">
            </div>
        @else
            <div>
                <img class="rounded my-3" src="{{ asset($option['path'] ?? 'wncms/images/placeholders/upload.png') }}" style="max-width: 100%;">
            </div>
        @endif
    </div>
@elseif($option['type'] == 'hidden')
    <input type="hidden" name="{{ $inputName }}" value="{{ $currentValue }}">
@elseif($option['type'] == 'inline')
    @if (!empty($option['repeat']))
        @for ($i = 1; $i <= $option['repeat']; $i++)
            @php
                $suffix = "_{$i}";
                $newOption = $option;
                if (!empty($newOption['sub_items']) && !empty($option['repeat'])) {
                    foreach ($newOption['sub_items'] as &$newOptionSubItem) {
                        $newOptionSubItem['name'] .= $suffix;
                    }
                }
            @endphp

            <div class="row mb-3 mw-100 mx-0">
                @foreach ($newOption['sub_items'] ?? [] as $sub_item)
                    {{-- <div class="col-lg-{{ floor(12 / (count($option['sub_items'] ?? []) ?: 1)) }}"> --}}
                    <div class="col">
                        @include('wncms::backend.parts.inputs', ['option' => $sub_item])
                    </div>
                @endforeach
            </div>
        @endfor
    @else
        <div class="row mb-3 mw-100 mx-0">
            @foreach ($option['sub_items'] ?? [] as $sub_item)
                {{-- <div class="col-lg-{{ floor(12 / (count($option['sub_items'] ?? []) ?: 1)) }}"> --}}
                <div class="col">
                    @include('wncms::backend.parts.inputs', ['option' => $sub_item])
                </div>
            @endforeach
        </div>
    @endif
@else
    <div class="row mb-3 mw-100 mx-0 @if (!empty($option['align_items_center'])) align-items-center @endif">
        <label class="col-lg-3 col-form-label fw-bold fs-6 text-nowrap text-truncate @required(!empty($option['required']))" title="{{ $option['label'] ?? $option['name'] }}">
            {{ $option['label'] ?? $option['name'] }}
            @if (gss('show_developer_hints'))
                <br><span class="text-secondary small">{{ $option['name'] ?? '' }}</span>
            @endif
        </label>

        <div class="col-lg-9 @if ($option['type'] == 'boolean') d-flex align-items-center @endif">
            @if ($option['type'] == 'text' || $option['type'] == 'number')

                <input type="{{ $option['type'] }}"
                    name="{{ $inputName }}"
                    class="form-control form-control-sm"
                    value="{{ $currentValue }}"
                    @disabled(!empty($option['disabled']) || !empty($disabled))
                    @required(!empty($option['required']))
                    @if (!empty($option['placeholder']) || !empty($disabled)) placeholder="{{ $option['placeholder'] }}" @endif />
            @elseif($option['type'] == 'image')
                <div class="image-input image-input-outline mw-100 {{ !empty($currentValue) ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url('{{ asset('wncms/images/placeholders/upload.png') }}');background-position:center">
                    <div class="image-input-wrapper w-400px h-125px mw-100" style="background-image: {{ !empty($currentValue) ? 'url("' . $currentValue . '")' : 'none' }};background-size: 100% 100%;width:{{ $option['width'] ?? 400 }}px !important;height:{{ $option['height'] ?? 125 }}px !important;"></div>

                    @if (empty($option['disabled']) && empty($disabled))
                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                            <i class="fa fa-pencil fs-7"></i>
                            <input type="file" name="{{ $inputName }}" accept="image/*" />
                            <input type="hidden" @if (!empty($has_translation) && !empty($locale_key)) class="{{ $locale_key }}_{{ $option['name'] }}_remove" @endif name="{{ $inputNameRemove }}" />
                        </label>
                    @endif

                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="@lang('wncms::word.edit')">
                        <i class="fa fa-times"></i>
                    </span>

                    @if (empty($option['disabled']) && empty($disabled))
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow @if (!empty($has_translation) && !empty($locale_key)) {{ $locale_key }}_{{ $option['name'] }}_remove @endif"
                            @if (!empty($has_translation) && !empty($locale_key)) data-wncms-action="remove_translated_image" @endif
                            data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="@lang('wncms::word.remove')">
                            <i class="fa fa-times"></i>
                        </span>

                        @if (!empty($has_translation) && !empty($locale_key))
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
                @if ($option['options'] == 'pages')

                    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
                        @if (empty($option['required']))
                            <option value="">@lang('wncms::word.please_select')</option>
                        @endif
                        @foreach (wncms()->page()->getList(['website_id' => $website?->id, 'cache' => false]) as $page)
                            <option value="{{ $page->id }}" @selected($currentValue == $page->id)>
                                {{ $page->title }}
                            </option>
                        @endforeach
                    </select>
                @elseif($option['options'] == 'menus')
                    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
                        @if (empty($option['required']))
                            <option value="">@lang('wncms::word.please_select')</option>
                        @endif
                        @foreach (wncms()->menu()->getList([], $website?->id) as $menu)
                            <option value="{{ $menu->id }}" @if ($currentValue == $menu->id) selected @endif>{{ $menu->name }}</option>
                        @endforeach
                    </select>
                @elseif($option['options'] == 'positions')
                    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
                        @if (empty($option['required']))
                            <option value="">@lang('wncms::word.please_select')</option>
                        @endif
                        @foreach (\Wncms\Models\Advertisement::POSITIONS ?? [] as $option_key => $option_value)
                            <option value="{{ $option_value }}" @if (($current_options[$option['name']] ?? '') == $option_value) selected @endif>
                                @if (isset($option['translate_option']) && $option['translate_option'] === false)
                                    {{ $option_value }}
                                @else
                                    @lang('wncms::word.' . $option_value)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    {{-- @elseif($option['options'] == 'contact_forms')
                    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
                        @if (empty($option['required']))
                            <option value="">@lang('wncms::word.please_select')</option>
                        @endif
                        @foreach ($wncms->contact_form()->getList() as $contact_form)
                            <option value="{{ $contact_form->id }}" @if ($currentValue == $contact_form->id) selected @endif>{{ $contact_form->name }}</option>
                        @endforeach
                    </select> --}}
                @else
                    <select name="{{ $inputName }}" class="form-select form-select-sm" @disabled(!empty($option['disabled']) || !empty($disabled)) @required(!empty($option['required']))>
                        @if (empty($option['required']))
                            <option value="">@lang('wncms::word.please_select')</option>
                        @endif
                        @foreach ($option['options'] ?? [] as $option_key => $option_value)
                            <option value="{{ $option_value }}" @if (($current_options[$option['name']] ?? '') == $option_value) selected @endif>
                                @if (isset($option['translate_option']) && $option['translate_option'] === false)
                                    {{ $option_value }}
                                @else
                                    @lang($themeId . '::word.' . $option_value)
                                @endif
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
                        value="1" {{ $currentValue ?? false ? 'checked' : '' }} />
                    <label class="form-check-label" for="{{ $option['name'] }}"></label>
                </div>
            @elseif($option['type'] == 'editor')
                <textarea id="editor_{{ $option['name'] }}" name="{{ $inputName }}" class="tox-target">{{ $currentValue }}</textarea>

                <script>
                    window.addEventListener('DOMContentLoaded', (event) => {
                        var options = {
                            selector: "#editor_{{ $option['name'] }}",
                            height: 480,
                            menubar: true,
                            promotion: false,
                            language: 'zh_TW',
                            toolbar: [
                                "styles fontsize fontsizeinput forecolor backcolor bold italic underline lineheight alignleft aligncenter alignright alignjustify wordcount accordion anchor undo redo link unlink bullist numlist outdent indent blockquote image emoticons fullscreen insertdatetime searchreplace table code"
                            ],
                            plugins: "lists advlist code image table wordcount link emoticons fullscreen insertdatetime searchreplace accordion anchor",
                            images_file_types: 'jpg,svg,webp,png',
                            images_upload_url: '{{ route('uploads.image') }}',
                            // images_upload_base_path: '/some/basepath',
                            image_class_list: [{
                                    title: 'img-fluid',
                                    value: 'img-fluid'
                                },


                            ],
                            image_title: true,
                            automatic_uploads: true,
                            toolbar_sticky: true,
                            toolbar_sticky_offset: 0,
                            content_style: 'img { max-width: 100%; height: auto; }',
                        }


                        if (KTThemeMode.getMode() === "dark") {
                            options["skin"] = "oxide-dark";
                            options["content_css"] = "dark";
                        }

                        tinymce.init(options);
                    });
                </script>
            @elseif($option['type'] == 'textarea')
                <textarea name="{{ $inputName }}" class="form-control" rows="6" @disabled(!empty($option['disabled']) || !empty($disabled))>{{ $currentValue }}</textarea>
            @elseif($option['type'] == 'color')
                <div class="input-group mb-5">
                    <input type="text"
                        name="{{ $inputName }}"
                        class="form-control form-control-sm"
                        value="{{ $currentValue }}"
                        placeholder="{{ $option['placeholder'] ?? '' }}"
                        @disabled(!empty($option['disabled']) || !empty($disabled)) />
                    <div class="colorpicker-input" data-input="{{ $inputName }}" data-current="{{ $current_options[$option['name']] ?? '#ccc' }}"></div>
                </div>
            @elseif($option['type'] == 'tagify')
                @if ($option['options'] == 'tags')
                    @php
                        $tags = wncms()
                            ->tag()
                            ->getList([
                                'tag_type' => $option['tag_type'] ?? null,
                                'cache' => false,
                            ])
                            ->map(function ($tag) {
                                return ['value' => $tag->id, 'name' => $tag->name];
                            })
                            ->toArray();
                    @endphp

                    <input id="tagify_{{ $option['name'] }}" class="form-control form-control-sm p-0"
                        name="{{ $inputName }}"
                        value="{{ $currentValue }}"
                        @if (!empty($option['required'])) required @endif
                        @disabled(!empty($option['disabled']) || !empty($disabled)) />

                    @push('foot_js')
                        <script type="text/javascript">
                            window.addEventListener('DOMContentLoaded', (event) => {
                                var input = document.querySelector("#tagify_{{ $option['name'] }}");
                                var tags = @json($tags);

                                // Initialize Tagify
                                tagify = new Tagify(input, {
                                    whitelist: tags,
                                    enforceWhitelist: {{ isset($option['whitelist_tag_only']) && $option['whitelist_tag_only'] == false ? 'false' : 'true' }},
                                    skipInvalid: true,
                                    duplicates: false,
                                    tagTextProp: 'name',
                                    maxTags: {{ $option['limit'] ?? 999 }},
                                    dropdown: {
                                        maxItems: 100,
                                        mapValueTo: 'name',
                                        classname: "tagify__inline__suggestions",
                                        enabled: 0,
                                        closeOnSelect: false,
                                        searchKeys: ['name', 'value'],
                                    },
                                });

                                // handle value changes
                                input.addEventListener('change', function onChange(e) {
                                    console.log(e.target.value);
                                });

                                // using 3-party script "dragsort"
                                // https://github.com/yairEO/dragsort
                                var dragsort = new DragSort(tagify.DOM.scope, {
                                    selector: '.' + tagify.settings.classNames.tag,
                                    callbacks: {
                                        dragEnd: onDragEnd
                                    }
                                })

                                function onDragEnd(elm) {
                                    tagify.updateValueByDOMTags()
                                }
                            });
                        </script>
                    @endpush
                @elseif($option['options'] == 'pages')
                    @php
                        $pages = wncms()
                            ->page()
                            ->getList(['website_id' => $website->id])
                            ->map(function ($page) {
                                return ['value' => $page->id, 'name' => $page->title];
                            })
                            ->toArray();

                        $currentPages = wncms()
                            ->page()
                            ->getList(['ids' => explode(',', $currentValue), 'website_id' => $website->id])
                            ->pluck('title', 'id')
                            ->toArray();

                        $current_options[$option['name']] = implode(',', $currentPages);

                    @endphp

                    <input id="tagify_{{ $option_index }}"
                        class="form-control form-control-sm p-0"
                        name="{{ $inputName }}"
                        value="{{ $currentValue }}"
                        @if (!empty($option['required'])) required @endif
                        @if (!empty($option['disabled'])) disabled @endif />

                    <script type="text/javascript">
                        window.addEventListener('DOMContentLoaded', (event) => {
                            var input = document.querySelector("#tagify_{{ $option_index }}");
                            var pages = @json($pages);

                            // Initialize Tagify
                            tagify = new Tagify(input, {
                                whitelist: pages,
                                enforceWhitelist: {{ isset($option['whitelist_tag_only']) && $option['whitelist_tag_only'] == false ? 'false' : 'true' }},
                                // skipInvalid: true,
                                // duplicates: false,
                                tagTextProp: 'name',
                                maxTags: {{ $option['limit'] ?? 999 }},
                                dropdown: {
                                    maxItems: 100,
                                    mapValueTo: 'name',
                                    classname: "tagify__inline__suggestions",
                                    enabled: 0,
                                    closeOnSelect: false,
                                    searchKeys: ['name', 'value'],
                                },
                            });

                            // handle value changes
                            input.addEventListener('change', function onChange(e) {
                                console.log(e.target.value);
                            });

                            // using 3-party script "dragsort"
                            // https://github.com/yairEO/dragsort
                            var dragsort = new DragSort(tagify.DOM.scope, {
                                selector: '.' + tagify.settings.classNames.tag,
                                callbacks: {
                                    dragEnd: onDragEnd
                                }
                            })

                            function onDragEnd(elm) {
                                tagify.updateValueByDOMTags()
                            }
                        });
                    </script>
                @elseif($option['options'] == 'posts')
                    @php
                        $posts = wncms()
                            ->post()
                            ->getList([
                                'website_id' => $website->id,
                                'cache' => false,
                            ])
                            ->map(function ($post) {
                                return ['value' => $post->id, 'name' => $post->title];
                            })
                            ->toArray();

                        $currentPosts = wncms()
                            ->post()
                            ->getList([
                                'ids' => explode(',', $currentValue),
                                'website_id' => $website->id,
                                'cache' => false,
                            ])
                            ->pluck('title', 'id')
                            ->toArray();

                        $current_options[$option['name']] = implode(',', $currentPosts);
                    @endphp

                    <input id="tagify_{{ $option_index }}"
                        class="form-control form-control-sm p-0"
                        name="{{ $inputName }}"
                        value="{{ $currentValue }}"
                        @if (!empty($option['required'])) required @endif
                        @disabled(!empty($option['disabled']) || !empty($disabled)) />

                    <script type="text/javascript">
                        window.addEventListener('DOMContentLoaded', (event) => {
                            var input = document.querySelector("#tagify_{{ $option_index }}");
                            var posts = @json($posts);

                            // Initialize Tagify
                            tagify = new Tagify(input, {
                                whitelist: posts,
                                enforceWhitelist: {{ isset($option['whitelist_tag_only']) && $option['whitelist_tag_only'] == false ? 'false' : 'true' }},
                                // skipInvalid: true,
                                // duplicates: false,
                                tagTextProp: 'name',
                                maxTags: {{ $option['limit'] ?? 999 }},
                                dropdown: {
                                    maxItems: 100,
                                    mapValueTo: 'name',
                                    classname: "tagify__inline__suggestions",
                                    enabled: 0,
                                    closeOnSelect: false,
                                    searchKeys: ['name', 'value'],
                                },
                            });

                            // handle value changes
                            input.addEventListener('change', function onChange(e) {
                                console.log(e.target.value);
                            });

                            // using 3-party script "dragsort"
                            // https://github.com/yairEO/dragsort
                            var dragsort = new DragSort(tagify.DOM.scope, {
                                selector: '.' + tagify.settings.classNames.tag,
                                callbacks: {
                                    dragEnd: onDragEnd
                                }
                            })

                            function onDragEnd(elm) {
                                tagify.updateValueByDOMTags()
                            }
                        });
                    </script>
                @elseif($option['options'] == 'menus')
                    @php
                        // Load all menus (value = id, name = menu name)
                        $menus = wncms()
                            ->menu()
                            ->getList([], $website?->id)
                            ->map(function ($menu) {
                                return ['value' => $menu->id, 'name' => $menu->name];
                            })
                            ->toArray();

                        // Convert saved IDs → menu names
                        $currentMenus = wncms()
                            ->menu()
                            ->getList([
                                'names' => explode(',', $currentValue), // same as posts using explode
                                'website_id' => $website?->id,
                                'cache' => false,
                            ])
                            ->pluck('name', 'id')
                            ->toArray();

                        // Display comma-separated menu names
                        $current_options[$option['name']] = implode(',', $currentMenus);

                    @endphp

                    <input id="tagify_{{ $option_index }}"
                        class="form-control form-control-sm p-0"
                        name="{{ $inputName }}"
                        value="{{ $current_options[$option['name']] }}"
                        @if (!empty($option['required'])) required @endif
                        @disabled(!empty($option['disabled']) || !empty($disabled)) />

                    <script type="text/javascript">
                        window.addEventListener('DOMContentLoaded', (event) => {
                            var input = document.querySelector("#tagify_{{ $option_index }}");
                            var menus = @json($menus);

                            // Initialize Tagify (same as posts)
                            tagify = new Tagify(input, {
                                whitelist: menus,
                                enforceWhitelist: {{ isset($option['whitelist_tag_only']) && $option['whitelist_tag_only'] == false ? 'false' : 'true' }},
                                // skipInvalid: true,
                                // duplicates: false,
                                tagTextProp: 'name',
                                maxTags: {{ $option['limit'] ?? 999 }},
                                dropdown: {
                                    maxItems: 100,
                                    mapValueTo: 'name',
                                    classname: "tagify__inline__suggestions",
                                    enabled: 0,
                                    closeOnSelect: false,
                                    searchKeys: ['name', 'value'],
                                },
                            });

                            // handle value changes
                            input.addEventListener('change', function onChange(e) {
                                console.log(e.target.value);
                            });

                            // using 3-party script "dragsort"
                            var dragsort = new DragSort(tagify.DOM.scope, {
                                selector: '.' + tagify.settings.classNames.tag,
                                callbacks: {
                                    dragEnd: onDragEnd
                                }
                            });

                            function onDragEnd(elm) {
                                tagify.updateValueByDOMTags()
                            }
                        });
                    </script>
                @else
                    <input id="tagify_{{ $option_index }}"
                        class="form-control form-control-sm p-0"
                        name="{{ $inputName }}"
                        value="{{ $currentValue }}"
                        @if (!empty($option['required'])) required @endif
                        @disabled(!empty($option['disabled']) || !empty($disabled)) />

                    <script type="text/javascript">
                        window.addEventListener('DOMContentLoaded', (event) => {
                            var input = document.querySelector("#tagify_{{ $option_index }}");
                            var whitelist = @json($option['options']);

                            // Initialize Tagify
                            tagify = new Tagify(input, {
                                whitelist: whitelist,
                                enforceWhitelist: {{ isset($option['whitelist_tag_only']) && $option['whitelist_tag_only'] == false ? 'false' : 'true' }},
                                // skipInvalid: true,
                                // duplicates: false,
                                tagTextProp: 'name',
                                maxTags: {{ $option['limit'] ?? 999 }},
                                dropdown: {
                                    maxItems: 100,
                                    mapValueTo: 'name',
                                    classname: "tagify__inline__suggestions",
                                    enabled: 0,
                                    closeOnSelect: false,
                                    searchKeys: ['name', 'value'],
                                },
                            });

                            // handle value changes
                            input.addEventListener('change', function onChange(e) {
                                console.log(e.target.value);
                            });

                            // using 3-party script "dragsort"
                            // https://github.com/yairEO/dragsort
                            var dragsort = new DragSort(tagify.DOM.scope, {
                                selector: '.' + tagify.settings.classNames.tag,
                                callbacks: {
                                    dragEnd: onDragEnd
                                }
                            })

                            function onDragEnd(elm) {
                                tagify.updateValueByDOMTags()
                            }
                        });
                    </script>
                @endif
            @elseif($option['type'] == 'accordion')
                @php
                    // Auto-generate a stable accordion key/id if not provided
                    $accordionKey = $option['id'] ?? ($option['name'] ?? 'acc_' . substr(md5(json_encode($option)), 0, 6));

                    $accordionDomId = $accordionKey . '_' . $randomIdSuffix;
                @endphp

                @if (!empty($option['repeat']) && $option['repeat'] > 1)
                    <div class="mb-3">
                        <button type="button"
                            class="btn btn-sm btn-dark fw-bold expand-all-accordion-items"
                            data-target=".accordion_{{ $accordionDomId }}">
                            @lang('wncms::word.expand_all')
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-dark fw-bold collapse-all-accordion-items"
                            data-target=".accordion_{{ $accordionDomId }}">
                            @lang('wncms::word.collapse_all')
                        </button>
                    </div>
                @endif

                @php
                    // Load saved order JSON: {"order_3":1,"order_2":2,"order_1":3}
                    $savedOrder = $current_options[$option['name']] ?? null;

                    if (is_string($savedOrder)) {
                        $savedOrder = json_decode($savedOrder, true);
                    }

                    // Build sorted index order: [1 => 3, 2 => 2, 3 => 1]
                    $itemOrder = [];

                    if (!empty($savedOrder)) {
                        foreach ($savedOrder as $key => $pos) {
                            $index = (int) str_replace('order_', '', $key);
                            $itemOrder[(int) $pos] = $index;
                        }
                        ksort($itemOrder);
                    }

                    $repeat = $option['repeat'] ?? 1;

                    // Default order if not saved
                    if (empty($itemOrder)) {
                        $itemOrder = range(1, $repeat);
                    }
                @endphp

                <div class="accordion accordion_{{ $accordionDomId }} mb-1"
                    id="{{ $accordionDomId }}">

                    @foreach ($itemOrder as $i)
                        @php
                            $suffix = "_{$i}"; 
                            $labelSuffix = $repeat > 1 ? $i : '';
                        @endphp

                        <div class="accordion-item">

                            @if (!empty($option['sortable']))
                                <input class="item-order-input"
                                    type="hidden"
                                    name="{{ $inputNameKey }}[{{ $accordionKey }}][order_{{ $i }}]"
                                    value="{{ $loop->iteration }}">
                            @endif

                            <h2 class="accordion-header"
                                id="{{ $accordionDomId . $suffix }}_header">

                                <button class="accordion-button fs-4 text-gray-800 fw-bold p-3 bg-gray-300"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $accordionDomId . $suffix }}_body"
                                    aria-expanded="true"
                                    aria-controls="{{ $accordionDomId . $suffix }}_body">
                                    @if (!empty($option['sortable']))
                                        <i class="fa-solid fa-bars me-2 drag-handle"></i>
                                    @endif
                                    {{ $option['label'] . $labelSuffix }}
                                </button>

                            </h2>

                            <div id="{{ $accordionDomId . $suffix }}_body"
                                class="accordion-collapse collapse"
                                aria-labelledby="{{ $accordionDomId . $suffix }}_header"
                                data-bs-parent="#{{ $accordionDomId }}">

                                <div class="accordion-body p-3">
                                    @foreach ($option['content'] as $tabContent)
                                        @php
                                            $indexed = $tabContent;

                                            if (!empty($indexed['name'])) {
                                                $indexed['name'] = $indexed['name'] . $suffix;
                                            }

                                            if (!empty($indexed['sub_items'])) {
                                                // inline inside accordion: sub_item_name_1  OR sub_item_name_1_2 for repeat
                                                $indexed['sub_items'] = array_map(function ($sub) use ($suffix) {
                                                    if (!empty($sub['name'])) {
                                                        $sub['name'] = $sub['name'] . $suffix;
                                                    }
                                                    return $sub;
                                                }, $indexed['sub_items']);
                                            }

                                            $indexed['input_name_key'] = $inputNameKey;
                                        @endphp

                                        @include('wncms::backend.parts.inputs', [
                                            'option' => $indexed,
                                            'inputNameKey' => $inputNameKey,
                                        ])
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    @endforeach

                    @if (!empty($option['sortable']))
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var accEl = document.getElementById('{{ $accordionDomId }}');
                                if (!accEl) return;

                                new Sortable(accEl, {
                                    draggable: '.accordion-item',
                                    handle: '.drag-handle',
                                    animation: 150,

                                    onStart: function(evt) {
                                        let body = evt.item.querySelector('.accordion-collapse');
                                        if (body) $(body).collapse('show');
                                    },

                                    onEnd: function(evt) {
                                        updateHiddenInputs(evt.from.children);
                                    }
                                });

                                function updateHiddenInputs(items) {
                                    Array.prototype.forEach.call(items, function(item, index) {
                                        var input = item.querySelector('.item-order-input');
                                        if (input) {
                                            input.value = index + 1;
                                        }
                                    });
                                }
                            });
                        </script>
                    @endif

                </div>

            @endif

            @if (!empty($option['description']))
                <div class="text-muted p-2">{{ $option['description'] }}</div>
            @endif
        </div>
    </div>
@endif
