<?php

namespace Wncms\Http\Controllers\Backend;

use Symfony\Component\Console\Exception\CommandNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Wncms\Http\Controllers\Controller;
use Wncms\Services\Installer\InstallerManager;

class ToolController extends Controller
{
    public function index(Request $request)
    {
        $coreUpdateVersions = $this->getCoreUpdateVersions();

        $view = 'backend.tools.index';
        $params = [
            'page_title' => __('wncms::word.tools'),
            'core_update_versions' => $coreUpdateVersions,
        ];

        Event::dispatch('wncms.backend.tools.index.resolve', [&$view, &$params, $request]);

        return $this->view($view, $params);
    }

    public function install_default_theme(Request $request)
    {
        try {
            $installer = new InstallerManager;
            $result = $installer->installDefaultThemeAssets();
        } catch (\Throwable $e) {
            report($e);

            $result = [
                'passed' => false,
                'status' => 1,
                'output' => $e->getMessage(),
            ];
        }

        $message = $result['passed']
            ? __('wncms::word.install_default_theme_success')
            : $this->resolveInstallDefaultThemeFailedMessage($result);

        $statusCode = 200;
        if (!$result['passed']) {
            $statusCode = $this->isPermissionRelatedInstallError((string) ($result['output'] ?? '')) ? 422 : 500;
        }

        if ($request->ajax()) {
            return Response::json([
                'status' => $result['passed'] ? 'success' : 'fail',
                'message' => $message,
                'reload' => true,
            ], $statusCode);
        }

        if ($result['passed']) {
            return redirect()->back()->with('success', $message);
        }

        return redirect()->back()->withErrors(['message' => $message]);
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

        try {
            $exitCode = Artisan::call('wncms:update', [
                'product' => 'core',
                '--rerun-version' => $version,
            ]);
        } catch (CommandNotFoundException $e) {
            $message = __('wncms::word.rerun_core_update_failed', ['version' => $version])
                . ' ' . 'Command not available in current runtime. Please clear cache and ensure package provider is loaded.';

            if ($request->ajax()) {
                return Response::json([
                    'status' => 'fail',
                    'message' => $message,
                ], 500);
            }

            return redirect()->back()->withInput()->withErrors(['message' => $message]);
        }

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

    protected function resolveInstallDefaultThemeFailedMessage(array $result): string
    {
        $output = (string) ($result['output'] ?? '');

        if ($this->isPermissionRelatedInstallError($output)) {
            return __('wncms::word.install_default_theme_permission_failed', [
                'tool_name' => __('wncms::word.fix_permission'),
            ]);
        }

        return __('wncms::word.install_default_theme_failed');
    }

    protected function isPermissionRelatedInstallError(string $message): bool
    {
        $normalizedMessage = strtolower($message);
        $keywords = [
            'permission denied',
            'insufficient permissions',
            'insufficient permission',
            'not writable',
            'unable to write',
            'unable to create',
            'failed to open stream',
            'operation not permitted',
            'access is denied',
            'read-only file system',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($normalizedMessage, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
