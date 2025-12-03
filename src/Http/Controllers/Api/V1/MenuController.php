<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\User;
use Http;
use Illuminate\Http\Request;
use Wncms\Http\Resources\MenuResource;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        // TODO: Check auth and website config
        // $menus = Post::limit(5)->get();
        $menus = collect([]);
        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => $menus,
        ])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public function store(Request $request)
    {
        info($request->all());
        //find user
    }

    public function sync(Request $request)
    {
        info($request->all());

        //check if user is admin
        $user = User::whereNotNull('api_token')->where('api_token',$request->api_token)->first();
        if(!$user->hasRole('admin')){
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.unauthorized_action'),
            ]);
        }
        
        //find website
        $website = $user->websites()
            ->where('websites.id', $request->website_id)
            ->orWhere('domain', $request->website_id)
            ->first();
        
        if(!$website){
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.website_is_not_found'),
            ]);
        }

        //find menu
        $menu = $website->menus()->where('name', $request->name)->firstOrCreate([
            'name' => $request->name,
        ]);

        //delete old item if found
        $menu->menu_items()->delete();

        //create new menuItem
        if(!empty($request->menu_items)){
            foreach($request->menu_items as $menuItemData){
                $menuItemNameLangArr = array_slice($menuItemData['name'], 0, 1, true);
                //get lang key and name
                foreach($menuItemNameLangArr as $langKey => $menuItemName);
                if($menuItemData['type'] == 'page'){

                    //create 
                    $page = $website->pages()->firstOrCreate(['name' => $menuItemName]);
                    $menuItem = $menu->menu_items()->create([
                        'model_type' => 'page',
                        'model_id' => $page->id,
                        'icon' => $menuItemName['icon'] ?? null,
                        'name' => $menuItemData['name'],
                    ]);
                }
  
                info("created menu item");
            }
        }

        wncms()->cache()->tags(['menus'])->flush();
        
        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_created_menu'),
        ]);

    }

    public function show(Request $request, $id)
    {
        $menu = wncms()->menu()->get(['id' => $id]);
        // dd($menu);
        return MenuResource::make($menu);
    }
}
