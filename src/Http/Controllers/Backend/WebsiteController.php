<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class WebsiteController extends BackendController
{
    public function index(Request $request)
    {
        if (isAdmin()) {
            $q = $this->modelClass::query();
        } else {
            $q = auth()->user()->websites();
        }

        if ($request->keyword) {
            $q->where(function ($subq) use ($request) {
                $subq->where('domain', 'like', "%$request->keyword%")
                    ->orWhere('site_name', 'like', "%$request->keyword%");
            });
        }

        $q->with(['domain_aliases', 'translations', 'media']);

        $q->orderBy('id', 'desc');

        $websites = $q->paginate($request->page_size ?? 50);

        return $this->view('backend.websites.index', [
            'websites' => $websites,
            'page_title' => __('wncms::word.website_management'),
            'hideToolbarWebsiteFiller' => true,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $website = $this->modelClass::find($id);
            if (!$website) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $website = new $this->modelClass;
        }

        $themes = wncms()->theme()->getThemes();
        $websiteCount = $this->modelClass::count();

        return $this->view('backend.websites.create', [
            'themes' => $themes,
            'page_title' => __('wncms::word.website_management'),
            'websiteCount' => $websiteCount,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'site_name' => 'required',
                'domain' => 'required',
            ],
            [
                'site_name.required' => __('wncms::word.site_name_is_required'),
                'domain.required' => __('wncms::word.domain_is_required'),
            ]
        );

        $existing_website = $this->modelClass::where('domain', $request->domain)->first();
        if ($existing_website) {
            return back()->withInput()->withErrors(['message' => __('wncms::word.website_is_already_exist')]);
        }

        $website = $this->modelClass::create([
            'site_name' => $request->site_name,
            'domain' => $request->domain,
            'theme' => $request->theme,
        ]);

        $website->users()->sync(auth()->id());

        if (!$website) return redirect()->route('websites.index');

        //handle images
        foreach (['site_logo', 'site_favicon'] as $key) {
            if ($request->hasFile($key)) {
                $website->clearMediaCollection($key);
                $image = $website->addMediaFromRequest($key)->toMediaCollection($key);
                $value = str_replace(env('APP_URL'), '', $image->getUrl());
                // $site_favicon AND $site_logo
                $$key = $value;
            }
        }

        //add theme options
        $default_theme_options = config('theme.' . $request->theme . '.default');

        foreach ($default_theme_options ?? [] as $key => $value) {
            $website->theme_options()->firstOrCreate(
                [
                    'theme' => $request->theme,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                ]
            );
        }

        $this->flush();

        return redirect()->route('websites.index')->with([
            'status' => 'success',
            'message' => __('wncms::word.successfully_created')
        ]);
    }

    public function edit($id)
    {
        $website = $this->modelClass::find($id);
        if (!$website) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $themes = wncms()->theme()->getThemes();

        // dd($themes);

        return $this->view('backend.websites.edit', [
            'page_title' => __('wncms::word.website_management'),
            'website' => $website,
            'themes' => $themes,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'theme' => 'nullable|string|max:100',
            'meta_verification' => 'nullable|string|max:255',
            'site_slogan' => 'nullable|string|max:255',
            'site_seo_description' => 'nullable|string|max:255',
            'site_seo_keywords' => 'nullable|string|max:255',
            'head_code' => 'nullable|string',
            'body_code' => 'nullable|string',
            'analytics' => 'nullable|string',
            'enabled_page_cache' => 'nullable|boolean',
            'enabled_data_cache' => 'nullable|boolean',
            'remark' => 'nullable|string|max:255',
        ], [
            'required' => __('wncms::word.field_required', ['field' => ':attribute']),
            'max' => __('wncms::word.field_max', ['field' => ':attribute', 'max' => ':max']),
        ], [
            'site_name' => __('wncms::word.site_name'),
            'theme' => __('wncms::word.theme'),
            'meta_verification' => __('wncms::word.meta_verification'),
            'site_slogan' => __('wncms::word.site_slogan'),
            'site_seo_description' => __('wncms::word.site_seo_description'),
            'site_seo_keywords' => __('wncms::word.site_seo_keywords'),
            'head_code' => __('wncms::word.head_code'),
            'body_code' => __('wncms::word.body_code'),
            'analytics' => __('wncms::word.analytics'),
            'remark' => __('wncms::word.remark'),
        ]);

        $website = $this->modelClass::find($id);
        if (!$website) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        //debug to list all posssible combinations of validation rules error message
        $rules = [
            'site_name' => 'required|string|max:255',
            'theme' => 'nullable|string|max:100',
            'meta_verification' => 'nullable|string|max:255',
            'site_slogan' => 'nullable|string|max:255',
            'site_seo_description' => 'nullable|string|max:255',
            'site_seo_keywords' => 'nullable|string|max:255',
            'head_code' => 'nullable|string',
            'body_code' => 'nullable|string',
            'analytics' => 'nullable|string',
            'enabled_page_cache' => 'nullable|boolean',
            'enabled_data_cache' => 'nullable|boolean',
            'remark' => 'nullable|string|max:255',
        ];

        $messages = [
            'required' => __('wncms::word.field_required', ['field' => ':attribute']),
            'max' => __('wncms::word.field_max', ['field' => ':attribute', 'max' => ':max']),
        ];

        $customMessages = [
            'site_name' => __('wncms::word.site_name'),
            'theme' => __('wncms::word.theme'),
            'meta_verification' => __('wncms::word.meta_verification'),
            'site_slogan' => __('wncms::word.site_slogan'),
            'site_seo_description' => __('wncms::word.site_seo_description'),
            'site_seo_keywords' => __('wncms::word.site_seo_keywords'),
            'head_code' => __('wncms::word.head_code'),
            'body_code' => __('wncms::word.body_code'),
            'analytics' => __('wncms::word.analytics'),
            'remark' => __('wncms::word.remark'),
        ];

        $allMessages = [];

        foreach ($rules as $field => $rule) {
            // Add required messages
            if (strpos($rule, 'required') !== false) {
                $allMessages["{$field}.required"] = str_replace(':attribute', __('wncms::word.' . $field), $messages['required']);
            }

            // Add max messages
            if (preg_match('/max:(\d+)/', $rule, $matches)) {
                $max = $matches[1];
                $allMessages["{$field}.max"] = str_replace([':attribute', ':max'], [__('wncms::word.' . $field), $max], $messages['max']);
            }
        }

        // dd($request->all());
        foreach (['site_logo', 'site_logo_white', 'site_favicon'] as $key) {
            if ($request->{$key . '_remove'}  == 1) {
                $website->clearMediaCollection($key);
            }

            if ($request->hasFile($key)) {
                $website->clearMediaCollection($key);
                $website->addMediaFromRequest($key)->toMediaCollection($key);
            }
        }

        //首次安裝主題
        if ($request->theme != $website->theme) {
            //check if first time
            if ($website->theme_options()->where('theme', $request->theme)->count() === 0) {
                //add theme options
                $default_theme_options = config('theme.' . $request->theme . '.default');
                foreach ($default_theme_options ?? [] as $key => $value) {
                    $website->theme_options()->firstOrCreate(
                        [
                            'theme' => $request->theme,
                            'key' => $key,
                        ],
                        [
                            'value' => $value,
                        ]
                    );
                }
            };
        }

        $website->update([
            'site_name' => $request->site_name,
            'theme' => $request->theme,
            'homepage' => $request->homepage,
            'meta_verification' => $request->meta_verification,
            'site_slogan' => $request->site_slogan,
            'site_seo_description' => $request->site_seo_description,
            'site_seo_keywords' => $request->site_seo_keywords,
            'head_code' => $request->head_code,
            'body_code' => $request->body_code,
            'analytics' => $request->analytics,
            'enabled_page_cache' => $request->enabled_page_cache ? true : false,
            'enabled_data_cache' => $request->enabled_data_cache ? true : false,
            'remark' => $request->remark,
            'domain' => $request->domain,
        ]);

        $domainAliasIds = [];
        $domainAliases = explode("\r\n", $request->domain_aliases ?? '');

        foreach ($domainAliases as $domain) {
            $existingDomainAlias = wncms()->getModelClass('domain_alias')::where('domain', $domain)->first();
            if ($existingDomainAlias) {
                $existingDomainAlias->update([
                    'website_id' => $website->id
                ]);
                $domainAliasIds[] = $existingDomainAlias->id;
            } else {
                $domainAlias = wncms()->getModelClass('domain_alias')::create([
                    'website_id' => $website->id,
                    'domain' => $domain
                ]);
                $domainAliasIds[] = $domainAlias->id;
            }
        }

        $website->domain_aliases()->whereNotIn('domain', $domainAliases)->delete();

        //清理緩存
        $this->flush();

        return redirect()->route('websites.edit', $website)->with([
            'status' => 'success',
            'message' => __('wncms::word.successfully_updated')
        ]);
    }

    public function editThemeOptions($id)
    {
        $website = $this->modelClass::find($id);
        if (!$website) {
            return back()->withMessage(__('wncms::word.model_not_found', [
                'model_name' => __('wncms::word.' . $this->singular)
            ]));
        }

        $theme = $website->theme ?? 'default';
        $optionTabs = config('theme.' . $theme . '.option_tabs');

        // flat map of all fields for quick lookup
        $map = $this->buildThemeOptionFieldMap($optionTabs);

        $currentOptions = [];

        // fetch theme options (scope=theme, group=themeId)
        $rows = $website->getOptions(scope: 'theme', group: $theme);

        foreach ($rows as $row) {

            $key = $row->key;
            $value = $row->value;

            // ------------------------------------------------------------------
            // 1) Detect accordion parent keys only (exact match)
            // ------------------------------------------------------------------
            // parent accordion keys must be explicitly defined in option_tabs
            $isAccordionParent = isset($map[$key]) && ($map[$key]['type'] ?? null) === 'accordion';

            if ($isAccordionParent && is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $currentOptions[$key] = $decoded;
                    continue;
                }
            }

            // ------------------------------------------------------------------
            // 2) Decode gallery JSON
            // ------------------------------------------------------------------
            $type = $map[$key]['type'] ?? null;

            if ($type === 'gallery' && is_string($value)) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {

                    $clean = [];
                    foreach ($decoded as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $clean[] = [
                            'image' => $item['image'] ?? '',
                            'text'  => $item['text'] ?? '',
                            'url'   => $item['url'] ?? '',
                        ];
                    }

                    $currentOptions[$key] = $clean;
                    continue;
                }
            }

            // ------------------------------------------------------------------
            // 3) Default — no special decoding
            // ------------------------------------------------------------------
            $currentOptions[$key] = $value;
        }

        // other websites with same theme
        $websites = wncms()->website()->getList(wheres: [
            ['theme', $theme],
            ['id', '<>', $website->id],
        ]);

        // dd($currentOptions);

        return $this->view('backend.websites.theme_options', [
            'page_title'     => __('wncms::word.theme_options') . ' #' . $website->id,
            '_website'       => $website,
            'websites'       => $websites,
            'option_tabs'    => $optionTabs,
            'currentOptions' => $currentOptions,
            'activeTab'      => request()->input('tab'),
        ]);
    }

    public function updateThemeOptions(Request $request, $id)
    {
        $website = $this->modelClass::find($id);
        if (!$website) {
            return back()->withMessage(__('wncms::word.model_not_found', [
                'model_name' => __('wncms::word.' . $this->singular)
            ]));
        }

        $themeId = $website->theme;
        $configs = config('theme.' . $themeId . ".option_tabs");
        $map = $this->buildThemeOptionFieldMap($configs);

        $inputs = $request->input('inputs', []);
        $allFiles = $request->file('inputs', []);

        $scope = 'theme';
        $group = $themeId;

        $allKeys = collect(array_keys($inputs))
            ->merge(array_keys($allFiles))
            ->unique()
            ->values()
            ->toArray();


        foreach ($allKeys as $key) {

            $data = $inputs[$key] ?? null;
            $filesForKey = $allFiles[$key] ?? null;

            $config = $map[$key] ?? null;
            $type = $config['type'] ?? null;

            $mediaCollectionName = $scope . '_' . $themeId . '_' . $group . '_' . $key;

            // accordion
            if ($type === 'accordion') {

                $final = [];

                foreach ($data as $rowIndex => $fields) {

                    if (!is_array($fields)) continue;
                    $rowOutput = [];

                    foreach ($fields as $fieldKey => $fieldValue) {

                        $lookupKey = $fieldKey . '_' . ($rowIndex + 1);
                        $fieldCfg = $map[$lookupKey] ?? null;
                        $fieldType = $fieldCfg['type'] ?? null;

                        $file = $filesForKey[$rowIndex][$fieldKey]['file'] ?? null;

                        // image in accordion
                        if ($fieldType === 'image') {

                            $removeFlag = $fieldValue['remove'] ?? 0;

                            if ($removeFlag == 1) {
                                $rowOutput[$fieldKey] = '';
                                continue;
                            }

                            // new upload
                            if ($file instanceof UploadedFile) {
                                $media = $website->addMedia($file)->toMediaCollection($mediaCollectionName);
                                $url = parse_url($media->getUrl(), PHP_URL_PATH);
                                $rowOutput[$fieldKey] = $url;
                                continue;
                            }

                            // existing
                            $rowOutput[$fieldKey] = $fieldValue['image'] ?? '';
                            continue;
                        }

                        // tagify in accordion
                        if ($fieldType === 'tagify' && is_string($fieldValue) && wncms()->isValidTagifyJson($fieldValue)) {
                            $ids = collect(json_decode($fieldValue, true))->pluck('value')->toArray();
                            $rowOutput[$fieldKey] = implode(',', $ids);
                            continue;
                        }

                        // normal field
                        $rowOutput[$fieldKey] = $fieldValue;
                    }

                    $final[] = $rowOutput;
                }

                $website->setOption(
                    key: $key,
                    value: json_encode($final, JSON_UNESCAPED_UNICODE),
                    scope: $scope,
                    group: $group
                );

                continue;
            }

            // image
            if ($type === 'image') {

                $image = $data['image'] ?? null;
                $remove = $data['remove'] ?? 0;
                $file = $filesForKey['file'] ?? null;

                if ($remove == 1) {
                    $website->clearMediaCollection($mediaCollectionName);
                    $website->deleteOption(scope: $scope, group: $group, key: $key);
                }

                if ($file instanceof UploadedFile) {
                    $website->clearMediaCollection($mediaCollectionName);
                    $media = $website->addMedia($file)->toMediaCollection($mediaCollectionName);
                    $url = parse_url($media->getUrl(), PHP_URL_PATH);

                    $website->setOption(
                        key: $key,
                        value: $url,
                        scope: $scope,
                        group: $group
                    );
                }

                continue;
            }

            // gallery
            if ($type === 'gallery') {

                $hasText = !empty($config['has_text']);
                $hasUrl = !empty($config['has_url']);

                $images = $data['image'] ?? [];
                $texts = $data['text'] ?? [];
                $urls = $data['url'] ?? [];
                $remove = $data['remove'] ?? [];

                $files = $filesForKey['file'] ?? [];

                // 1) EXISTING IMAGES
                $existing = [];
                foreach ($images as $i => $img) {
                    if (!$img) continue;
                    $existing[] = [
                        'image' => $img,
                        'text'  => $hasText ? ($texts[$i] ?? '') : '',
                        'url'   => $hasUrl ? ($urls[$i] ?? '') : '',
                    ];
                }

                // 2) REMOVE MARKED
                $kept = [];
                foreach ($existing as $i => $item) {
                    if (!isset($remove[$i]) || (int)$remove[$i] === 0) {
                        $kept[] = $item;
                    }
                }

                // 3) DELETE MEDIA THAT NO LONGER EXISTS
                foreach ($website->getMedia($mediaCollectionName) as $media) {
                    $url = parse_url($media->getUrl(), PHP_URL_PATH);
                    $stillExists = collect($kept)->contains(fn($x) => $x['image'] === $url);
                    if (!$stillExists) $media->delete();
                }

                // 4) NEW FILE UPLOADS
                foreach ($files as $file) {
                    if ($file instanceof UploadedFile) {
                        $media = $website->addMedia($file)->toMediaCollection($mediaCollectionName);
                        $url = parse_url($media->getUrl(), PHP_URL_PATH);
                        $lastIndex = count($kept);

                        $kept[] = [
                            'image' => $url,
                            'text'  => $hasText ? ($texts[$lastIndex] ?? '') : '',
                            'url'   => $hasUrl ? ($urls[$lastIndex] ?? '') : '',
                        ];
                    }
                }

                // 5) SAVE RESULT
                $normalized = collect($kept)
                    ->filter(fn($x) => !empty($x['image']))
                    ->values()
                    ->toArray();

                $website->setOption(
                    key: $key,
                    value: json_encode($normalized, JSON_UNESCAPED_UNICODE),
                    scope: $scope,
                    group: $group
                );

                continue;
            }

            // tagify
            if ($type === 'tagify' && is_string($data) && wncms()->isValidTagifyJson($data)) {
                $ids = collect(json_decode($data, true))->pluck('value')->toArray();
                $data = implode(',', $ids);

                $website->setOption(
                    key: $key,
                    value: $data,
                    scope: $scope,
                    group: $group
                );

                continue;
            }

            // array data
            if (is_array($data)) {

                // remove "remove" fields globally
                if (isset($data['remove'])) {
                    unset($data['remove']);
                }

                // remove remove flags inside arrays (e.g. gallery, accordion subfields)
                foreach ($data as $k => $v) {
                    if (is_array($v) && isset($v['remove'])) {
                        unset($data[$k]['remove']);
                    }
                }

                $website->setOption(
                    key: $key,
                    value: json_encode($data, JSON_UNESCAPED_UNICODE),
                    scope: $scope,
                    group: $group
                );
                continue;
            }

            // default: plain value
            $website->setOption(
                key: $key,
                value: $data,
                scope: $scope,
                group: $group
            );
        }

        wncms()->cache()->flush(['websites', 'pages']);

        return redirect()->route('websites.theme.options', [
            'id'  => $website,
            'tab' => $request->query('tab'),
        ]);
    }

    protected function buildThemeOptionFieldMap(array $optionTabs): array
    {
        $map = [];

        foreach ($optionTabs as $tabKey => $tabContent) {
            if (!is_array($tabContent)) continue;

            foreach ($tabContent as $option) {

                // invalid option format
                if (!is_array($option)) continue;

                // invalid option type
                if (empty($option['type'])) continue;

                // skip non-input options
                if (in_array($option['type'], ['heading', 'sub_heading', 'display_image'])) {
                    continue;
                }

                // inline (top-level)
                if ($option['type'] === 'inline' && !empty($option['sub_items']) && is_array($option['sub_items'])) {
                    // non-repeat inline
                    if (empty($option['repeat'])) {
                        foreach ($option['sub_items'] as $sub) {
                            if (!is_array($sub)) continue;
                            $this->addFieldToThemeOptionMap($sub, $map);
                        }
                        continue;
                    }

                    if (!is_numeric($option['repeat']) || $option['repeat'] < 1) {
                        continue;
                    }

                    // repeat inline
                    $repeat = (int) $option['repeat'];
                    foreach ($option['sub_items'] as $sub) {
                        if (!is_array($sub)) continue;
                        for ($i = 1; $i <= $repeat; $i++) {
                            $key = $sub['name'] . '_' . $i;
                            $field = $sub;
                            $field['name'] = $key;
                            $this->addFieldToThemeOptionMap($field, $map);
                        }
                    }
                    continue;
                }

                // accordion options
                if ($option['type'] === 'accordion') {

                    // 1) register the *parent* accordion field itself
                    //    so $map['accordion_single']['type'] === 'accordion'
                    if (!empty($option['name'])) {
                        $this->addFieldToThemeOptionMap($option, $map);
                    }

                    // 2) then register children (normal + inline children)
                    if (empty($option['sub_items']) || !is_array($option['sub_items'])) {
                        continue;
                    }

                    $repeat = $option['repeat'] ?? 1;
                    if (!is_numeric($repeat) || $repeat < 1) {
                        $repeat = 1;
                    }
                    $repeat = (int) $repeat;

                    foreach ($option['sub_items'] as $child) {

                        if (!is_array($child) || empty($child['type'])) {
                            continue;
                        }

                        // normal (non-inline) child fields inside accordion
                        if ($child['type'] !== 'inline') {

                            if (empty($child['name'])) continue;

                            for ($i = 1; $i <= $repeat; $i++) {
                                $field = $child;
                                $field['name'] = $child['name'] . '_' . $i;
                                $this->addFieldToThemeOptionMap($field, $map);
                            }

                            continue;
                        }

                        // inline groups inside accordion
                        if (!empty($child['sub_items']) && is_array($child['sub_items'])) {

                            $inlineRepeat = $child['repeat'] ?? 1;
                            if (!is_numeric($inlineRepeat) || $inlineRepeat < 1) {
                                $inlineRepeat = 1;
                            }
                            $inlineRepeat = (int) $inlineRepeat;

                            foreach ($child['sub_items'] as $sub) {

                                if (!is_array($sub) || empty($sub['name']) || empty($sub['type'])) {
                                    continue;
                                }

                                for ($accIndex = 1; $accIndex <= $repeat; $accIndex++) {

                                    // no repeat on inline → sub_name_{accIndex}
                                    if (!isset($child['repeat'])) {
                                        $field = $sub;
                                        $field['name'] = $sub['name'] . '_' . $accIndex;
                                        $this->addFieldToThemeOptionMap($field, $map);
                                        continue;
                                    }

                                    // inline has repeat → sub_name_{accIndex}_{inlineIndex}
                                    for ($inlineIndex = 1; $inlineIndex <= $inlineRepeat; $inlineIndex++) {
                                        $field = $sub;
                                        $field['name'] = $sub['name'] . '_' . $accIndex . '_' . $inlineIndex;
                                        $this->addFieldToThemeOptionMap($field, $map);
                                    }
                                }
                            }
                        }
                    }

                    continue;
                }

                // simple options
                if (!empty($option['name']) && !empty($option['type'])) {
                    $this->addFieldToThemeOptionMap($option, $map);
                    continue;
                }
            }
        }

        return $map;
    }

    protected function addFieldToThemeOptionMap(array $field, array &$map): void
    {
        $name = $field['name'] ?? null;
        $type = $field['type'] ?? null;

        if (!$name || !$type) {
            return;
        }

        // Base key (e.g. "footer_text_1", "accordion_sortable", "inline_group")
        if (!isset($map[$name])) {
            $map[$name] = $field;
        }

        // Inline: generate inline_text_1, inline_text_2, ...
        if ($type === 'inline' && !empty($field['sub_items']) && is_array($field['sub_items'])) {
            $repeat = $field['repeat'] ?? 1;

            foreach ($field['sub_items'] as $sub) {
                if (empty($sub['name']) || empty($sub['type'])) continue;

                for ($i = 1; $i <= $repeat; $i++) {
                    $key = $sub['name'] . '_' . $i;

                    if (!isset($map[$key])) {
                        $map[$key] = $sub;
                    }
                }
            }
        }

        // Accordion: generate nest_text_1, nest_text_2, ... etc
        if ($type === 'accordion' && !empty($field['content']) && is_array($field['content'])) {
            $repeat = $field['repeat'] ?? 1;

            foreach ($field['content'] as $child) {
                if (empty($child['name']) || empty($child['type'])) continue;

                $childType = $child['type'];

                // Accordion child is inline
                if ($childType === 'inline' && !empty($child['sub_items']) && is_array($child['sub_items'])) {
                    $inlineRepeat = $child['repeat'] ?? 1;

                    foreach ($child['sub_items'] as $sub) {
                        if (empty($sub['name']) || empty($sub['type'])) continue;

                        for ($i = 1; $i <= $repeat; $i++) {
                            for ($r = 1; $r <= $inlineRepeat; $r++) {
                                $key = $sub['name'] . '_' . $i . '_' . $r;

                                if (!isset($map[$key])) {
                                    $map[$key] = $sub;
                                }
                            }
                        }
                    }

                    continue;
                }

                // Normal child field in accordion: name_1, name_2, ...
                for ($i = 1; $i <= $repeat; $i++) {
                    $key = $child['name'] . '_' . $i;

                    if (!isset($map[$key])) {
                        $map[$key] = $child;
                    }
                }
            }
        }
    }

    public function cloneThemeOptions(Request $request, $id)
    {
        $website = $this->modelClass::find($id);
        if (!$website) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $fromWebsite = wncms()->website()->get($request->from_website_id);
        $fromSettings = $fromWebsite->get_options();
        $shouldClearTagCache = false;

        foreach ($fromSettings as $key => $value) {

            if (strpos($key, 'categories') !== false) {
                $shouldClearTagCache = true;
            }

            //clear value
            $website->theme_options()->updateOrCreate(
                [
                    'theme' => $website->theme,
                    'key' => $key,
                ],
                [
                    'value' => $value
                ]
            );
        }

        if ($shouldClearTagCache) {
            wncms()->cache()->tags(['tags'])->flush();
        }

        wncms()->cache()->flush(['page', 'websites']);

        return back()->withMessage(__('wncms::word.successfully_cloned'));
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * 匯入主題預設參數
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.1.7
     * @version 3.1.7
     * 
     * ----------------------------------------------------------------------------------------------------
     */
    public function importDefaultOption(Request $request)
    {
        // dd($request->all());
        if ($request->confirmation != 'default') {
            return back()->withErrors(['message' => __('wncms::word.enter_default_to_confirm')]);
        }

        //get website
        $website = wncms()->website()->get($request->website_id, false);
        $count = 0;
        if ($website) {

            //get all default option
            $defaultThemeOptions = config("theme.{$website->theme}.default");


            //foreach 
            foreach ($defaultThemeOptions ?? [] as $key => $value) {
                //first or create
                $website->theme_options()->updateOrCreate(
                    [
                        'key' => $key,
                        'theme' => $website->theme,
                        'website_id' => $website->id,
                    ],
                    [
                        'value' => $value,
                    ],
                );

                //count
                $count++;
            }

            // dd($count, $website, $website->theme, $defaultThemeOptions);

            wncms()->cache()->flush(['websites', 'pages']);

            //return message
            return back()->withMessage(__('wncms::word.successfully_updated_count', ['count' => $count]));
        } else {
            return back()->withErrors(['message' => __('wncms::word.websites_is_not_found')]);
        }
    }

    // public function updateThemeOptions(Request $request, $id)
    // {
    //     // dd($request->all());
    //     $website = $this->modelClass::find($id);
    //     if (!$website) {
    //         return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
    //     }

    //     $configs = config('theme.' . $website->theme . ".option_tabs");

    //     // Build precise map: actual_key => field_config
    //     $map = $this->buildThemeOptionFieldMap($configs);

    //     $inputs = $request->input('inputs', []);

    //     foreach ($inputs as $key => $group) {

    //         $config = $map[$key] ?? null;

    //         $type = $config['type'] ?? null; 

    //         // sortable accordion ordering (e.g. accordion_sortable_sortable_order)
    //         if (str_ends_with($key, '_sortable_order')) {

    //             // Always save the ordering JSON as-is.
    //             // No child fields are touched.
    //             $website->theme_options()->updateOrCreate(
    //                 ['theme' => $website->theme, 'key' => $key],
    //                 ['value' => json_encode($group, JSON_UNESCAPED_UNICODE)]
    //             );

    //             continue;
    //         }

    //         // image
    //         if ($type === 'image') {

    //             $image = $group['image']  ?? null;
    //             $remove = $group['remove'] ?? 0;
    //             $file = $request->file("inputs.{$key}.file");

    //             // REMOVE
    //             if ($remove == 1) {
    //                 $website->clearMediaCollection($key);

    //                 $website->theme_options()->updateOrCreate(
    //                     ['theme' => $website->theme, 'key' => $key],
    //                     ['value' => null]
    //                 );
    //                 continue;
    //             }

    //             // UPLOAD
    //             if ($file instanceof UploadedFile) {
    //                 $website->clearMediaCollection($key);
    //                 $media = $website->addMedia($file)->toMediaCollection($key);
    //                 $url = parse_url($media->getUrl(), PHP_URL_PATH);

    //                 $website->theme_options()->updateOrCreate(
    //                     ['theme' => $website->theme, 'key' => $key],
    //                     ['value' => $url]
    //                 );
    //                 continue;
    //             }

    //             // KEEP OLD
    //             $website->theme_options()->updateOrCreate(
    //                 ['theme' => $website->theme, 'key' => $key],
    //                 ['value' => $image]
    //             );

    //             continue;
    //         }

    //         // gallery
    //         if ($type === 'gallery') {

    //             // load flags
    //             $hasText = !empty($config['has_text']);
    //             $hasUrl = !empty($config['has_url']);

    //             // load existing non-file values
    //             $images = $group['image'] ?? [];
    //             $texts = $group['text']  ?? [];
    //             $urls = $group['url']   ?? [];
    //             $remove = $group['remove'] ?? [];

    //             // build existing items
    //             $existing = [];
    //             foreach ($images as $i => $img) {
    //                 if (!$img) continue;

    //                 $existing[] = [
    //                     'image' => $img,
    //                     'text' => $hasText ? ($texts[$i] ?? '') : '',
    //                     'url' => $hasUrl  ? ($urls[$i]  ?? '') : '',
    //                 ];
    //             }

    //             // apply remove flags
    //             $kept = [];
    //             foreach ($existing as $i => $item) {
    //                 if (!isset($remove[$i]) || (int)$remove[$i] === 0) {
    //                     $kept[] = $item;
    //                 }
    //             }

    //             // delete removed spatie media
    //             foreach ($website->getMedia($key) as $media) {
    //                 $url = parse_url($media->getUrl(), PHP_URL_PATH);

    //                 $exists = collect($kept)->contains(function ($x) use ($url) {
    //                     return $x['image'] === $url;
    //                 });

    //                 if (!$exists) {
    //                     $media->delete();
    //                 }
    //             }

    //             // load uploaded files from request
    //             $files = $request->file("inputs.{$key}.file") ?? [];
    //             if (!is_array($files)) {
    //                 $files = $files ? [$files] : [];
    //             }

    //             // append uploaded images
    //             foreach ($files as $file) {
    //                 if ($file instanceof UploadedFile) {
    //                     $media = $website->addMedia($file)->toMediaCollection($key);
    //                     $url = parse_url($media->getUrl(), PHP_URL_PATH);

    //                     $kept[] = [
    //                         'image' => $url,
    //                         'text' => $hasText ? '' : '',
    //                         'url' => $hasUrl  ? '' : '',
    //                     ];
    //                 }
    //             }

    //             // normalize structure
    //             $normalized = collect($kept)
    //                 ->filter(fn($x) => !empty($x['image']))
    //                 ->values()
    //                 ->toArray();

    //             // save option
    //             $website->theme_options()->updateOrCreate(
    //                 [
    //                     'theme' => $website->theme,
    //                     'key' => $key,
    //                 ],
    //                 [
    //                     'value' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
    //                 ]
    //             );

    //             continue;
    //         }

    //         // tagify
    //         if ($type === 'tagify' && is_string($group) && wncms()->isValidTagifyJson($group)) {
    //             $ids = collect(json_decode($group, true))->pluck('value')->toArray();
    //             $group = implode(',', $ids);
    //         }

    //         // normal array field
    //         if (is_array($group)) {
    //             $group = json_encode($group, JSON_UNESCAPED_UNICODE);
    //         }

    //         // save option
    //         $website->theme_options()->updateOrCreate(
    //             [
    //                 'theme' => $website->theme,
    //                 'key' => $key,
    //             ],
    //             [
    //                 'value' => $group,
    //             ]
    //         );
    //     }

    //     wncms()->cache()->flush(['websites', 'pages']);

    //     $tab = $request->query('tab');

    //     return redirect()->route('websites.theme.options', [
    //         'id' => $website,
    //         'tab' => $tab
    //     ]);
    // }

    // public function editThemeOptions($id)
    // {
    //     $website = $this->modelClass::find($id);
    //     if (!$website) {
    //         return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
    //     }

    //     // load option config
    //     $option_tabs = config('theme.' . ($website->theme ?? 'default') . '.option_tabs');

    //     // build accurate field map (actual_key => field_config)
    //     $map = $this->buildThemeOptionFieldMap($option_tabs);

    //     // load stored DB values
    //     $currentOptions = $website->theme_options()
    //         ->where('theme', $website->theme)
    //         ->get()
    //         ->mapWithKeys(function ($option) use ($map) {

    //             $value = $option->getTranslation('value', app()->getLocale());
    //             $key = $option->key;

    //             // find config by exact key
    //             $config = $map[$key] ?? null;
    //             $type = $config['type'] ?? null;

    //             // decode gallery
    //             if ($type === 'gallery' && is_string($value)) {

    //                 $decoded = json_decode($value, true);

    //                 if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {

    //                     $clean = [];

    //                     foreach ($decoded as $item) {
    //                         if (!is_array($item)) continue;

    //                         $clean[] = [
    //                             'image' => $item['image'] ?? '',
    //                             'text' => $item['text']  ?? '',
    //                             'url' => $item['url']   ?? '',
    //                         ];
    //                     }

    //                     $value = $clean;
    //                 }
    //             }

    //             return [$key => $value];
    //         })
    //         ->toArray();

    //     // load other websites with same theme
    //     $websites = wncms()->website()->getList(wheres: [
    //         ['theme', $website->theme],
    //         ['id', "<>", $website->id],
    //     ]);

    //     return $this->view('backend.websites.theme_options', [
    //         'page_title' => __('wncms::word.theme_options') . " #" . $website->id,
    //         '_website' => $website,
    //         'websites' => $websites,
    //         'option_tabs' => $option_tabs,
    //         'currentOptions' => $currentOptions,
    //         'activeTab' => request()->input('tab'),
    //     ]);
    // }

}
