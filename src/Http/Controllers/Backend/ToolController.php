<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Wncms\Http\Controllers\Controller;
use Wncms\Services\Installer\InstallerManager;

class ToolController extends Controller
{
    public function index()
    {
        return view('wncms::backend.tools.index', [
            'page_title' => __('wncms::word.tools'),
        ]);
    }

    public function install_default_theme(Request $request)
    {
        $installer = new InstallerManager;
        $result = $installer->installDefaultThemeAssets();

        if ($request->ajax()) {
            return Response::json([
                'status' => $result['passed'] ? 'success' : 'fail',
                'message' => $result['passed']
                    ? __('wncms::word.install_default_theme_success')
                    : __('wncms::word.install_default_theme_failed'),
                'reload' => true,
            ], $result['passed'] ? 200 : 500);
        }

        if ($result['passed']) {
            return redirect()->back()->with('success', __('wncms::word.install_default_theme_success'));
        }

        return redirect()->back()->withErrors(['message' => __('wncms::word.install_default_theme_failed')]);
    }
}
