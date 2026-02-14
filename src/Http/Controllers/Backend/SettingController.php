<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Mails\TestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('developer_mode')) {
            uss('developer_mode', $request->developer_mode === '1' ? '1' : '0');
            return redirect()->route('settings.index');
        }

        $settings = wncms()->setting()->getList();
        $availableSettings = array_merge(config('wncms-system-settings'), config('wncms.custom-settings') ?? []);

        if (gss('multi_website')) {
            $availableSettings['multisite'] = [
                'tab_name' => 'multisite',
                'tab_content' => [
                    ['type' => 'custom', 'name' => 'model_website_modes'],
                ],
            ];
        }

        Event::dispatch('wncms.backend.settings.tabs.extend', [&$availableSettings, $settings, $request]);

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
            $resolvedPackageId = method_exists($modelClass, 'getPackageId')
                ? ($modelClass::getPackageId() ?: 'wncms')
                : 'wncms';

            $routes = array_map(function ($route) use ($resolvedPackageId) {
                if (!is_array($route)) {
                    return $route;
                }

                $route['package_id'] = $route['package_id'] ?? $resolvedPackageId;
                return $route;
            }, $modelClass::getApiRoutes());

            $apiModels[$modelKey] = [
                'class' => $modelClass,
                'routes' => $routes,
                'package_id' => $resolvedPackageId,
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
        $resolvedWebsiteModes = $this->getResolvedModelWebsiteModes($settings);

        $multisiteModels = collect($modelDisplayList)
            ->filter(fn($modelData) => !empty($modelData['routes']))
            ->map(function ($modelData) use ($resolvedWebsiteModes) {
                $modelKey = !empty($modelData['model_key'])
                    ? Str::snake(Str::singular($modelData['model_key']))
                    : Str::snake(Str::singular($modelData['model_name']));

                return [
                    'key' => $modelKey,
                    'class' => $modelData['model_name_with_namespace'] ?? null,
                    'mode' => $resolvedWebsiteModes[$modelKey] ?? 'global',
                ];
            })
            ->values()
            ->all();

        return $this->view('wncms::backend.admin.settings.index', [
            'settings' => $settings,
            'page_title' => __('wncms::word.setting'),
            'availableSettings' => $availableSettings,
            'activeTab' => $activeTab,
            'apiModels' => $apiModels,
            'commonActions' => $commonActions,
            'otherActions' => $otherActions,
            'modelDisplayList' => $modelDisplayList,
            'multisiteModels' => $multisiteModels,
        ]);
    }


    public function update(Request $request)
    {
        if ($request->has('model_website_modes')) {
            $allowedModelKeys = collect(wncms_get_model_names())
                ->filter(fn($modelData) => !empty($modelData['routes']))
                ->map(function ($modelData) {
                    return !empty($modelData['model_key'])
                        ? Str::snake(Str::singular($modelData['model_key']))
                        : Str::snake(Str::singular($modelData['model_name']));
                })
                ->unique()
                ->values()
                ->all();

            $modes = collect((array) $request->model_website_modes)
                ->mapWithKeys(function ($mode, $modelKey) {
                    $normalizedKey = Str::snake(Str::singular($modelKey));
                    $normalizedMode = in_array($mode, ['global', 'single', 'multi'], true) ? $mode : 'global';
                    return [$normalizedKey => $normalizedMode];
                })
                ->only($allowedModelKeys)
                ->toArray();
            uss('model_website_modes', json_encode($modes));
        }

        foreach ((array) $request->settings as $key => $value) {
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

    protected function getResolvedModelWebsiteModes(array $settings = []): array
    {
        $modes = [];

        foreach ((array) config('wncms.model_website_modes', []) as $modelKey => $mode) {
            $normalizedKey = Str::snake(Str::singular($modelKey));
            if (in_array($mode, ['global', 'single', 'multi'], true)) {
                $modes[$normalizedKey] = $mode;
            }
        }

        foreach ((array) config('wncms.models', []) as $modelKey => $configData) {
            $normalizedKey = Str::snake(Str::singular($modelKey));
            $mode = $configData['website_mode'] ?? null;
            if (in_array($mode, ['global', 'single', 'multi'], true)) {
                $modes[$normalizedKey] = $mode;
            }
        }

        $dbModes = json_decode($settings['model_website_modes'] ?? '{}', true);
        if (is_array($dbModes)) {
            foreach ($dbModes as $modelKey => $mode) {
                $normalizedKey = Str::snake(Str::singular($modelKey));
                if (in_array($mode, ['global', 'single', 'multi'], true)) {
                    $modes[$normalizedKey] = $mode;
                }
            }
        }

        return $modes;
    }
}
