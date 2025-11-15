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
        if ($id) {
            $page = $this->modelClass::find($id);
            if (!$page) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $page = new $this->modelClass;
        }

        if (isAdmin()) {
            $users = wncms()->getModelClass('user')::all();
        } else {
            $users = wncms()->getModelClass('user')::where('id', auth()->id())->get();
        }

        return $this->view('backend.pages.create', [
            'page_title' => wncms_model_word('page', 'management'),
            'users' => $users,
            'sorts' => $this->modelClass::SORTS,
            'types' => $this->modelClass::TYPES,
            'statuses' => $this->modelClass::STATUSES,
            'visibilities' => $this->modelClass::VISIBILITIES,
            'page' => $page,
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
            'status' => $request->status,
            'visibility' => $request->visibility,
            'title' => $request->title,
            'slug' => $request->slug ?? wncms_get_unique_slug('posts', 'slug', 6),
            'remark' => $request->remark,
            'content' => $request->content,
            'type' => $request->type ?? 'plain',
            'is_locked' => $request->is_locked == 1 ? true : false,
            'blade_name' => $request->blade_name,
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
            'id' => $page->id
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function restore(Request $request)
    {
        dd('restore page from default theme setting');
    }

    public function edit($id)
    {
        if (isAdmin()) {
            $users = wncms()->getModelClass('user')::all();
        } else {
            $users = wncms()->getModelClass('user')::where('id', auth()->id())->get();
        }

        if ($id) {
            $page = $this->modelClass::find($id);
            if (!$page) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $page = new $this->modelClass;
        }

        if(gss('multi_website')){
            $available_templates = collect(config("theme." . $page->website?->theme . ".templates"));
        }else{
            $available_templates = collect(config("theme." . wncms()->website()->get()?->theme . ".templates"));
        }

        return $this->view('backend.pages.edit', [
            'page_title' => wncms_model_word('page', 'management'),
            'page' => $page,
            'users' => $users,
            'statuses' => $this->modelClass::STATUSES,
            'types' => $this->modelClass::TYPES,
            'visibilities' => $this->modelClass::VISIBILITIES,
            'available_templates' => $available_templates,
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());

        $page = $this->modelClass::find($id);
        if (!$page) {
            return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)])]);
        }

        if ($page->is_locked && $request->is_locked) {
            return redirect()->back()->withInput()->withErrors([
                'message' => __('wncms::word.page_is_lock_please_unlock_and_save_first_to_edit')
            ]);
        }

        if ($page->is_locked && !$request->is_locked) {
            $page->update(['is_locked' => false]);
            return back()->withMessage(__('wncms::word.page_is_unlocked'));
        }

        if (isAdmin()) {
            $user = wncms()->getModelClass('user')::find($request->user_id) ?? auth()->user();
        } else {
            $user = auth()->user();
        }

        if (!$user) return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.user_not_found')]);

        $request->validate(
            [
                // 'title' => 'required|max:255',
                'status' => 'required',
                'visibility' => 'required',
            ],
            [
                // 'title.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.title')]),
                'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
                'visibility.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.visibility')]),
            ]
        );

        // TODO: Handle order
        // dd($request->inputs, $inputs);

        $page->update([
            'status' => $request->status,
            'visibility' => $request->visibility,
            'title' => $request->title,
            'slug' => $request->slug ?? wncms_get_unique_slug('posts', 'slug', 6),
            'remark' => $request->remark,
            'content' => $request->content,
            'type' => $request->type ?? 'plain',
            'is_locked' => $request->is_locked == 1 ? true : false,
            'blade_name' => $request->blade_name,
        ]);

        if ($page->type == 'template' && !empty($page->blade_name) && !empty($request->inputs)) {
            $page->savePageOption($request);
        }

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

        //clear cache
        $this->flush();

        return redirect()->route('pages.edit', [
            'id' => $page->id
        ])->withMessage(__('wncms::word.successfully_updated'));
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
