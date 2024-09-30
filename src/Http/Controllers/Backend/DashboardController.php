<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function show_dashboard(Request $request)
    {
        if(auth()->user()->hasRole(['superadmin','admin'])){

            //websites
            $website_count = wn('website')->getList()->count();
            
            // TODO: stat

            //page
            $page_count = wncms_get_pages()->count();
            
            //post
            $post_count = wncms_get_posts()->count();
            
            //user
            $user_count = wncms_get_users()?->count();

            //update
            $result  = (new UpdateController)->getUpdateData();
            $colors  = (new UpdateController)->colors;
            
            return view('backend.dashboards.admin_dashboard', [
                'page_title' => __('word.user_role_dashboard', ['role' => __('word.admin')]),
                'result' => $result ?? [],
                'colors' => $colors,
                'page_count' => $page_count,
                'post_count' => $post_count,
                'user_count' => $user_count,
                'website_count' => $website_count,
            ]);
        }

        if(auth()->user()->hasRole(['manager'])){
            return view('backend.dashboards.manager_dashboard', [
                'page_title' => __('word.user_role_dashboard', ['role' => __('word.manager')]),
                'result' => $result,
            ]);
        }

        if(auth()->user()->hasRole(['suspended'])){
            return view('backend.dashboards.suspended_dashboard', [
                'page_title' => __('word.user_role_dashboard', ['role' => __('word.suspended')]),
                'result' => $result,
            ]);
        }

        return view('backend.dashboards.member_dashboard', [
            'page_title' => __('word.user_role_dashboard', ['role' => __('word.member')]),
        ]);
    }

    public function switch_website(Request $request)
    {
        info($request->all());
        session([
            'selected_website_id' => $request->websiteId,
            'selected_domain' => $request->domain,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('word.switched_website_id_to', ['website_id' => $request->websiteId]),
            'reload' => true,
        ]);
    }
}
