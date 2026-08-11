<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Wncms\Api\V2\ApiContractValidator;

class CheckBackendApiV2Parity extends Command
{
    protected bool $jsonEncodingFailed = false;

    protected $signature = 'wncms:check-backend-api-v2-parity
        {--coverage : Report configured v7 AI-first domain coverage}
        {--contract : Validate API v2 registry, runtime routes, and OpenAPI consistency}
        {--json : Output result data as JSON}';

    protected $description = 'Ensure backend API v2 parity and report v7 AI-first automation coverage.';

    /**
     * Run the selected parity or coverage check.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->jsonEncodingFailed = false;

        if ((bool) $this->option('contract')) {
            $exitCode = $this->handleContractMode();
        } elseif ((bool) $this->option('coverage')) {
            $exitCode = $this->handleCoverageReport();
        } else {
            $exitCode = $this->handleBackendApiV2Parity();
        }

        return $this->jsonEncodingFailed ? self::FAILURE : $exitCode;
    }

    /**
     * Resolve and validate the installed API v2 contract safely.
     *
     * Provider registration and OpenAPI generation both occur while the
     * validator is resolved, so this command boundary must retain the
     * machine-readable result even when either dependency cannot be built.
     *
     * @return int
     */
    protected function handleContractMode(): int
    {
        try {
            return $this->handleContractValidation(app(ApiContractValidator::class));
        } catch (\Throwable) {
            return $this->handleContractBootstrapFailure();
        }
    }

    /**
     * Report a contract dependency construction failure.
     *
     * Exception identities and messages are intentionally omitted because
     * provider supplied values are not safe or stable automation fields.
     *
     * @return int
     */
    protected function handleContractBootstrapFailure(): int
    {
        $errors = [
            'contract.bootstrap_failed' => [
                [
                    'reason' => 'API v2 contract dependencies could not be constructed.',
                ],
            ],
        ];
        $report = [
            'operation_count' => 0,
            'v7_parity_eligible' => false,
            'v7_parity_ineligible_operation_ids' => [],
            'errors' => $errors,
            'warnings' => [],
        ];
        $message = 'API v2 contract validation could not start.';

        $this->outputResult([
            'code' => 500,
            'status' => 'fail',
            'message' => $message,
            'data' => $report,
            'meta' => $this->resultMeta('api-v2-contract'),
            'errors' => $errors,
        ], true, function () use ($message, $errors): void {
            $this->error($message);
            $this->line('Error group count: '.count($errors));
        });

        return self::FAILURE;
    }

    /**
     * Validate the installed API v2 contract for CI and automation consumers.
     *
     * @param  \Wncms\Api\V2\ApiContractValidator  $validator
     * @return int
     */
    protected function handleContractValidation(ApiContractValidator $validator): int
    {
        $report = $validator->validate();
        $failed = $report['errors'] !== [];
        $message = $failed
            ? 'API v2 contract validation failed.'
            : 'API v2 contract validation passed.';

        $this->outputResult([
            'code' => $failed ? 500 : 200,
            'status' => $failed ? 'fail' : 'success',
            'message' => $message,
            'data' => $report,
            'meta' => $this->resultMeta('api-v2-contract'),
            'errors' => $failed ? $report['errors'] : [],
        ], $failed, function () use ($message, $report, $failed): void {
            if ($failed) {
                $this->error($message);
            } else {
                $this->info($message);
            }

            $this->line('Registered operation count: '.$report['operation_count']);
            $this->line('Error group count: '.count($report['errors']));
            $this->line('Warning group count: '.count($report['warnings']));
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Ensure backend business route names have backend API v2 equivalents.
     *
     * @return int
     */
    protected function handleBackendApiV2Parity(): int
    {
        $backendRouteFile = __DIR__ . '/../../../routes/backend.php';
        if (!file_exists($backendRouteFile)) {
            $this->outputResult([
                'code' => 500,
                'status' => 'fail',
                'message' => 'backend.php not found',
                'data' => null,
                'meta' => $this->resultMeta('backend-api-v2-parity'),
                'errors' => [
                    'backend_route_file' => [$backendRouteFile],
                ],
            ], true);
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
            $this->outputResult([
                'code' => 500,
                'status' => 'fail',
                'message' => 'Missing backend API v2 equivalents.',
                'data' => [
                    'checked_count' => count($businessRouteNames),
                    'missing_backend_route_names' => $missing,
                ],
                'meta' => $this->resultMeta('backend-api-v2-parity'),
                'errors' => [
                    'missing_backend_api_v2_routes' => array_map(
                        fn (string $name) => "api.v2.backend.{$name}",
                        $missing
                    ),
                ],
            ], true);
            return self::FAILURE;
        }

        $this->outputResult([
            'code' => 200,
            'status' => 'success',
            'message' => 'Backend API v2 parity check passed.',
            'data' => [
                'checked_count' => count($businessRouteNames),
                'missing_backend_route_names' => [],
            ],
            'meta' => $this->resultMeta('backend-api-v2-parity'),
            'errors' => [],
        ], false);

        return self::SUCCESS;
    }

    /**
     * Print the configured v7 coverage report.
     *
     * @return int
     */
    protected function handleCoverageReport(): int
    {
        $report = $this->buildCoverageReport();

        $this->outputResult([
            'code' => 200,
            'status' => 'success',
            'message' => 'V7 AI-first coverage report generated.',
            'data' => $report,
            'meta' => $this->resultMeta('v7-ai-first-coverage'),
            'errors' => [],
        ], false, fn () => $this->renderCoverageReport($report));

        return self::SUCCESS;
    }

    /**
     * Build coverage data from config and runtime registries.
     *
     * @return array
     */
    protected function buildCoverageReport(): array
    {
        $domains = (array) config('wncms-backend-api-v2.coverage.domains', []);
        $commandNames = $this->registeredCommandNames();
        $rows = [];
        $summary = [];

        foreach ($this->coverageSurfaceDefinitions() as $surfaceKey => $surface) {
            $summary[$surfaceKey] = [
                'label' => $surface['label'],
                'Complete' => 0,
                'Partial' => 0,
                'Missing' => 0,
                'Not applicable' => 0,
                'Needs design' => 0,
            ];
        }

        foreach ($domains as $domainKey => $definition) {
            $statuses = [];

            foreach ($this->coverageSurfaceDefinitions() as $surfaceKey => $surface) {
                $assessment = $this->assessCoverageSurface(
                    $definition,
                    $surface,
                    $commandNames
                );

                $statuses[$surfaceKey] = $assessment;
                if (isset($summary[$surfaceKey][$assessment['status']])) {
                    $summary[$surfaceKey][$assessment['status']]++;
                }
            }

            $rows[] = [
                'key' => (string) $domainKey,
                'label' => (string) ($definition['label'] ?? $domainKey),
                'reference' => (bool) ($definition['reference'] ?? false),
                'surfaces' => $statuses,
            ];
        }

        return [
            'domains' => $rows,
            'summary' => $summary,
            'reference_domain' => (string) config('wncms-backend-api-v2.coverage.reference_domain', 'links'),
        ];
    }

    /**
     * Render coverage data as a compact CLI table.
     *
     * @param array $report
     * @return void
     */
    protected function renderCoverageReport(array $report): void
    {
        $this->line('WNCMS v7 AI-first Coverage Report');
        $this->line('Reference domain: ' . ($report['reference_domain'] ?? 'links'));
        $this->newLine();

        $rows = [];
        foreach ((array) ($report['domains'] ?? []) as $domain) {
            $surfaces = (array) ($domain['surfaces'] ?? []);
            $rows[] = [
                ($domain['reference'] ?? false) ? $domain['label'] . ' *' : $domain['label'],
                $surfaces['backend_ui']['status'] ?? 'Missing',
                $surfaces['api_v2']['status'] ?? 'Missing',
                $surfaces['cli']['status'] ?? 'Missing',
                $surfaces['mcp']['status'] ?? 'Missing',
                $surfaces['docs']['status'] ?? 'Missing',
                $surfaces['tests']['status'] ?? 'Missing',
            ];
        }

        $this->table(
            ['Domain', 'Backend UI', 'API v2', 'CLI', 'MCP', 'Docs', 'Tests'],
            $rows
        );

        $this->line('* Recommended reference domain for v7 parity implementation.');
        $this->line('Use --json for missing item details.');
    }

    /**
     * Assess one coverage surface for a domain.
     *
     * @param array $definition
     * @param array $surface
     * @param array $commandNames
     * @return array
     */
    protected function assessCoverageSurface(array $definition, array $surface, array $commandNames): array
    {
        $items = $this->resolveCoverageItems($definition, $surface);

        if ($items === null) {
            return $this->coverageAssessment('Not applicable', [], [], []);
        }

        if (is_string($items) && in_array($items, ['Complete', 'Partial', 'Missing', 'Not applicable', 'Needs design'], true)) {
            return $this->coverageAssessment($items, [], [], []);
        }

        $items = array_values(array_filter(array_map('strval', (array) $items)));
        if (empty($items)) {
            return $this->coverageAssessment('Missing', [], [], []);
        }

        $found = [];
        $missing = [];

        foreach ($items as $item) {
            $exists = match ($surface['type']) {
                'route' => Route::has($item),
                'command' => in_array($item, $commandNames, true),
                'file' => $this->coverageFileExists($item),
                'configured' => true,
                default => false,
            };

            if ($exists) {
                $found[] = $item;
            } else {
                $missing[] = $item;
            }
        }

        if (count($found) === count($items)) {
            $status = 'Complete';
        } elseif (!empty($found)) {
            $status = 'Partial';
        } else {
            $status = 'Missing';
        }

        $statusOverride = (string) (($definition['surface_statuses'] ?? [])[$surface['key']] ?? '');
        if (in_array($statusOverride, ['Complete', 'Partial', 'Missing', 'Not applicable', 'Needs design'], true)) {
            $status = $statusOverride;
        }

        return $this->coverageAssessment($status, $items, $found, $missing);
    }

    /**
     * Resolve configured items for a coverage surface.
     *
     * @param array $definition
     * @param array $surface
     * @return array|string|null
     */
    protected function resolveCoverageItems(array $definition, array $surface): array|string|null
    {
        if (($surface['key'] ?? null) === 'api_v2') {
            return $this->resolveApiV2CoverageRoutes($definition);
        }

        $configKey = (string) $surface['config_key'];
        return array_key_exists($configKey, $definition) ? $definition[$configKey] : [];
    }

    /**
     * Resolve backend API v2 route names from explicit routes, resources, and actions.
     *
     * @param array $definition
     * @return array|string|null
     */
    protected function resolveApiV2CoverageRoutes(array $definition): array|string|null
    {
        if (array_key_exists('api_v2_routes', $definition)) {
            return $definition['api_v2_routes'];
        }

        $routes = [];

        foreach ((array) ($definition['api_v2_resources'] ?? []) as $resource) {
            $routes = array_merge($routes, $this->apiV2ResourceRouteNames((string) $resource));
        }

        foreach ((array) ($definition['api_v2_actions'] ?? []) as $action) {
            $routes[] = 'api.v2.backend.' . $action;
        }

        return array_values(array_unique($routes));
    }

    /**
     * Resolve backend API v2 resource route names from resource config.
     *
     * @param string $resource
     * @return array
     */
    protected function apiV2ResourceRouteNames(string $resource): array
    {
        $resourceConfig = (array) config("wncms-backend-api-v2.resources.{$resource}", []);
        if (empty($resourceConfig)) {
            return [];
        }

        $enabledActions = $resourceConfig['enabled_actions'] ?? ['index', 'show', 'store', 'update', 'destroy', 'bulk_delete'];
        $routes = [];

        foreach ((array) $enabledActions as $action) {
            if ($action === 'bulk_delete' && ($resourceConfig['enable_bulk_delete'] ?? true) !== true) {
                continue;
            }

            $routes[] = "api.v2.backend.{$resource}.{$action}";
        }

        return $routes;
    }

    /**
     * Return surface definitions used by the v7 coverage report.
     *
     * @return array
     */
    protected function coverageSurfaceDefinitions(): array
    {
        return [
            'backend_ui' => [
                'key' => 'backend_ui',
                'label' => 'Backend UI',
                'config_key' => 'backend_routes',
                'type' => 'route',
            ],
            'api_v2' => [
                'key' => 'api_v2',
                'label' => 'API v2',
                'config_key' => 'api_v2_routes',
                'type' => 'route',
            ],
            'cli' => [
                'key' => 'cli',
                'label' => 'CLI',
                'config_key' => 'cli_commands',
                'type' => 'command',
            ],
            'mcp' => [
                'key' => 'mcp',
                'label' => 'MCP',
                'config_key' => 'mcp_tools',
                'type' => 'configured',
            ],
            'docs' => [
                'key' => 'docs',
                'label' => 'Docs',
                'config_key' => 'docs',
                'type' => 'file',
            ],
            'tests' => [
                'key' => 'tests',
                'label' => 'Tests',
                'config_key' => 'tests',
                'type' => 'file',
            ],
        ];
    }

    /**
     * Format a coverage assessment payload.
     *
     * @param string $status
     * @param array $expected
     * @param array $found
     * @param array $missing
     * @return array
     */
    protected function coverageAssessment(string $status, array $expected, array $found, array $missing): array
    {
        return [
            'status' => $status,
            'expected' => $expected,
            'found' => $found,
            'missing' => $missing,
        ];
    }

    /**
     * Return registered Artisan command names.
     *
     * @return array
     */
    protected function registeredCommandNames(): array
    {
        $names = array_keys(Artisan::all());
        sort($names);

        return $names;
    }

    /**
     * Check whether a configured coverage file exists in the core package or host app.
     *
     * @param string $path
     * @return bool
     */
    protected function coverageFileExists(string $path): bool
    {
        foreach ($this->coverageFileCandidates($path) as $candidate) {
            if (File::exists($candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build file candidates for package-relative and app-relative coverage paths.
     *
     * @param string $path
     * @return array
     */
    protected function coverageFileCandidates(string $path): array
    {
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        $coreRoot = defined('WNCMS_ROOT')
            ? rtrim(WNCMS_ROOT, DIRECTORY_SEPARATOR)
            : rtrim((string) realpath(__DIR__ . '/../../..'), DIRECTORY_SEPARATOR);

        return array_values(array_unique([
            $coreRoot . DIRECTORY_SEPARATOR . $path,
            base_path($path),
        ]));
    }

    /**
     * Output a result as JSON or through a human-readable callback.
     *
     * @param array $result
     * @param bool $isError
     * @param callable|null $humanRenderer
     * @return void
     */
    protected function outputResult(array $result, bool $isError, ?callable $humanRenderer = null): void
    {
        if ((bool) $this->option('json')) {
            try {
                $encoded = json_encode(
                    $result,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                );
            } catch (\JsonException) {
                $this->jsonEncodingFailed = true;
                $this->line($this->fixedJsonEncodingFailure(
                    ($result['meta']['mode'] ?? null) === 'api-v2-contract'
                ));

                return;
            }

            $this->line($encoded);
            return;
        }

        if ($humanRenderer) {
            $humanRenderer();
            return;
        }

        if ($isError) {
            $this->error((string) $result['message']);
            foreach ((array) ($result['data']['missing_backend_route_names'] ?? []) as $name) {
                $this->line("- {$name}");
            }
            return;
        }

        $this->info((string) $result['message']);
        if (isset($result['data']['checked_count'])) {
            $this->line('Checked route count: ' . $result['data']['checked_count']);
        }
    }

    /**
     * Build a fixed JSON failure payload from static safe values.
     *
     * @param  bool  $contractMode
     * @return string
     *
     * @throws \JsonException
     */
    protected function fixedJsonEncodingFailure(bool $contractMode): string
    {
        $errors = [
            'contract.output_encoding_failed' => [
                [
                    'reason' => 'Command result could not be encoded as JSON.',
                ],
            ],
        ];
        $data = $contractMode
            ? [
                'operation_count' => 0,
                'v7_parity_eligible' => false,
                'v7_parity_ineligible_operation_ids' => [],
                'errors' => $errors,
                'warnings' => [],
            ]
            : [
                'errors' => $errors,
                'warnings' => [],
            ];

        return json_encode([
            'code' => 500,
            'status' => 'fail',
            'message' => 'Command result could not be encoded as JSON.',
            'data' => $data,
            'meta' => [
                'surface' => 'cli',
                'command' => 'wncms:check-backend-api-v2-parity',
                'mode' => $contractMode ? 'api-v2-contract' : 'command-output',
            ],
            'errors' => $errors,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Build consistent CLI automation metadata.
     *
     * @param string $mode
     * @return array
     */
    protected function resultMeta(string $mode): array
    {
        return [
            'surface' => 'cli',
            'command' => (string) $this->getName(),
            'mode' => $mode,
        ];
    }

    /**
     * Extract backend route names from the core backend route file.
     *
     * @param string $file
     * @return array
     */
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

    /**
     * Filter route names that should be represented by backend API v2.
     *
     * @param array $names
     * @return array
     */
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
