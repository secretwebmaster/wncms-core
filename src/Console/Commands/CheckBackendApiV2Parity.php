<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class CheckBackendApiV2Parity extends Command
{
    protected $signature = 'wncms:check-backend-api-v2-parity';
    protected $description = 'Ensure backend business routes have equivalent backend API v2 route names.';

    public function handle(): int
    {
        $backendRouteFile = __DIR__ . '/../../../routes/backend.php';
        if (!file_exists($backendRouteFile)) {
            $this->error('backend.php not found');
            return self::FAILURE;
        }

        $backendRouteNames = $this->extractBackendRouteNames($backendRouteFile);
        $businessRouteNames = $this->filterBusinessRouteNames($backendRouteNames);

        $missing = [];
        foreach ($businessRouteNames as $backendName) {
            $apiName = "api.v2.backend.{$backendName}";
            if (!Route::has($apiName)) {
                $missing[] = $backendName;
            }
        }

        if (!empty($missing)) {
            $this->error('Missing backend API v2 equivalents:');
            foreach ($missing as $name) {
                $this->line("- {$name}");
            }
            return self::FAILURE;
        }

        $this->info('Backend API v2 parity check passed.');
        $this->line('Checked route count: ' . count($businessRouteNames));

        return self::SUCCESS;
    }

    protected function extractBackendRouteNames(string $file): array
    {
        $content = file($file, FILE_IGNORE_NEW_LINES) ?: [];
        $names = [];

        foreach ($content as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '//')) {
                continue;
            }

            if (preg_match_all("/->name\\('([^']+)'\\)/", $line, $matches)) {
                foreach ($matches[1] as $name) {
                    $names[] = $name;
                }
            }
        }

        $names = array_values(array_unique($names));
        sort($names);
        return $names;
    }

    protected function filterBusinessRouteNames(array $names): array
    {
        $excludedSuffixes = (array) config('wncms-backend-api-v2.parity.excluded_suffixes', []);
        $excludedNames = (array) config('wncms-backend-api-v2.parity.excluded_names', []);

        return array_values(array_filter($names, function (string $name) use ($excludedSuffixes, $excludedNames) {
            if (in_array($name, $excludedNames, true)) {
                return false;
            }

            foreach ($excludedSuffixes as $suffix) {
                if (str_ends_with($name, ".{$suffix}")) {
                    return false;
                }
            }

            return true;
        }));
    }
}

