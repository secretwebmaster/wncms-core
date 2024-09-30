<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Menu;
use Wncms\Models\Website;
use Wncms\Models\MenuItem;
use Illuminate\Http\Request;
use Wncms\Models\Tag;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = Menu::query();

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

        $q->with('menu_items');
        $menus = $q->paginate($request->page_size ?? 20);

        $websites = wn('website')->getList();
        return view('wncms::backend.menus.index', [
            'menus' => $menus,
            'websites' => $websites,
            'page_title' => wncms_model_word('menu', 'management'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $websites = wn('website')->getList();
        return view('wncms::backend.menus.create', [
            'websites' => $websites,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $website = Website::find($request->website_id);
        if(!$website) return back()->withErrors(['message' => __('wncms::word.website_not_exist')]);
        $menu = $website->menus()->create([
            'name' => $request->name,
        ]);

        return redirect()->route('menus.edit', ['menu' => $menu]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Menu $menu)
    {
        // dd($menu);
        $websites = wn('website')->getList();
        $current_website = $request->website ? Website::find($request->$website) : Website::first();
        
        // $post_tags = Tag::getWithType('post_tag');
        // $post_categories = Tag::getWithType('post_category');
        // $video_tags = Tag::getWithType('video_tag');
        // $video_categories = Tag::getWithType('video_category');

        //get all models

        //get all model taxonomes
        $tag_type_arr = [];
        $tag_types = wncms_get_all_tag_types();
        foreach($tag_types as $tag_type){
            $tag_type_arr[$tag_type] = Tag::where('type',$tag_type)->whereNull('parent_id')->with('children')->get();
        }

        $menus = Menu::all();
        return view('wncms::backend.menus.edit', [
            'page_title' =>__('wncms::word.menu_management'),
            'websites' => $websites,
            'current_website' => $current_website,
            'menus' => $menus,
            'menu' => $menu,
            'tag_type_arr' => $tag_type_arr
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Menu $menu)
    {
        // dd(
        //     $request->all(),
        //     $request->new_menu,
        //     json_decode($request->new_menu,true)
        // );

        //更新菜單 (非菜單項目)
        $menu->update([
            'name' => $request->name
        ]);

        //刪除菜單項目
        $menu->menu_items()->whereIn('id', $request->removes ?? [])->delete();

        //更新菜單項目
        // dd(json_decode($request->new_menu, true));
        foreach(json_decode($request->new_menu, true) as $order => $menu_item){
            $this->add_items($menu, $menu_item, null, $order);
        }

        wncms()->cache()->flush(['menus']);

        return redirect()->route('menus.edit', [
            'menu' => $menu,
        ])->withMessage(__('wncms::word.successfully_updated'));

    }

    public function add_items($menu, $menu_item, $parent_id = null, $order = 0)
    {
        // dd($menu_item);
        $existing_item = $menu->menu_items()->find($menu_item['id']);
        if($existing_item){
            $existing_item->update([
                'parent_id' => $parent_id,
                'model_type' => $menu_item['modelType'] ?? $existing_item->modelType,
                'model_id' => $menu_item['modelId'] ?? $existing_item->modelId,
                'icon' => $menu_item['icon'] ?? $existing_item->icon,
                'type' => $menu_item['type'] ?? $existing_item->type,
                'name' => $menu_item['name'] ?? __('wncms::word.untitled'),
                'description' => $menu_item['description'] ?? null,
                'url' => $menu_item['url'] ?? $existing_item->url,
                'is_new_window' => $menu_item['newWindow'] === 1 ? true : false,
                'is_mega_menu' => $menu_item['is_mega_menu'] ?? false,
                'order' => $order,
            ]);
            $new_item = $existing_item;
        }else{
            $new_item = $menu->menu_items()->create([
                'parent_id' => $parent_id,
                'model_type' => $menu_item['modelType'] ?? null,
                'model_id' => $menu_item['modelId'] ?? null,
                'icon' => $menu_item['icon'] ?? null,
                'type' => $menu_item['type'] ?? null,
                'name' => $menu_item['name'] ?? __('wncms::word.untitled'),
                'description' => $menu_item['description'] ?? null,
                'url' => $menu_item['url'] ?? null,
                'is_new_window' => $menu_item['newWindow'] === 1 ? true : false,
                'is_mega_menu' => $menu_item['is_mega_menu'] ?? false,
                'order' => $order,
            ]);
        }

        if(!empty($menu_item['children'])){
            foreach($menu_item['children'] as $sub_menu_item){
                // info($sub_menu_item);
                $this->add_items($menu, $sub_menu_item, $new_item->id, $order);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('menus.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function get_menu_item(Request $request)
    {
        return MenuItem::find($request->menu_item_id)->append('thumbnail');
    }

    public function edit_menu_item(Request $request)
    {
        // info($request->all());

        $menu_item = MenuItem::find($request->menu_item_id);
        $menu_item->append('thumbnail');
        // info($menu_item);

        if(!$menu_item) return response()->json([
            'status' => 'fail',
            'message' => __('wncms::word.menu_item_is_not_found'),
            'hide_modal' => false
        ]);

        if(!empty($request->menu_item_thumbnail)){
            $menu_item->addMediaFromRequest('menu_item_thumbnail')->toMediaCollection('menu_item_thumbnail');
        }

        if(!empty($request->menu_item_thumbnail_remove)){
            $menu_item->ClearMediaCollection('menu_item_thumbnail');
        }

        $success = $menu_item->update([
            'url' => $request->menu_item_url,
            'description' => $request->menu_item_description,
            'icon' => wncms_get_fontawesome_class($request->menu_item_icon),
            'is_new_window' => !$request->menu_item_new_window ? false : true,
        ]);

        // info(wncms_get_fontawesome_class($request->menu_item_icon));

        $menu_item->setTranslations('name',$request->menu_item_name);
        $menu_item->save();
        // info($menu_item);

        if($success){
            wncms()->cache()->tags('menus')->flush();
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_updated'),
                'menu_item' => $menu_item,
                'menu' => $menu_item->menu->menu_items()->whereNull('parent_id')->with('children','children.children')->get(),
                'hide_modal' => true,
                'restoreBtn' => true,
            ]);
        }else{
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.something_went_wrong'),
                'hide_modal' => false,
            ]);
        }

    }

    // public function get_latest_menu(Request $request){
    //     $menu = Menu::find($request->menu_id);
    //     return $menu->menu_items()->whereNull('parent_id')->with('children','children.children')->get();
    // }
}
