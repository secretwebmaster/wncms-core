<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Mails\TestMail;
use Wncms\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        if($request->has('developer_mode')){
            if($request->developer_mode === '1'){
                uss('developer_mode', '1');
            }elseif($request->developer_mode === '0'){
                uss('developer_mode', '0');
            }
            return redirect()->route('settings.index');
        }

        //check if there is system update
        $settings = Setting::pluck('value','key')->toArray();

        $availableSettings = array_merge(config('wncms-system-settings'), (config('wncms.custom-settings') ?? []));
        return $this->view('wncms::backend.admin.settings',[
            'settings' => $settings,
            'page_title' => __('wncms::word.setting'),
            'availableSettings' => $availableSettings,
        ]);
    }

    public function update(Request $request)
    {
        foreach($request->settings as $key => $value){
            uss($key, $value);
        }

        //特別處理項目
        if($request->active_models){
            uss('active_models', json_encode($request->active_models));
        }else{
            uss('active_models', "{}");
        }

        return redirect()->back();
    }

    public function smtp_test(Request $request)
    {
        info($request->all());
        if(empty($request->recipient)){
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.recipient_is_not_set'),
            ]);
        }

        if(filter_var($request->recipient, FILTER_VALIDATE_EMAIL) === false){
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.please_enter_a_valid_email'),
            ]);
        }

        // dd(config('mail.mailers.smtp'));
        Mail::to($request->recipient)->send(new TestMail());

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.smtp_test_mail_is_send_please_check_your_mailbox')  . " " . $request->recipient,
        ]);
    }

    public function add_quick_link(Request $request)
    {
        // get current quick links
        $quickLinkStr = gss('quick_links');
        $quickLinks = json_decode($quickLinkStr, true) ?? [];

        // prepare new quick link data
        $quickLinkData = [
            'route' => $request->route,
            'name' => $request->name,
            'url' => $request->url,
        ];

        // append new quick link
        if(!in_array($quickLinkData, $quickLinks)){
            $quickLinks[] = $quickLinkData;
        }

        // save quick links
        uss('quick_links', json_encode($quickLinks));
        
        return back()->withMessage(__('wncms::word.successfully_updated'));
    }

    public function remove_quick_link(Request $request)
    {
        $quickLinkStr = gss('quick_links');
        $quickLinks = json_decode($quickLinkStr, true) ?? [];
    
        $quickLinkData = [
            'route' => $request->route,
            'url' => $request->url,
        ];
    
        $quickLinks = array_filter($quickLinks, function ($quickLink) use ($quickLinkData) {
            return !(
                ($quickLink['route'] ?? null) === $quickLinkData['route'] ||
                ($quickLink['url'] ?? null) === $quickLinkData['url']
            );
        });
    
        uss('quick_links', json_encode(array_values($quickLinks)));
    
        return back()->withMessage(__('wncms::word.successfully_updated'));
    }
}
