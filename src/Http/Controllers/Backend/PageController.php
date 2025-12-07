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

        $q->orderBy('id', 'desc');

        $pages = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.pages.index', [
            'page_title' => wncms_model_word('page', 'management'),
            'pages' => $pages,
            'sorts' => $this->modelClass::SORTS,
            'statuses' => $this->modelClass::STATUSES,
            'visibilities' => $this->modelClass::VISIBILITIES,
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
            'page_title' => wncms_model_word('page', 'management'),
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
            'slug' => $request->input('slug') ?? wncms()->getUniqueSlug('pages'),
            'remark' => $request->input('remark'),
            'content' => $request->input('content'),
            'type' => $request->input('type') ?: 'plain',
            'is_locked' => $request->boolean('is_locked'),
            'blade_name' => $request->input('blade_name'),
        ]);

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
            'page_title' => wncms_model_word('page', 'management'),
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
                foreach ($fields as $key => $value) {

                    // full key
                    $optionKey = $sectionKey . '.' . $key;

                    // find type
                    $cfg  = $templateMap[$optionKey] ?? null;
                    $type = $cfg['type'] ?? null;

                    $mediaCollectionName = $scope . '_' . $group . '_' . $key;

                    $file = $fileInputs[$sectionKey][$key]['file'] ?? null;

                    // image
                    if ($type === 'image') {

                        $removeFlag = isset($value['remove']) ? (int)$value['remove'] : 0;
                        $existing   = $value['image'] ?? null;
                        $file       = $fileInputs[$sectionKey][$key]['file'] ?? null;

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

                        $rows = $value; // example: [0 => [...fields...] ]
                        $final = [];

                        foreach ($rows as $rowIndex => $rowFields) {

                            if (!is_array($rowFields)) continue;

                            $rowOutput = [];

                            $filesForRow = $fileInputs[$sectionKey][$key][$rowIndex] ?? [];

                            foreach ($rowFields as $fieldKey => $fieldValue) {

                                // detect child config
                                $childFullKey = $optionKey . '.' . $fieldKey; // example.acc_single.acc_image
                                $childCfg = $templateMap[$childFullKey] ?? null;
                                $childType = $childCfg['type'] ?? null;

                                // child IMAGE inside accordion
                                if ($childType === 'image' && is_array($fieldValue)) {

                                    $removeFlag = (int)($fieldValue['remove'] ?? 0);
                                    $existing   = $fieldValue['image'] ?? '';
                                    $fileInner  = $filesForRow[$fieldKey]['file'] ?? null;

                                    // remove
                                    if ($removeFlag === 1) {
                                        $rowOutput[$fieldKey] = '';
                                        continue;
                                    }

                                    // new upload
                                    if ($fileInner instanceof UploadedFile) {
                                        $media = $page->addMedia($fileInner)->toMediaCollection($scope . '_' . $group . '_' . $fieldKey);
                                        $url = parse_url($media->getUrl(), PHP_URL_PATH);
                                        $rowOutput[$fieldKey] = $url;
                                        continue;
                                    }

                                    // keep existing
                                    $rowOutput[$fieldKey] = $existing ?: '';
                                    continue;
                                }

                                // normal field
                                $rowOutput[$fieldKey] = $fieldValue;
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

                        // uploaded files
                        $files = $fileInputs[$sectionKey][$key]['file'] ?? [];

                        $kept = [];

                        // 1) EXISTING IMAGES (same logic as WebsiteController)
                        foreach ($images as $i => $img) {

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

                        $website->setOption(
                            key: $key,
                            value: json_encode($data, JSON_UNESCAPED_UNICODE),
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

    protected function buildTemplateFieldMap(array $templateConfig): array
    {
        $map = [];

        foreach ($templateConfig as $sectionKey => $section) {
            foreach ($section['options'] as $opt) {

                // parent: only add if it really has a name
                if (!empty($opt['name'])) {
                    $map[$sectionKey . '.' . $opt['name']] = $opt;
                }

                // nested sub_items
                if (!empty($opt['sub_items']) && is_array($opt['sub_items'])) {

                    foreach ($opt['sub_items'] as $sub) {

                        // sub item MUST have name
                        if (empty($sub['name'])) {
                            continue; // skip safely
                        }

                        // parent may NOT have name
                        if (!empty($opt['name'])) {
                            // section.parent.child
                            $map[$sectionKey . '.' . $opt['name'] . '.' . $sub['name']] = $sub;
                        } else {
                            // section.child
                            $map[$sectionKey . '.' . $sub['name']] = $sub;
                        }
                    }
                }
            }
        }

        return $map;
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
