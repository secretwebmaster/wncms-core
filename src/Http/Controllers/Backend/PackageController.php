<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Wncms\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        $packages = wncms()->getRegisteredPackages();
        $installed = Package::all()->keyBy('package_id');

        return $this->view('backend.packages.index', [
            'packages' => $packages,
            'installed' => $installed,
            'page_title' => __('wncms::word.packages'),
        ]);
    }

    // Check for available updates
    public function check()
    {
        // check for update
    }

    public function activate(string $key)
    {
        $package = wncms()->activatePackage($key);
        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.package_activated_with_name', ['name' => $package->name]),
            'reload' => true,
            'restoreBtn' => false,
        ]);
    }

    public function deactivate(string $key)
    {
        $package = wncms()->deactivatePackage($key);
        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.package_deactivated_with_name', ['name' => $package->name]),
            'reload' => true,
            'restoreBtn' => false,
        ]);
    }
}
