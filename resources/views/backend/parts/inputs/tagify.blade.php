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

    <input id="tagify_{{ $option['name'] . '_' . $optionIndex }}" class="form-control form-control-sm p-0"
        name="{{ $inputName }}"
        value="{{ $currentValue }}"
        @if (!empty($option['required'])) required @endif
        @disabled(!empty($option['disabled']) || !empty($disabled)) />

    @push('foot_js')
        <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', (event) => {
                var input = document.querySelector("#tagify_{{ $option['name'] . '_' . $optionIndex }}");
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

        $currentOptions[$option['name']] = implode(',', $currentPages);

    @endphp

    <input id="tagify_{{ $optionIndex }}"
        class="form-control form-control-sm p-0"
        name="{{ $inputName }}"
        value="{{ $currentValue }}"
        @if (!empty($option['required'])) required @endif
        @if (!empty($option['disabled'])) disabled @endif />

    <script type="text/javascript">
        window.addEventListener('DOMContentLoaded', (event) => {
            var input = document.querySelector("#tagify_{{ $optionIndex }}");
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

        $currentOptions[$option['name']] = implode(',', $currentPosts);
    @endphp

    <input id="tagify_{{ $optionIndex }}"
        class="form-control form-control-sm p-0"
        name="{{ $inputName }}"
        value="{{ $currentValue }}"
        @if (!empty($option['required'])) required @endif
        @disabled(!empty($option['disabled']) || !empty($disabled)) />

    <script type="text/javascript">
        window.addEventListener('DOMContentLoaded', (event) => {
            var input = document.querySelector("#tagify_{{ $optionIndex }}");
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
        $currentOptions[$option['name']] = implode(',', $currentMenus);

    @endphp

    <input id="tagify_{{ $optionIndex }}"
        class="form-control form-control-sm p-0"
        name="{{ $inputName }}"
        value="{{ $currentOptions[$option['name']] }}"
        @if (!empty($option['required'])) required @endif
        @disabled(!empty($option['disabled']) || !empty($disabled)) />

    <script type="text/javascript">
        window.addEventListener('DOMContentLoaded', (event) => {
            var input = document.querySelector("#tagify_{{ $optionIndex }}");
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
@elseif($option['options'] == 'model')
    @php
        $modelClass = $option['model_type'] ?? null;
        $modelItems = [];

        if (class_exists($modelClass)) {
            $modelItems = app($modelClass)
                ->newQuery()
                ->get()
                ->map(function ($item) use ($option) {
                    return [
                        'value' => $item->getKey(),
                        'name' => $item->{$option['model_display_field'] ?? 'name'},
                    ];
                })
                ->toArray();
        }
    @endphp

    <input id="tagify_{{ $optionIndex }}"
        class="form-control form-control-sm p-0"
        name="{{ $inputName }}"
        value="{{ $currentOptions[$option['name']] }}"
        @if (!empty($option['required'])) required @endif
        @disabled(!empty($option['disabled']) || !empty($disabled)) />

    <script type="text/javascript">
        window.addEventListener('DOMContentLoaded', (event) => {
            var input = document.querySelector("#tagify_{{ $optionIndex }}");
            var modelItems = @json($modelItems);
            // Initialize Tagify
            tagify = new Tagify(input, {
                whitelist: modelItems,
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
    <input id="tagify_{{ $optionIndex }}"
        class="form-control form-control-sm p-0"
        name="{{ $inputName }}"
        value="{{ $currentValue }}"
        @if (!empty($option['required'])) required @endif
        @disabled(!empty($option['disabled']) || !empty($disabled)) />

    <script type="text/javascript">
        window.addEventListener('DOMContentLoaded', (event) => {
            var input = document.querySelector("#tagify_{{ $optionIndex }}");
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
