<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;
use Wncms\Api\V2\OpenApiDocumentBuilder;

class GenerateApiV2OpenApi extends Command
{
    protected $signature = 'wncms:api-v2-openapi
        {--write= : Write the generated OpenAPI document to this path}
        {--check= : Verify this file exactly matches the generated document}';

    protected $description = 'Generate or validate the WNCMS API v2 OpenAPI document.';

    /**
     * Generate or validate the OpenAPI snapshot.
     *
     * @param  \Wncms\Api\V2\OpenApiDocumentBuilder  $builder
     *
     * @return int
     *
     * @throws \JsonException
     */
    public function handle(OpenApiDocumentBuilder $builder): int
    {
        $writePath = trim((string) $this->option('write'));
        $checkPath = trim((string) $this->option('check'));

        if (($writePath === '') === ($checkPath === '')) {
            $this->error('Provide exactly one of --write or --check.');

            return self::FAILURE;
        }

        $contents = json_encode(
            $builder->build(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        )."\n";

        if ($writePath !== '') {
            return $this->writeSnapshot($this->resolvePath($writePath), $contents);
        }

        return $this->checkSnapshot($this->resolvePath($checkPath), $contents);
    }

    /**
     * Write the normalized OpenAPI snapshot.
     *
     * @param  string  $path
     * @param  string  $contents
     *
     * @return int
     */
    private function writeSnapshot(string $path, string $contents): int
    {
        try {
            if (File::isDirectory($path)) {
                $this->error("Unable to write OpenAPI snapshot to {$path}: target is a directory.");

                return self::FAILURE;
            }

            File::ensureDirectoryExists(dirname($path));
            $bytes = File::put($path, $contents);
        } catch (Throwable $exception) {
            $this->error("Unable to write OpenAPI snapshot to {$path}: {$exception->getMessage()}");

            return self::FAILURE;
        }

        if ($bytes === false) {
            $this->error("Unable to write OpenAPI snapshot to {$path}.");

            return self::FAILURE;
        }

        $this->info("OpenAPI document written to {$path}.");

        return self::SUCCESS;
    }

    /**
     * Check a snapshot against the normalized generated document.
     *
     * @param  string  $path
     * @param  string  $contents
     *
     * @return int
     */
    private function checkSnapshot(string $path, string $contents): int
    {
        try {
            if (! File::exists($path)) {
                $this->error("OpenAPI snapshot not found at {$path}.");

                return self::FAILURE;
            }

            if (! File::isFile($path)) {
                $this->error("Unable to check OpenAPI snapshot at {$path}: target is not a file.");

                return self::FAILURE;
            }

            $actual = File::get($path);
        } catch (Throwable $exception) {
            $this->error("Unable to check OpenAPI snapshot at {$path}: {$exception->getMessage()}");

            return self::FAILURE;
        }

        if ($actual !== $contents) {
            $this->error("OpenAPI snapshot differs from the generated document at {$path}.");

            return self::FAILURE;
        }

        $this->info("OpenAPI snapshot matches {$path}.");

        return self::SUCCESS;
    }

    /**
     * Resolve relative snapshot paths from the Laravel application root.
     *
     * @param  string  $path
     *
     * @return string
     */
    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
