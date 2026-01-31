<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wncms:update {product=core} {--version=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the WNCMS application by applying required update scripts.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting WNCMS update process.');

        $product = $this->argument('product');
        $targetVersion = $this->option('version'); // Optional specific version to update to

        // Step 1: Get the user's current version
        $currentVersion = gss("{$product}_version", '1.0.0');
        $this->info("Current version: {$currentVersion}");

        // Step 2: Fetch the list of available versions
        $availableVersions = $this->fetchAvailableVersions($product);
        if (empty($availableVersions)) {
            $this->error('No available updates found. Aborting.');
            return Command::FAILURE;
        }

        $this->info('Available versions: ' . implode(', ', $availableVersions));

        // Step 3: Filter updates that are later than the current version
        $updatesToRun = $this->filterUpdates($currentVersion, $availableVersions, $targetVersion);

        if (empty($updatesToRun)) {
            $this->info('No updates are required. Your system is up-to-date.');
            return Command::SUCCESS;
        }

        $this->info('Updates to apply: ' . implode(', ', $updatesToRun));

        // Step 4: Run the updates
        foreach ($updatesToRun as $version) {
            $this->info("Applying update for version: {$version}");
            if (!$this->runUpdateScript($product, $version)) {
                $this->error("Failed to apply update for version: {$version}");
                return Command::FAILURE;
            }
        }

        $this->info('All updates have been applied successfully.');
        return Command::SUCCESS;
    }

    /**
     * Fetch the list of available update versions from the API.
     *
     * @return array
     */
    protected function fetchAvailableVersions($product)
    {
        try {
            $response = Http::post("https://api.wncms.cc/api/v1/update/versions?product={$product}");

            if ($response->ok()) {
                $result = $response->json();
                $versions = $result['data'][$product]['versions'] ?? [];

                // Sort by semantic version including alpha/beta
                usort($versions, function ($a, $b) {
                    return version_compare($a, $b);
                });

                return $versions;
            }

            $this->error('Failed to fetch available versions: ' . $response->status());
        } catch (\Exception $e) {
            $this->error('Error fetching available versions: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Filter updates that are later than the current version.
     *
     * @param string $currentVersion
     * @param array $availableVersions
     * @return array
     */
    protected function filterUpdates($currentVersion, $availableVersions, $targetVersion = null)
    {
        $updates = array_filter($availableVersions, function ($version) use ($currentVersion, $targetVersion) {
            if ($targetVersion !== null && version_compare($version, $targetVersion, '>')) {
                return false;
            }
            return version_compare($version, $currentVersion, '>');
        });

        usort($updates, 'version_compare');

        return $updates;
    }

    /**
     * Run the update script for a specific version.
     *
     * @param string $version
     * @return bool
     */
    protected function runUpdateScript($product, $version)
    {
        $packageRoot = dirname(__DIR__, 3);
        $updateFile = $packageRoot . "/updates/update_{$product}_{$version}.php";

        if (!file_exists($updateFile)) {
            $this->info("Update script not found for {$product} version: {$version}. Skipping.");
            return true; // Consider skipping as success
        }

        try {
            include $updateFile;
            $this->info("Successfully applied update for {$product} version: {$version}");
            return true;
        } catch (\Throwable $e) {
            $this->error("Error applying update for {$product} version {$version}: {$e->getMessage()}");
            return false;
        }
    }
}
