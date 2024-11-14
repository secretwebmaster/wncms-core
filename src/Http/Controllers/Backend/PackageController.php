<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PackageController extends Controller
{
    // Display installed packages
    public function index()
    {
        $composerLockPath = base_path('composer.lock');
        $packages = [];

        if (File::exists($composerLockPath)) {
            $composerData = json_decode(File::get($composerLockPath), true);

            $packages = array_filter($composerData['packages'] ?? [], function ($package) {
                return in_array('wncms', $package['keywords'] ?? []);
            });
        }

        return view('wncms::backend.packages.index', [
            'packages' => $packages,
            'page_title' => __('wncms::word.packages'),
        ]);
    }

    // Check for available updates
    public function check()
    {
        $process = new Process(['composer', 'outdated', '--format=json'], base_path());
        $process->setTimeout(300);

        try {
            $process->mustRun();
            $outdatedPackages = json_decode($process->getOutput(), true)['installed'] ?? [];

            $updates = array_filter($outdatedPackages, function ($package) {
                return in_array('wncms', $package['keywords'] ?? []);
            });

            $updates = array_map(function ($package) {
                return [
                    'name' => $package['name'],
                    'version' => $package['version'],
                    'latest' => $package['latest'],
                ];
            }, $updates);

            return response()->json(['updates' => $updates]);
        } catch (ProcessFailedException $exception) {
            return response()->json(['error' => 'Failed to check for updates: ' . $exception->getMessage()], 500);
        }
    }

    // Add a Composer package
    public function add(Request $request)
    {
        parse_str($request->formData, $formData);

        $package = $formData['package'];
        $version = $formData['version'];

        $command = $version ? ["composer", "require", "{$package}:{$version}"] : ["composer", "require", $package];

        $result = $this->runComposerCommand($command, 'Package added successfully.');
        if ($result['error']) {
            return response()->json(['status' => 'error', 'message' => $result['output']], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.package_added_successfully'),
            'data' => $result,
            'reload' => true,
        ]);
    }

    // Update a Composer package
    public function update(Request $request)
    {
        $request->validate([
            'package' => 'required|string',
            'version' => 'nullable|string',
        ]);

        $package = $request->package;
        // Remove leading "v" if present
        $version = $request->version ? preg_replace("/^v/", "", $request->version) : null;

        // Use an exact version rather than caret (^) for greater control
        $command = $version ? ["composer", "require", "{$package}:{$version}"] : ["composer", "require", $package];

        // Run the Composer command to install/update the package
        $result = $this->runComposerCommand($command, 'Package updated successfully.');

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.package_updated_successfully'),
            'data' => $result,
            'reload' => true,
        ]);
    }

    // Remove a Composer package
    public function remove(Request $request)
    {
        $request->validate([
            'package' => 'required|string',
        ]);

        $package = $request->package;
        $command = ["composer", "remove", $package];

        $result = $this->runComposerCommand($command, 'Package removed successfully.');
        if ($result['error']) {
            return response()->json(['status' => 'error', 'message' => $result['output']], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.package_removed_successfully'),
            'data' => $result,
            'reload' => true,
        ]);
    }

    // Run Composer commands
    private function runComposerCommand(array $command, string $successMessage)
    {
        $process = new Process($command);
        $process->setWorkingDirectory(base_path()); // Ensure it runs in the project root
        $process->run();

        if (!$process->isSuccessful()) {
            // Return an array with an error key to identify errors properly
            return [
                'error' => true,
                'output' => $process->getErrorOutput(),
            ];
        }

        return [
            'error' => false,
            'output' => $process->getOutput(),
            'message' => $successMessage,
        ];
    }
}
