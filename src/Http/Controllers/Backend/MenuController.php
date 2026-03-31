<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Menu;
use Wncms\Models\Website;
use Wncms\Models\MenuItem;
use Illuminate\Http\Request;
use Wncms\Models\Tag;
use Illuminate\Support\Arr;

class MenuController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();
        $this->applyBackendListWebsiteScope($q);

        $q->with('menu_items');

        $q->orderBy('id', 'desc');

        $menus = $q->paginate($request->page_size ?? 20);

        $websites =  wncms()->website()->getList();
        return $this->view('backend.menus.index', [
            'menus' => $menus,
            'websites' => $websites,
            'page_title' => wncms()->getModelWord('menu', 'management'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id = null)
    {
        return $this->view('backend.menus.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());

        $menu = $this->modelClass::create([
            'name' => $request->name,
        ]);
        $this->syncBackendMutationWebsites($menu);

        return redirect()->route('menus.edit', [
            'id' => $menu
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $menu = $this->modelClass::find($id);

        if (!$menu) {
            return back()->withErrors(['message' => __('wncms::word.model_not_found', [
                'model_name' => __('wncms::word.' . $this->singular)
            ])]);
        }

        $tagTypeArr = [];

        // Loop all registered models
        foreach (wncms()->getModels() as $modelClass) {

            // Each model's tag meta definitions
            if (method_exists($modelClass, 'getTagMeta')) {
                foreach ($modelClass::getTagMeta() as $meta) {

                    $tagType = $meta['key'];

                    // Load top-level tags for this tagType (with children)
                    $tags = wncms()->getModelClass('tag')::withType($tagType)
                        ->whereNull('parent_id')
                        ->with('children')
                        ->get();

                    if ($tags->isEmpty()) {
                        continue;
                    }

                    // Merge tags into the meta structure
                    $tagTypeArr[$tagType] = array_merge($meta, [
                        'tags' => $tags,
                    ]);
                }
            }
        }

        $menuSources = wncms()->menu()->resolveMenuSources(request());

        // dd($tagTypeArr);

        $menus = $this->modelClass::all();

        return $this->view('backend.menus.edit', [
            'page_title' => wncms()->getModelWord('menu', 'management'),
            'menus' => $menus,
            'menu' => $menu,
            'tagTypeArr' => $tagTypeArr,
            'menuSources' => $menuSources,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $menu = $this->modelClass::find($id);
        if (!$menu) {
            return back()->withErrors(['message' => __('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)])]);
        }

        //更新菜單 (非菜單項目)
        $menu->update([
            'name' => $request->name
        ]);

        //刪除菜單項目
        // $menu->menu_items()->whereIn('id', $request->removes ?? [])->delete();
        $removingMenuItems = $menu->menu_items()->whereIn('id', $request->removes ?? [])->get();
        foreach ($removingMenuItems as $removingMenuItems) {
            $removingMenuItems->delete();
        }

        //更新菜單項目
        // dd(json_decode($request->new_menu, true));
        foreach (json_decode($request->new_menu, true) as $sort => $menu_item) {
            $this->add_items($menu, $menu_item, null, $sort);
        }

        $this->syncBackendMutationWebsites($menu);
        $this->flush();

        return redirect()->route('menus.edit', [
            'id' => $menu,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function add_items($menu, $menu_item, $parent_id = null, $sort = 0)
    {
        // dd($menu_item);
        $existing_item = $menu->menu_items()->find($menu_item['id']);
        $resolvedName = $this->resolveIncomingMenuItemName($menu_item);
        $resolvedModelType = Arr::get($menu_item, 'modelType', Arr::get($menu_item, 'model_type'));
        $resolvedModelId = Arr::get($menu_item, 'modelId', Arr::get($menu_item, 'model_id'));

        if ($existing_item) {
            $existing_item->update([
                'parent_id' => $parent_id,
                'model_type' => $resolvedModelType ?? $existing_item->model_type,
                'model_id' => $resolvedModelId ?? $existing_item->model_id,
                'icon' => $menu_item['icon'] ?? $existing_item->icon,
                'type' => $menu_item['type'] ?? $existing_item->type,
                'name' => $resolvedName,
                'description' => $menu_item['description'] ?? null,
                'url' => $menu_item['url'] ?? $existing_item->url,
                'is_new_window' => $menu_item['is_new_window'] === 1 ? true : false,
                'is_mega_menu' => $menu_item['is_mega_menu'] ?? false,
                'sort' => $sort,
            ]);
            $new_item = $existing_item;
        } else {
            $new_item = $menu->menu_items()->create([
                'parent_id' => $parent_id,
                'model_type' => $resolvedModelType ?? null,
                'model_id' => $resolvedModelId ?? null,
                'icon' => $menu_item['icon'] ?? null,
                'type' => $menu_item['type'] ?? null,
                'name' => $resolvedName,
                'description' => $menu_item['description'] ?? null,
                'url' => $menu_item['url'] ?? null,
                'is_new_window' => $menu_item['is_new_window'] === 1 ? true : false,
                'is_mega_menu' => $menu_item['is_mega_menu'] ?? false,
                'sort' => $sort,
            ]);
        }

        if ($resolvedName !== '') {
            $new_item->setTranslation('name', app()->getLocale(), $resolvedName);
        }

        if (!empty($menu_item['children'])) {
            foreach ($menu_item['children'] as $sub_menu_item) {
                // info($sub_menu_item);
                $this->add_items($menu, $sub_menu_item, $new_item->id, $sort);
            }
        }
    }

    public function search_source_items(Request $request)
    {
        $sourceKey = (string) $request->input('source_key', '');
        $keyword = (string) $request->input('keyword', '');

        $source = wncms()->menu()->getMenuSource($sourceKey, $request);
        if (!$source) {
            return response()->json([
                'status' => 'fail',
                'items' => [],
                'message' => __('wncms::word.model_not_found', ['model_name' => 'menu_source']),
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'items' => wncms()->menu()->searchMenuSourceItems($sourceKey, $keyword, $request),
        ]);
    }

    public function get_menu_item(Request $request)
    {
        $menuItem = wncms()->getModelClass('menu_item')::find($request->menu_item_id);

        if (!$menuItem) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.menu_item_is_not_found'),
            ], 404);
        }

        return $menuItem->append(['thumbnail', 'resolved_name']);
    }

    public function edit_menu_item(Request $request)
    {
        // dd($request->all());
        // info($request->all());

        $menu_item = wncms()->getModelClass('menu_item')::find($request->menu_item_id);
        $menu_item->append('thumbnail');
        // info($menu_item);

        if (!$menu_item) return response()->json([
            'status' => 'fail',
            'message' => __('wncms::word.menu_item_is_not_found'),
            'hide_modal' => false
        ]);

        if (!empty($request->menu_item_thumbnail)) {
            $menu_item->addMediaFromRequest('menu_item_thumbnail')->toMediaCollection('menu_item_thumbnail');
        }

        if (!empty($request->menu_item_thumbnail_remove)) {
            $menu_item->ClearMediaCollection('menu_item_thumbnail');
        }

        $success = $menu_item->update([
            'url' => $request->menu_item_url,
            'description' => $request->menu_item_description,
            'icon' => str_replace(['<i class="', '"></i>'], '', (string) $request->menu_item_icon),
            'is_new_window' => !$request->menu_item_new_window ? false : true,
        ]);

        // info(str_replace(['<i class="', '"></i>'], '', (string) $request->menu_item_icon));

        $menu_item->save();

        foreach ($request->menu_item_name as $locale => $name) {
            $menu_item->setTranslation('name', $locale, $name);
        }

        $menu_item->refresh();

        if ($success) {
            $this->flush(['menu']);

            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_updated'),
                'menu_item' => $menu_item,
                'menu' => $menu_item->menu->menu_items()->whereNull('parent_id')->with('children', 'children.children')->get()->append(['resolved_name']),
                'hide_modal' => true,
                'restoreBtn' => true,
            ]);
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.something_went_wrong'),
                'hide_modal' => false,
            ]);
        }
    }

    protected function resolveIncomingMenuItemName(array $menuItem): ?string
    {
        $type = (string) ($menuItem['type'] ?? '');
        $name = trim((string) Arr::get($menuItem, 'name', ''));
        $modelType = trim((string) Arr::get($menuItem, 'modelType', Arr::get($menuItem, 'model_type', '')));
        $modelId = trim((string) Arr::get($menuItem, 'modelId', Arr::get($menuItem, 'model_id', '')));

        if ($name !== '') {
            return $name;
        }

        if ($type !== 'external_link' && ($modelType !== '' || $modelId !== '')) {
            return null;
        }

        return __('wncms::word.untitled');
    }

    // public function get_latest_menu(Request $request){
    //     $menu = $this->modelClass::find($request->menu_id);
    //     return $menu->menu_items()->whereNull('parent_id')->with('children','children.children')->get();
    // }
}
