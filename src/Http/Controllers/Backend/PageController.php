<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Page;
use Wncms\Models\User;
use Wncms\Models\Website;
use Illuminate\Http\Request;

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
                'id'         => $templateId,
                'blade_name' => $templateId,
                'label'      => $templateDef['label'] ?? $templateId,
            ];
        }

        // During create:
        $page_template_options = [];
        $page_template_values  = [];

        return $this->view('backend.pages.create', [
            'page_title'            => wncms_model_word('page', 'management'),
            'users'                 => $users,
            'sorts'                 => $this->modelClass::SORTS,
            'types'                 => $this->modelClass::TYPES,
            'statuses'              => $this->modelClass::STATUSES,
            'visibilities'          => $this->modelClass::VISIBILITIES,
            'page'                  => $page,

            // NEW
            'available_templates'   => $available_templates,
            'page_template_options' => $page_template_options,
            'page_template_values'  => $page_template_values,
        ]);
    }

    public function store(Request $request)
    {
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
            'slug' => $request->input('slug', wncms()->getUniqueSlug('pages')),
            'remark' => $request->input('remark'),
            'content' => $request->input('content'),
            'type' => $request->input('type', 'plain'),
            'is_locked' => $request->boolean('is_locked'),
            'blade_name' => $request->input('blade_name'),
        ]);


        // options
        if ($request->has('options')) {
            $page->setOptions($request->input('options'));
        }

        //thumbnail
        if (!empty($request->page_thumbnail_remove)) {
            $page->clearMediaCollection('page_thumbnail');
        }

        if (!empty($request->page_thumbnail)) {
            $page->addMediaFromRequest('page_thumbnail')->toMediaCollection('page_thumbnail');
        }

        $page->savePageOption($request);
        // $inputs = [];
        // // dd($request->inputs);
        // $pageWidgetOrder = 0;
        // if(!empty($request->inputs)){
        //     foreach($request->inputs as $pageWidgetId => $valueArr){
        //         foreach($valueArr as $key => $value){

        //             //set id
        //             $inputs[$pageWidgetId]['pageWidgetId'] = $pageWidgetId;

        //             //set order
        //             $inputs[$pageWidgetId]['pageWidgetOrder'] = $pageWidgetOrder;

        //             //set field values
        //             $inputs[$pageWidgetId]['fields'][$key] = $value;

        //             if(str()->endswith($key, '_remove')){

        //                 $file_key = str_replace("_remove" , '', $key);
        //                 if($value == 1){
        //                     $page->clearMediaCollection($file_key);
        //                     unset($inputs[$pageWidgetId][$file_key]);
        //                 }else{
        //                     $inputs[$pageWidgetId]['fields'][$file_key] = $page->getPageTemplateOption($pageWidgetId, $file_key);
        //                 }

        //                 //do nnt need to save key with _remove
        //                 unset($inputs[$pageWidgetId]['fields'][$key]);
        //             }

        //             if($request->hasFile("inputs.{$pageWidgetId}.{$key}")){
        //                 $collection = "{$pageWidgetId}_{$key}";
        //                 $page->clearMediaCollection($collection);
        //                 $image = $page->addMediaFromRequest("inputs.{$pageWidgetId}.{$key}")->toMediaCollection($collection);
        //                 $value = str_replace(env('APP_URL') , '' ,$image->getUrl());
        //                 $inputs[$pageWidgetId]['fields'][$key] = $value;
        //             }

        //         }

        //         $pageWidgetOrder++;
        //     }
        // }

        // $page->update([
        //     'options' => !empty($request->inputs) ? [wncms()->getLocale() => $inputs] : null,
        // ]);


        //clear cache
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
                    'id'         => $templateId,
                    'blade_name' => $templateId,
                    'label'      => $templateDef['label'] ?? $templateId,
                ];
            }

            // Get selected template's grouped options
            if ($page->blade_name && isset($templateConfig[$page->blade_name])) {

                // Now template is: templates.template1.sections.sectionA.options[]
                $page_template_options = $templateConfig[$page->blade_name]['sections'] ?? [];

                // Load saved values
                $row = $page->page_templates()
                    ->where('theme_id', $theme)
                    ->where('template_id', $page->blade_name)
                    ->first();

                $page_template_values = $row?->value ?? [];
            }
        }

        // dd(
        //     $templateConfig,
        //     $page->blade_name,
        //     $page_template_options,
        //     $page_template_values,
        // );

        return $this->view('backend.pages.edit', [
            'page_title'           => wncms_model_word('page', 'management'),
            'page'                 => $page,
            'users'                => $users,
            'statuses'             => $this->modelClass::STATUSES,
            'types'                => $this->modelClass::TYPES,
            'visibilities'         => $this->modelClass::VISIBILITIES,
            'available_templates'  => $available_templates,
            'page_template_options' => $page_template_options,
            'page_template_values' => $page_template_values,
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
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
            'status'     => 'required',
            'visibility' => 'required',
        ], [
            'status.required'     => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
            'visibility.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.visibility')]),
        ]);

        // Update base fields
        $page->update([
            'status'     => $request->input('status'),
            'visibility' => $request->input('visibility'),
            'title'      => $request->input('title'),
            'slug'       => $request->input('slug', wncms()->getUniqueSlug('pages')),
            'remark'     => $request->input('remark'),
            'content'    => $request->input('content'),
            'type'       => $request->input('type', 'plain'),
            'is_locked'  => $request->boolean('is_locked'),
            'blade_name' => $request->input('blade_name'),
        ]);

        // Save grouped template values
        if ($page->type === 'template' && $page->blade_name && $request->has('template_inputs')) {

            $page->saveTemplateInputs($request);
        }

        // Save page switches/options
        if ($request->has('options')) {
            $page->setOptions($request->input('options'));
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
