<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Page;
use Wncms\Models\User;
use Wncms\Models\Website;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Backend
     * ----------------------------------------------------------------------------------------------------
     */
    public function index(Request $request)
    {
        $q = Page::query();
        $q->orderBy('id', 'desc');
        
        $selectedWebsiteId = $request->website ?? session('selected_website_id');
        if($selectedWebsiteId){
            $q->whereHas('website', function ($subq) use ($selectedWebsiteId) {
                $subq->where('websites.id', $selectedWebsiteId);
            });
        }elseif(!$request->has('website')){
            $websiteId = wncms()->website()->get()?->id;
            $q->whereHas('website', function ($subq) use ($websiteId) {
                $subq->where('websites.id', $websiteId);
            });
        }

        $pages = $q->get();

        $websites = Website::all();

        return view('wncms::backend.pages.index', [
            'page_title' => __('wncms::word.page_management'),
            'pages' => $pages,
            'websites' => $websites,
            'orders' => Page::ORDERS,
            'statuses' => Page::STATUSES,
            'visibilities' => Page::VISIBILITIES,
        ]);
    }

    public function create(Page $page = null)
    {
        if (isAdmin()) {
            $users = User::all();
            $websites = Website::all();
        } else {
            $users = User::where('id', auth()->id())->get();
            $websites = auth()->user()->websites;
        }

        return view('wncms::backend.pages.create', [
            'page_title' => __('wncms::word.page_management'),
            'websites' => $websites,
            'users' => $users,
            'orders' => Page::ORDERS,
            'types' => Page::TYPES,
            'statuses' => Page::STATUSES,
            'visibilities' => Page::VISIBILITIES,
            'page' => $page ??= new Page,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        if (isAdmin()) {
            $user = User::find($request->user_id) ?? auth()->user();
            $website = Website::find($request->website_id);
        } else {
            $user = auth()->user();
            $website = auth()->user()->websites()->find($request->website_id);
        }

        if (!$user) return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.user_not_found')]);
        if (!$website) return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.website_not_found')]);

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

        $page = $user->pages()->create([
            'website_id' => $website->id,
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

        //thumbnail
        $page->handleThumbnailFromRequest($request, 'page_thumbnail');
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
        wncms()->cache()->flush(['pages']);
        return redirect()->route('pages.edit', $page->id);
    }

    public function restore(Request $request)
    {
        dd('restore page from default theme setting');
    }

    public function edit(Page $page)
    {
        // dd($page);
        if (isAdmin()) {
            $users = User::all();
            $websites = Website::all();
        } else {
            $users = User::where('id', auth()->id())->get();
            $websites = auth()->user()->websites;
        }

        $available_templates = collect(config("theme." . $page->website?->theme . ".templates"));

        return view('wncms::backend.pages.edit', [
            'page_title' => __('wncms::word.page_management'),
            'page' => $page,
            'websites' => $websites,
            'users' => $users,
            'statuses' => Page::STATUSES,
            'types' => Page::TYPES,
            'visibilities' => Page::VISIBILITIES,
            'available_templates' => $available_templates,
        ]);
    }

    public function update(Request $request, Page $page)
    {
        // dd($request->all());
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
            $user = User::find($request->user_id) ?? auth()->user();
            $website = Website::find($request->website_id);
        } else {
            $user = auth()->user();
            $website = auth()->user()->websites()->find($request->website_id);
        }

        if (!$user) return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.user_not_found')]);
        if (!$website) return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.website_not_found')]);

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
            'website_id' => $website->id,
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

        if($page->type == 'template' && !empty($page->blade_name) && !empty($request->inputs)){
            $page->spto($request);
        }

        // dd($request->model_attributes);
        // TODO: create a composer package for this and add event listener
        // $page->saveExtraAttribute($request->model_attributes);

        //thubmnail
        $page->handleThumbnailFromRequest($request, 'page_thumbnail');

        $page->savePageOption($request);

        //clear cache
        wncms()->cache()->flush(['pages']);

        return redirect()->route('pages.edit', $page->id)->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->route('pages.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        // dd($request->all());
        if(!is_array($request->model_ids)){
            $modelIds = explode(",", $request->model_ids);
        }else{
            $modelIds = $request->model_ids;
        }

        $count = Page::whereIn('id', $modelIds)->delete();

        if($request->ajax()){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('pages.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }

    public function get_available_templates(Request $request)
    {
        $website = Website::find($request->website_id);
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
        if(empty($request->website_id)){
            return back()->withErrors(['message' => __('wncms::word.website_is_required')]);
        }
        //get website
        if(isAdmin()){
            $website = Website::find($request->website_id);
        }else{
            $website = Website::query()
            ->whereRelation('users', 'users.id', auth()->id());
        }

        if(empty($website)){
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
        $posts = wncms()->post()->getList(
            count:10,
        );
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

        $randomNameKeySuffix = md5(microtime(true) . rand(10000,99999));
        $randomKey = 'inputs[item_' . $randomNameKeySuffix . ']';
        $html = '';


        $sortableIds = [];
        foreach($widgetOptions ?? [] as $widgetOption){
            $randomIdSuffix = md5(microtime(true) . rand(10000,99999));
            if(!empty($widgetOption['sortable']) && !empty($widgetOption['id'])){
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
