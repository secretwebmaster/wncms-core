<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function show_dashboard(Request $request)
    {
        if (auth()->user()->hasRole(['superadmin', 'admin'])) {

            //websites
            $website_count = wncms()->website()->getList()->count();

            // TODO: stat

            //page
            $page_count = wncms()->page()->getCount();

            //post
            $post_count = wncms()->post()->getCount();

            //user
            $user_count =  wncms()->user()->getCount();

            //update
            $result  = (new UpdateController)->getUpdateData();

            return $this->view('backend.dashboards.admin_dashboard', [
                'page_title' => __('wncms::word.user_role_dashboard', ['role' => __('wncms::word.admin')]),
                'result' => $result ?? [],
                'page_count' => $page_count,
                'post_count' => $post_count,
                'user_count' => $user_count,
                'website_count' => $website_count,

            ]);
        }

        if (auth()->user()->hasRole(['manager'])) {
            return $this->view('backend.dashboards.manager_dashboard', [
                'page_title' => __('wncms::word.user_role_dashboard', ['role' => __('wncms::word.manager')]),
                'result' => $result,
            ]);
        }

        if (auth()->user()->hasRole(['suspended'])) {
            return $this->view('backend.dashboards.suspended_dashboard', [
                'page_title' => __('wncms::word.user_role_dashboard', ['role' => __('wncms::word.suspended')]),
                'result' => $result,
            ]);
        }

        // custom user dashboard
        if (gss('use_custom_user_dashbaord')) {
            dd('return custom user dashboard');
        }

        // if user logged in using backend panel login, redirect to fronend default theme user dashboard
        return redirect()->route('frontend.users.dashboard');

        // TODO: user do not belongs to backend controller
        return $this->view('backend.dashboards.member_dashboard', [
            'page_title' => __('wncms::word.user_role_dashboard', ['role' => __('wncms::word.member')]),
        ]);
    }

    public function switch_website(Request $request)
    {
        // info($request->all());
        session([
            'selected_website_id' => $request->websiteId,
            'selected_domain' => $request->domain,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.switched_website_id_to', ['website_id' => $request->websiteId]),
            'reload' => true,
        ]);
    }
}
