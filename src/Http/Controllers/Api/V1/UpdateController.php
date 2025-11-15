<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
    public function update(Request $request)
    {
        info('[UpdateController] Incoming request', $request->all());

        if (gss('disable_core_update')) {
            info('[UpdateController] Core update disabled by config');
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.core_update_disabled'),
            ]);
        }

        // block if already updating and lock < 3 minutes
        if (gss('updating_core') && gss('update_lock') > time() - 180) {
            info('[UpdateController] Another update is already in progress');
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.core_update_in_progress'),
            ]);
        }

        info('[UpdateController] Setting update lock + status');
        uss('updating_core', 1);
        uss('update_lock', time());

        $package = $request->package;
        $version = $request->version; // optional

        if (empty($package)) {
            info('[UpdateController] Invalid request: missing package');
            uss('updating_core', 0);
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.invalid_update_request'),
            ]);
        }

        try {
            $params = ['package' => $package];
            if (!empty($version)) {
                $params['version'] = $version;
            }

            info('[UpdateController] Calling wncms:update-package with params', $params);
            Artisan::call('wncms:update-package', $params);

            $exitCode = Artisan::output();
            info('[UpdateController] Artisan output: ' . $exitCode);
            info("[UpdateController] Core version updated to {$version}");

            $status = [
                'status' => 'success',
                'message' => __('wncms::word.successfully_updated'),
                'version' => $version ?: __('wncms::word.latest'),
            ];

        } catch (\Throwable $e) {
            info('[UpdateController] Update failed: ' . $e->getMessage());
            $status = [
                'status' => 'fail',
                'message' => __('wncms::word.update_failed') . ': ' . $e->getMessage(),
            ];
        }

        // always release lock
        uss('updating_core', 0);
        info('[UpdateController] Update process finished, lock released');

        return response()->json($status);
    }

    public function progress(Request $request)
    {
        if ($request->itemId === 'core') {
            info('[UpdateController] Checking progress for core update');
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_fetched_updating_progress'),
                'progress' => gss('updating_core', 0, false),
            ]);
        }
    }
}
