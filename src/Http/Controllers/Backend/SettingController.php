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
        if ($request->has('developer_mode')) {
            uss('developer_mode', $request->developer_mode === '1' ? '1' : '0');
            return redirect()->route('settings.index');
        }

        $settings = Setting::pluck('value', 'key')->toArray();
        $availableSettings = array_merge(config('wncms-system-settings'), config('wncms.custom-settings') ?? []);

        // Determine active tab
        $firstTabName = collect($availableSettings)
            ->firstWhere(fn($t) => !empty($t['tab_name']) && !empty($t['tab_content']))['tab_name'] ?? null;

        $activeTab = $request->tab ?? old('active_tab') ?? $firstTabName;

        // API model processing
        $apiModels = [];
        $allActions = [];

        foreach (wncms()->getModels() as $modelClass) {

            if (!in_array(\Wncms\Interfaces\ApiModelInterface::class, class_implements($modelClass))) {
                continue;
            }

            $modelKey = $modelClass::$modelKey ?? strtolower(class_basename($modelClass));
            $routes = $modelClass::getApiRoutes();

            $apiModels[$modelKey] = [
                'class' => $modelClass,
                'routes' => $routes,
            ];

            foreach ($routes as $route) {
                $allActions[$route['action']] = $route['action'];
            }
        }

        // Desired forced positions
        $forceFirst = 'index';
        $forceLast  = 'delete';

        // Action ordering
        $commonActionsOriginal = ['index', 'show', 'store', 'update', 'delete'];

        $commonActions = [];

        if (in_array($forceFirst, $commonActionsOriginal)) {
            $commonActions[] = $forceFirst;
        }

        foreach ($commonActionsOriginal as $action) {
            if (!in_array($action, [$forceFirst, $forceLast])) {
                $commonActions[] = $action;
            }
        }

        if (in_array($forceLast, $commonActionsOriginal)) {
            $commonActions[] = $forceLast;
        }

        // Other actions (not in common list), maintain original order
        $otherActions = array_diff($allActions, $commonActionsOriginal);

        $modelDisplayList = wncms_get_model_names();

        return $this->view('wncms::backend.admin.settings.index', [
            'settings' => $settings,
            'page_title' => __('wncms::word.setting'),
            'availableSettings' => $availableSettings,
            'activeTab' => $activeTab,
            'apiModels' => $apiModels,
            'commonActions' => $commonActions,
            'otherActions' => $otherActions,
            'modelDisplayList' => $modelDisplayList,
        ]);
    }


    public function update(Request $request)
    {
        foreach ($request->settings as $key => $value) {
            uss($key, $value);
        }

        //特別處理項目
        if ($request->active_models) {
            uss('active_models', json_encode($request->active_models));
        } else {
            uss('active_models', "{}");
        }

        return redirect()->back();
    }

    public function smtp_test(Request $request)
    {
        info($request->all());
        if (empty($request->recipient)) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.recipient_is_not_set'),
            ]);
        }

        if (filter_var($request->recipient, FILTER_VALIDATE_EMAIL) === false) {
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
        if (!in_array($quickLinkData, $quickLinks)) {
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
