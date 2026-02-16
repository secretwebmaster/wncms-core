<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Page;
use Wncms\Models\User;
use Wncms\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class PageController extends BackendController
{
    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Backend
     * ----------------------------------------------------------------------------------------------------
     */
    public function index(Request $request)
    {
        $q = $this->modelClass::query();
        $this->applyBackendListWebsiteScope($q);

        if ($request->keyword) {
            $q->where(function ($subQ) use ($request) {
                $subQ->where('title', 'like', '%' . $request->keyword . '%')
                    ->orWhere('slug', 'like', '%' . $request->keyword . '%');
            });
        }

        $q->orderBy('id', 'desc');

        $pages = $q->paginate($request->page_size ?? 100);

        $homepageDomains = Website::query()
            ->whereNotNull('homepage')
            ->get(['homepage', 'domain'])
            ->groupBy('homepage')
            ->map(function ($items) {
                return $items->pluck('domain')->values()->all();
            })
            ->toArray();

        return $this->view('backend.pages.index', [
            'page_title' => wncms()->getModelWord('page', 'management'),
            'pages' => $pages,
            'sorts' => $this->modelClass::SORTS,
            'statuses' => $this->modelClass::STATUSES,
            'visibilities' => $this->modelClass::VISIBILITIES,
            'homepageDomains' => $homepageDomains,
        ]);
    }

    public function create($id = null)
    {
        // Load existing page if cloning or editing draft during create
        if ($id) {
            $page = $this->modelClass::find($id);
            if (!$page) {
                return back()->withMessage(__('wncms::word.model_not_found', [
                    'model_name' => __('wncms::word.' . $this->singular)
                ]));
            }

            $page->slug = $page->slug . '-' . time();
        } else {
            $page = new $this->modelClass;
        }

        // Load users
        if (isAdmin()) {
            $users = wncms()->getModelClass('user')::all();
        } else {
            $users = wncms()->getModelClass('user')::where('id', auth()->id())->get();
        }

        // Load available theme templates
        if (gss('multi_website')) {
            $theme = $page->website?->theme ?? wncms()->website()->get()?->theme;
        } else {
            $theme = wncms()->website()->get()?->theme;
        }

        $templateConfig = config("theme.{$theme}.templates", []);

        $available_templates = [];
        foreach ($templateConfig as $templateId => $templateDef) {
            $available_templates[] = [
                'id' => $templateId,
                'blade_name' => $templateId,
                'label' => $templateDef['label'] ?? $templateId,
            ];
        }

        // During create:
        $page_template_options = [];
        $page_template_values  = [];

        // cloning existing page with template
        if ($id && $page->type === 'template') {

            $blade = $page->blade_name;

            if ($blade && isset($templateConfig[$blade])) {

                // Load grouped options (sections)
                $page_template_options = $templateConfig[$blade]['sections'] ?? [];

                // Load saved values from page_templates table
                $row = $page->page_templates()
                    ->where('theme_id', $theme)
                    ->where('template_id', $blade)
                    ->first();

                // Clone values
                $page_template_values = $row?->value ?? [];
            }
        }

        // dd($page_template_options, $page_template_values);

        return $this->view('backend.pages.create', [
            'page_title' => wncms()->getModelWord('page', 'management'),
            'users'     => $users,
            'sorts'     => $this->modelClass::SORTS,
            'types'     => $this->modelClass::TYPES,
            'statuses'  => $this->modelClass::STATUSES,
            'visibilities' => $this->modelClass::VISIBILITIES,
            'page'      => $page,
            'available_templates' => $available_templates,
            'page_template_options' => $page_template_options,
            'page_template_values' => $page_template_values,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all(), $request->allFiles());
        $user = wncms()->getModelClass('user')::find($request->user_id) ?? auth()->user();
        if (!$user) return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.user_not_found')]);

        $request->validate(
            [
                'title' => 'required|max:255',
                'status' => 'required',
                'visibility' => 'required',
            ],
            [
                'title.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.title')]),
                'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
                'visibility.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.visibility')]),
            ]
        );

        $existingPage = $this->modelClass::where('slug', $request->slug)->first();
        if ($existingPage) {
            return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.slug_already_exists')]);
        }

        $page = $user->pages()->create([
            'status' => $request->input('status'),
            'visibility' => $request->input('visibility'),
            'title' => $request->input('title'),
            'slug' => $request->input('slug') ?: wncms()->getUniqueSlug('pages'),
            'remark' => $request->input('remark'),
            'content' => $request->input('content'),
            'type' => $request->input('type') ?: 'plain',
            'is_locked' => $request->boolean('is_locked'),
            'blade_name' => $request->input('blade_name'),
        ]);
        $this->syncBackendMutationWebsites($page);

        // page options
        if ($request->has('options')) {
            foreach ($request->options as $key => $value) {
                $page->setOption($key, $value);
            }
        }

        // thumbnail remove
        if (!empty($request->page_thumbnail_remove)) {
            $page->clearMediaCollection('page_thumbnail');
        }

        // thumbnail upload
        if ($request->hasFile('page_thumbnail')) {
            $page->addMediaFromRequest('page_thumbnail')->toMediaCollection('page_thumbnail');
        }

        // save template inputs (NEW)
        if (
            $page->type === 'template'
            && $page->blade_name
            && $request->has('template_inputs')
        ) {
            $page->saveTemplateInputs($request);
        }

        // clear cache
        $this->flush();

        return redirect()->route('pages.edit', [
            'id' => $page->id,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function restore(Request $request)
    {
        dd('restore page from default theme setting');
    }

    public function edit($id)
    {
        // Load users
        if (isAdmin()) {
            $users = wncms()->getModelClass('user')::all();
        } else {
            $users = wncms()->getModelClass('user')::where('id', auth()->id())->get();
        }

        // Load page
        if ($id) {
            $page = $this->modelClass::find($id);
            if (!$page) {
                return back()->withMessage(__('wncms::word.model_not_found', [
                    'model_name' => __('wncms::word.' . $this->singular)
                ]));
            }
        } else {
            $page = new $this->modelClass;
        }

        $available_templates = [];
        $page_template_options = [];
        $page_template_values = [];

        // Handle template config
        if ($page->type === 'template') {

            if (gss('multi_website')) {
                $theme = $page->website?->theme ?? wncms()->website()->get()?->theme;
            } else {
                $theme = wncms()->website()->get()?->theme;
            }

            $templateConfig = config("theme.{$theme}.templates", []);

            // Fill dropdown list
            foreach ($templateConfig as $templateId => $templateDef) {
                $available_templates[] = [
                    'id' => $templateId,
                    'blade_name' => $templateId,
                    'label' => $templateDef['label'] ?? $templateId,
                ];
            }

            // Get selected template's grouped options
            if ($page->blade_name && isset($templateConfig[$page->blade_name])) {

                $page_template_options = $templateConfig[$page->blade_name]['sections'] ?? [];

                // Load all saved template options from DB
                $rows = $page->getOptions(scope: 'template', group: $page->blade_name);

                $page_template_values = [];

                foreach ($rows as $row) {
                    $fullKey = $row->key;
                    $value   = $row->value;

                    // decode json array/object
                    if (is_string($value) && strlen($value) > 0 && ($value[0] === '[' || $value[0] === '{')) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $value = $decoded;
                        }
                    }

                    if (str_contains($fullKey, '.')) {
                        [$section, $field] = explode('.', $fullKey, 2);
                        $page_template_values[$section][$field] = $value;
                    } else {
                        $page_template_values[$fullKey] = $value;
                    }
                }
            }
        }

        // dd(
        //     $templateConfig,
        //     $page->blade_name,
        //     $page_template_options,
        //     $page_template_values,
        // );

        // dd($page->option('gallery_images'));

        return $this->view('backend.pages.edit', [
            'page_title' => wncms()->getModelWord('page', 'management'),
            'page'     => $page,
            'users'    => $users,
            'statuses' => $this->modelClass::STATUSES,
            'types'    => $this->modelClass::TYPES,
            'visibilities' => $this->modelClass::VISIBILITIES,
            'available_templates' => $available_templates,
            'page_template_options' => $page_template_options,
            'page_template_values' => $page_template_values ?? [],
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd(
        //     $request->all(),
        //     $request->allFiles()
        // );
        $page = $this->modelClass::find($id);
        if (!$page) {
            return redirect()->back()->withInput()->withErrors([
                'message' => __('wncms::word.model_not_found', [
                    'model_name' => __('wncms::word.' . $this->singular)
                ])
            ]);
        }

        // Locked page checks
        if ($page->is_locked && $request->is_locked) {
            return redirect()->back()->withInput()->withErrors([
                'message' => __('wncms::word.page_is_lock_please_unlock_and_save_first_to_edit')
            ]);
        }

        if ($page->is_locked && !$request->is_locked) {
            $page->update(['is_locked' => false]);
            return back()->withMessage(__('wncms::word.page_is_unlocked'));
        }

        // Load user
        if (isAdmin()) {
            $user = wncms()->getModelClass('user')::find($request->user_id) ?? auth()->user();
        } else {
            $user = auth()->user();
        }

        if (!$user) {
            return redirect()->back()->withInput()->withErrors([
                'message' => __('wncms::word.user_not_found')
            ]);
        }

        // Validation
        $request->validate([
            'status' => 'required',
            'visibility' => 'required',
        ], [
            'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
            'visibility.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.visibility')]),
        ]);

        // Update base fields
        $page->update([
            'status' => $request->input('status'),
            'visibility' => $request->input('visibility'),
            'title' => $request->input('title'),
            'slug' => $request->input('slug') ?? wncms()->getUniqueSlug('pages'),
            'remark' => $request->input('remark'),
            'content' => $request->input('content'),
            'type' => $request->input('type') ?? $page->type ?? 'plain',
            'is_locked' => $request->boolean('is_locked'),
            'blade_name' => $request->input('blade_name'),
        ]);
        $this->syncBackendMutationWebsites($page);

        // save template values
        if ($page->type === 'template' && $page->blade_name && $request->has('template_inputs')) {
            $scope = 'template';
            $group = $page->blade_name;

            $templateInputs = $request->input('template_inputs');
            $fileInputs     = $request->allFiles()['template_inputs'] ?? [];

            // merge keys from inputs + files
            foreach ($fileInputs as $sectionKey => $fileSection) {
                if (!isset($templateInputs[$sectionKey])) {
                    $templateInputs[$sectionKey] = [];
                }
                foreach ($fileSection as $fieldKey => $fileValue) {
                    if (!array_key_exists($fieldKey, $templateInputs[$sectionKey])) {
                        $templateInputs[$sectionKey][$fieldKey] = null; // placeholder
                    }
                }
            }

            // Load available theme templates
            if (gss('multi_website')) {
                $theme = $page->website?->theme ?? wncms()->website()->get()?->theme;
            } else {
                $theme = wncms()->website()->get()?->theme;
            }

            $templateConfig = config("theme.{$theme}.templates");
            $templateMap = $this->buildTemplateFieldMap($templateConfig[$page->blade_name]['sections'] ?? []);

            foreach ($templateInputs as $sectionKey => $fields) {
                foreach ($fields as $fieldKey => $value) {

                    // full key
                    $optionKey = $sectionKey . '.' . $fieldKey;

                    // find type
                    $cfg  = $templateMap[$optionKey] ?? null;
                    $type = $cfg['type'] ?? null;

                    $mediaCollectionName = $scope . '_' . $group . '_' . $optionKey;

                    $file = $fileInputs[$sectionKey][$fieldKey]['file'] ?? null;

                    // image
                    if ($type === 'image') {

                        $removeFlag = isset($value['remove']) ? (int)$value['remove'] : 0;
                        $existing   = $value['image'] ?? null;
                        $file       = $fileInputs[$sectionKey][$fieldKey]['file'] ?? null;

                        // remove
                        if ($removeFlag === 1) {
                            $page->clearMediaCollection($mediaCollectionName);
                            $page->deleteOption(scope: $scope, group: $group, key: $optionKey);
                            continue;
                        }

                        // new upload
                        if ($file instanceof UploadedFile) {
                            $page->clearMediaCollection($mediaCollectionName);
                            $media = $page->addMedia($file)->toMediaCollection($mediaCollectionName);
                            $url = parse_url($media->getUrl(), PHP_URL_PATH);
                            $page->setOption($optionKey, $url, $scope, $group);
                            continue;
                        }

                        // no remove, no upload → keep
                        $page->setOption($optionKey, $existing ?? '', $scope, $group);
                        continue;
                    }

                    // accordion (repeat = 1, or repeat = n without sortable)
                    if ($type === 'accordion' && is_array($value)) {

                        $rows = $value;
                        $final = [];

                        foreach ($rows as $rowIndex => $rowFields) {

                            if (!is_array($rowFields)) continue;

                            $rowOutput = [];

                            // uploaded files for this row
                            $filesForRow = $fileInputs[$sectionKey][$fieldKey][$rowIndex] ?? [];

                            foreach ($rowFields as $childKey => $childValue) {

                                // detect child config
                                // example: timeline.timeline.tab_gallery
                                $childFullKey = $optionKey . '.' . $childKey;
                                $childCfg = $templateMap[$childFullKey] ?? null;
                                $childType = $childCfg['type'] ?? null;

                                // gallery inside accordion
                                if ($childType === 'gallery' && is_array($childValue)) {

                                    $galleryImages = $childValue['image']  ?? [];
                                    $galleryTexts  = $childValue['text']   ?? [];
                                    $galleryUrls   = $childValue['url']    ?? [];
                                    $galleryRemove = $childValue['remove'] ?? [];

                                    $galleryFiles = $filesForRow[$childKey]['file'] ?? [];

                                    $rowsKeep = [];

                                    // 1) existing items
                                    foreach ($galleryImages as $i => $img) {

                                        if (!$img) continue;

                                        if (isset($galleryRemove[$i]) && (int)$galleryRemove[$i] === 1) {
                                            continue;
                                        }

                                        $rowsKeep[] = [
                                            'image' => $img,
                                            'text'  => !empty($childCfg['has_text']) ? ($galleryTexts[$i] ?? '') : '',
                                            'url'   => !empty($childCfg['has_url'])  ? ($galleryUrls[$i] ?? '') : '',
                                        ];
                                    }

                                    // 2) new uploads
                                    foreach ($galleryFiles as $i => $f) {

                                        if ($f instanceof UploadedFile) {

                                            $media = $page->addMedia($f)->toMediaCollection($scope . '_' . $group . '_' . $childKey);
                                            $url = parse_url($media->getUrl(), PHP_URL_PATH);

                                            $lastIndex = count($rowsKeep);

                                            $rowsKeep[] = [
                                                'image' => $url,
                                                'text'  => !empty($childCfg['has_text']) ? ($galleryTexts[$lastIndex] ?? '') : '',
                                                'url'   => !empty($childCfg['has_url'])  ? ($galleryUrls[$lastIndex] ?? '') : '',
                                            ];
                                        }
                                    }

                                    $rowOutput[$childKey] = $rowsKeep;
                                    continue;
                                }

                                // image inside accordion
                                if ($childType === 'image' && is_array($childValue)) {

                                    $removeFlag = (int)($childValue['remove'] ?? 0);
                                    $existing   = $childValue['image'] ?? '';
                                    $fileInner  = $filesForRow[$childKey]['file'] ?? null;

                                    if ($removeFlag === 1) {
                                        $rowOutput[$childKey] = '';
                                        continue;
                                    }

                                    if ($fileInner instanceof UploadedFile) {
                                        $media = $page->addMedia($fileInner)->toMediaCollection($scope . '_' . $group . '_' . $childKey);
                                        $url = parse_url($media->getUrl(), PHP_URL_PATH);
                                        $rowOutput[$childKey] = $url;
                                        continue;
                                    }

                                    $rowOutput[$childKey] = $existing ?: '';
                                    continue;
                                }

                                // other fields
                                $rowOutput[$childKey] = $childValue;
                            }

                            $final[] = $rowOutput;
                        }

                        // save final accordion rows
                        $page->setOption($optionKey, json_encode($final), $scope, $group);
                        continue;
                    }

                    // gallery
                    if ($type === 'gallery') {

                        // match WebsiteController structure
                        $hasText = !empty($cfg['has_text']);
                        $hasUrl  = !empty($cfg['has_url']);

                        // fields come grouped exactly like WebsiteController:
                        // image[] text[] url[] remove[]
                        $images = $value['image']  ?? [];
                        $texts  = $value['text']   ?? [];
                        $urls   = $value['url']    ?? [];
                        $remove = $value['remove'] ?? [];
                        $sorts  = $value['sort']   ?? [];

                        // uploaded files
                        $files = $fileInputs[$sectionKey][$fieldKey]['file'] ?? [];

                        $kept = [];

                        // 1) EXISTING IMAGES (ordered by sort[])
                        $order = [];

                        foreach ($images as $i => $img) {
                            if (!$img) continue;

                            $order[] = [
                                'index' => $i,
                                'sort'  => $sorts[$i] ?? $i,
                            ];
                        }

                        usort($order, fn($a, $b) => $a['sort'] <=> $b['sort']);

                        foreach ($order as $o) {
                            $i   = $o['index'];
                            $img = $images[$i];

                            if (!$img) continue;

                            // remove flag
                            if (isset($remove[$i]) && (int)$remove[$i] === 1) {
                                continue;
                            }

                            $kept[] = [
                                'image' => $img,
                                'text'  => $hasText ? ($texts[$i] ?? '') : '',
                                'url'   => $hasUrl  ? ($urls[$i]  ?? '') : '',
                            ];
                        }

                        // 2) DELETE SPATIE MEDIA THAT NO LONGER EXISTS
                        foreach ($page->getMedia($mediaCollectionName) as $media) {

                            $mediaUrl = parse_url($media->getUrl(), PHP_URL_PATH);

                            $stillExists = collect($kept)->contains(fn($x) => $x['image'] === $mediaUrl);

                            if (!$stillExists) {
                                $media->delete();
                            }
                        }

                        // 3) NEW UPLOADS
                        foreach ($files as $i => $file) {

                            if ($file instanceof UploadedFile) {

                                $media = $page->addMedia($file)->toMediaCollection($mediaCollectionName);
                                $url = parse_url($media->getUrl(), PHP_URL_PATH);

                                $lastIndex = count($kept);

                                $kept[] = [
                                    'image' => $url,
                                    'text'  => $hasText ? ($texts[$lastIndex] ?? '') : '',
                                    'url'   => $hasUrl  ? ($urls[$lastIndex]  ?? '') : '',
                                ];
                            }
                        }

                        // 4) SAVE FINAL RESULT
                        $normalized = collect($kept)
                            ->filter(fn($x) => !empty($x['image']))
                            ->values()
                            ->toArray();

                        if (isset($value['sort'])) {
                            unset($value['sort']);
                        }

                        // clean inner image value before final json_encode
                        foreach ($normalized as &$item) {

                            // check if this is a JSON string pretending to be an image
                            if (is_string($item['image']) && str_starts_with($item['image'], '[')) {
                                $decoded = json_decode($item['image'], true);

                                if (is_array($decoded) && isset($decoded[0]['image'])) {
                                    $item['image'] = $decoded[0]['image'];
                                } else {
                                    $item['image'] = '';
                                }
                            }
                        }

                        unset($item);

                        $page->setOption($optionKey, json_encode($normalized), $scope, $group);

                        continue;
                    }

                    if ($type === 'tagify' && is_string($value) && wncms()->isValidTagifyJson($value)) {
                        $ids = collect(json_decode($value, true))->pluck('value')->toArray();
                        $value = implode(',', $ids);
                        $page->setOption($optionKey, $value, $scope, $group);
                    }

                    if (is_array($value)) {

                        // remove "remove" fields globally
                        if (isset($value['remove'])) {
                            unset($value['remove']);
                        }

                        // remove remove flags inside arrays (e.g. gallery, accordion subfields)
                        foreach ($value as $k => $v) {
                            if (is_array($v) && isset($v['remove'])) {
                                unset($value[$k]['remove']);
                            }
                        }

                        $page->setOption(
                            key: $optionKey,
                            value: json_encode($value, JSON_UNESCAPED_UNICODE),
                            scope: $scope,
                            group: $group
                        );

                        continue;
                    }

                    // other types
                    $page->setOption($optionKey, $value, $scope, $group);
                }
            }
        }

        // Save page switches/options
        if ($request->has('options')) {
            foreach ($request->options as $key => $value) {
                $page->setOption($key, $value);
            }
        }

        // Delete thumbnail
        if (!empty($request->page_thumbnail_remove)) {
            $page->clearMediaCollection('page_thumbnail');
        }

        // Upload thumbnail
        if ($request->hasFile('page_thumbnail')) {
            $page->addMediaFromRequest('page_thumbnail')->toMediaCollection('page_thumbnail');
        }

        // Clear cache
        $this->flush();

        return back()->withMessage(__('wncms::word.successfully_updated'));
    }

    protected function buildTemplateFieldMap(array $templateSections): array
    {
        $map = [];

        foreach ($templateSections as $sectionKey => $section) {
            if (!is_array($section)) {
                continue;
            }

            $options = $section['options'] ?? null;
            if (!is_array($options)) {
                continue;
            }

            foreach ($options as $option) {

                if (!is_array($option)) {
                    continue;
                }

                if (empty($option['type'])) {
                    continue;
                }

                // skip non-input options (same as WebsiteController)
                if (in_array($option['type'], ['heading', 'sub_heading', 'display_image'])) {
                    continue;
                }

                // -----------------------------------------------------------------
                // inline (top-level inside this section)
                // -----------------------------------------------------------------
                if (
                    $option['type'] === 'inline'
                    && !empty($option['sub_items'])
                    && is_array($option['sub_items'])
                ) {
                    // non-repeat inline
                    if (empty($option['repeat'])) {
                        foreach ($option['sub_items'] as $sub) {
                            if (!is_array($sub) || empty($sub['name']) || empty($sub['type'])) {
                                continue;
                            }
                            $this->addFieldToTemplateMap($sectionKey, $sub, $map);
                        }
                        continue;
                    }

                    // repeat inline
                    if (!is_numeric($option['repeat']) || $option['repeat'] < 1) {
                        continue;
                    }

                    $repeat = (int) $option['repeat'];

                    foreach ($option['sub_items'] as $sub) {
                        if (!is_array($sub) || empty($sub['name']) || empty($sub['type'])) {
                            continue;
                        }

                        for ($i = 1; $i <= $repeat; $i++) {
                            $field = $sub;
                            $field['name'] = $sub['name'] . '_' . $i; // tab_image_1, tab_image_2...
                            $this->addFieldToTemplateMap($sectionKey, $field, $map);
                        }
                    }

                    continue;
                }

                // -----------------------------------------------------------------
                // accordion options (similar to your existing logic, but via helper)
                // -----------------------------------------------------------------
                if ($option['type'] === 'accordion') {

                    // parent accordion itself: section.accordion_name
                    if (!empty($option['name'])) {
                        $this->addFieldToTemplateMap($sectionKey, $option, $map);
                    }

                    if (empty($option['sub_items']) || !is_array($option['sub_items'])) {
                        continue;
                    }

                    foreach ($option['sub_items'] as $child) {

                        if (!is_array($child) || empty($child['name']) || empty($child['type'])) {
                            continue;
                        }

                        // we want keys like: section.accordion.child
                        if (!empty($option['name'])) {
                            $fullName = $option['name'] . '.' . $child['name'];
                        } else {
                            $fullName = $child['name'];
                        }

                        $field = $child;
                        $field['name'] = $fullName;

                        $this->addFieldToTemplateMap($sectionKey, $field, $map);
                    }

                    continue;
                }

                // -----------------------------------------------------------------
                // simple options (no inline / no accordion)
                // -----------------------------------------------------------------
                if (!empty($option['name']) && !empty($option['type'])) {
                    $this->addFieldToTemplateMap($sectionKey, $option, $map);
                    continue;
                }
            }
        }

        return $map;
    }

    protected function addFieldToTemplateMap(string $sectionKey, array $field, array &$map): void
    {
        $name = $field['name'] ?? null;
        $type = $field['type'] ?? null;

        if (!$name || !$type) {
            return;
        }

        // full key: section.field_name (e.g. "tabs.tab_image_1")
        $key = $sectionKey . '.' . $name;

        if (!isset($map[$key])) {
            $map[$key] = $field;
        }
    }

    public function get_available_templates(Request $request)
    {
        $website = wncms()->getModelClass('website')::find($request->website_id);
        if (!$website) return response()->json([
            'status' => 'fail',
            'message' => __('wncms::word.website_not_exist')
        ]);

        $available_templates = config("theme." . $website->theme . ".templates");
        $count = count($available_templates ?? []);

        return response()->json([
            'status' => 'success',
            'available_templates' => $available_templates,
            'message' => __('wncms::word.loaded_templtes', ['count' => $count]),
        ]);
    }

    public function create_theme_pages(Request $request)
    {
        // dd($request->all());
        if (empty($request->website_id)) {
            return back()->withErrors(['message' => __('wncms::word.website_is_required')]);
        }
        //get website
        if (isAdmin()) {
            $website = wncms()->getModelClass('website')::find($request->website_id);
        } else {
            $website = wncms()->getModelClass('website')::query()->whereRelation('users', 'users.id', auth()->id());
        }

        if (empty($website)) {
            return back()->withErrors(['message' => __('wncms::word.website_is_not_found')]);
        }

        $count = wncms()->page()->createDefaultThemeTemplatePages($website);

        //return back
        return back()->withMessage(__('wncms::word.successfully_created_count', ['count' => $count]));
    }

    /**
     * Beta
     */
    public function editor(Request $request, Page $page)
    {
        // $posts = wncms()->post()->getList(['count' => 10]);
        return $this->show_gjs_editor($request, $page);
    }

    public function widget(Request $request)
    {
        // info($request->all());
        $widgetId = $request->widgetId;
        $theme = $request->theme;
        $widgetOptions = config("theme.{$theme}.widgets.{$widgetId}.fields");
        $widgetOptions[] = ['type' => 'hidden', 'name' => 'widget_key'];
        $current_options = ['widget_key' => $widgetId];

        $randomNameKeySuffix = md5(microtime(true) . rand(10000, 99999));
        $randomKey = 'inputs[item_' . $randomNameKeySuffix . ']';
        $html = '';

        $sortableIds = [];
        foreach ($widgetOptions ?? [] as $widgetOption) {
            $randomIdSuffix = md5(microtime(true) . rand(10000, 99999));
            if (!empty($widgetOption['sortable']) && !empty($widgetOption['id'])) {
                $sortableIds[] = $widgetOption['id'] . $randomIdSuffix;
            }

            $widgetOption['input_name_key'] = $randomKey;
            $html .= view('wncms::backend.parts.inputs', [
                'option' => $widgetOption,
                'current_options' => $current_options,
                'randomIdSuffix' => $randomIdSuffix,
            ])->render();
        }

        info($sortableIds);

        // info($html);

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_created'),
            'html' => $html,
            'sortableIds' => $sortableIds,
        ]);
    }
}
