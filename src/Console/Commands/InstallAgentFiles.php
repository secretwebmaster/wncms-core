<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallAgentFiles extends Command
{
    protected $signature = 'wncms:install-agent-files
        {--force : Overwrite existing AGENTS.md and .github/skills without prompting}
        {--dry-run : Preview actions without writing files}';

    protected $description = 'Publish WNCMS agent files (AGENTS.md and .github/skills) into the host project root';

    public function handle(): int
    {
        $sourceRoot = dirname(__DIR__, 3) . '/resources/agent-files';

        if (!is_dir($sourceRoot)) {
            $this->error("Agent source directory not found: {$sourceRoot}");
            return Command::FAILURE;
        }

        $targets = [
            'AGENTS.md' => 'AGENTS.md',
            '.github/skills' => '.github/skills',
        ];

        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $copied = 0;
        $overwritten = 0;
        $skipped = 0;

        foreach ($targets as $relativeSource => $relativeTarget) {
            $source = $sourceRoot . '/' . $relativeSource;
            $target = base_path($relativeTarget);

            if (!file_exists($source)) {
                $this->warn("Source missing, skipped: {$relativeSource}");
                $skipped++;
                continue;
            }

            $targetExists = file_exists($target);
            $allowOverwrite = false;

            if ($targetExists) {
                if ($force) {
                    $allowOverwrite = true;
                    $this->line("Force overwrite enabled: {$relativeTarget}");
                } elseif ($dryRun) {
                    $this->line("Would prompt overwrite for existing target: {$relativeTarget}");
                    $skipped++;
                    continue;
                } else {
                    $allowOverwrite = $this->confirm("Target exists ({$relativeTarget}). Overwrite this target?", false);
                    if (!$allowOverwrite) {
                        $this->line("Skipped existing target: {$relativeTarget}");
                        $skipped++;
                        continue;
                    }
                }
            }

            if (is_dir($source)) {
                [$dirCopied, $dirOverwritten, $dirSkipped] = $this->copyDirectory($source, $target, $allowOverwrite, $dryRun);
                $copied += $dirCopied;
                $overwritten += $dirOverwritten;
                $skipped += $dirSkipped;
                continue;
            }

            if ($dryRun) {
                $this->line("Would copy file: {$relativeSource} -> {$relativeTarget}");
                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);

            if ($targetExists) {
                $overwritten++;
                $this->info("Overwritten: {$relativeTarget}");
            } else {
                $copied++;
                $this->info("Copied: {$relativeTarget}");
            }
        }

        $this->newLine();
        $this->line('Summary');
        $this->line("Copied: {$copied}");
        $this->line("Overwritten: {$overwritten}");
        $this->line("Skipped: {$skipped}");

        if ($dryRun) {
            $this->comment('Dry run only. No files were changed.');
        }

        return Command::SUCCESS;
    }

    protected function copyDirectory(string $sourceDir, string $targetDir, bool $allowOverwrite, bool $dryRun): array
    {
        $copied = 0;
        $overwritten = 0;
        $skipped = 0;

        $sourceFiles = File::allFiles($sourceDir);

        foreach ($sourceFiles as $sourceFile) {
            $sourcePath = $sourceFile->getPathname();
            $relativePath = ltrim(str_replace($sourceDir, '', $sourcePath), DIRECTORY_SEPARATOR);
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $relativePath;
            $targetExists = file_exists($targetPath);

            if ($targetExists && !$allowOverwrite) {
                $skipped++;
                $this->line("Skipped existing file: " . $this->relativeFromBasePath($targetPath));
                continue;
            }

            if ($dryRun) {
                $this->line("Would copy file: {$relativePath} -> " . $this->relativeFromBasePath($targetPath));
                continue;
            }

            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($sourcePath, $targetPath);

            if ($targetExists) {
                $overwritten++;
            } else {
                $copied++;
            }
        }

        return [$copied, $overwritten, $skipped];
    }

    protected function relativeFromBasePath(string $path): string
    {
        return ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR);
    }
}
