<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Wncms\Http\Controllers\Controller;
use Wncms\Services\Installer\InstallerManager;

class ToolController extends Controller
{
    public function index()
    {
        $coreUpdateVersions = $this->getCoreUpdateVersions();

        return view('wncms::backend.tools.index', [
            'page_title' => __('wncms::word.tools'),
            'core_update_versions' => $coreUpdateVersions,
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

    public function rerun_core_update(Request $request)
    {
        $request->validate([
            'version' => 'required|string',
        ]);

        $version = $this->normalizeVersion((string) $request->input('version'));
        $coreUpdateVersions = $this->getCoreUpdateVersions();

        if ($version === '' || !in_array($version, $coreUpdateVersions, true)) {
            $message = __('wncms::word.update_version_not_found', ['version' => $version ?: (string) $request->input('version')]);

            if ($request->ajax()) {
                return Response::json([
                    'status' => 'fail',
                    'message' => $message,
                ], 422);
            }

            return redirect()->back()->withInput()->withErrors(['message' => $message]);
        }

        $exitCode = Artisan::call('wncms:update', [
            'product' => 'core',
            '--version' => $version,
        ]);

        $output = trim((string) Artisan::output());

        if ($exitCode !== 0) {
            $message = __('wncms::word.rerun_core_update_failed', ['version' => $version]);
            if (!empty($output)) {
                $message .= ' ' . $output;
            }

            if ($request->ajax()) {
                return Response::json([
                    'status' => 'fail',
                    'message' => $message,
                ], 500);
            }

            return redirect()->back()->withInput()->withErrors(['message' => $message]);
        }

        $message = __('wncms::word.rerun_core_update_success', ['version' => $version]);

        if ($request->ajax()) {
            return Response::json([
                'status' => 'success',
                'message' => $message,
                'reload' => false,
            ]);
        }

        return redirect()->back()->withMessage($message);
    }

    protected function getCoreUpdateVersions(): array
    {
        $versions = [];
        $updatesPath = rtrim(WNCMS_ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'updates';
        $updateFiles = glob($updatesPath . DIRECTORY_SEPARATOR . 'update_core_*.php') ?: [];

        foreach ($updateFiles as $file) {
            $filename = basename($file);
            if (!preg_match('/^update_core_(.+)\.php$/', $filename, $matches)) {
                continue;
            }

            $version = $this->normalizeVersion($matches[1]);
            if ($version !== '') {
                $versions[] = $version;
            }
        }

        $versions = array_values(array_unique($versions));
        usort($versions, function ($a, $b) {
            return version_compare($b, $a);
        });

        return $versions;
    }

    protected function normalizeVersion(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }
}
