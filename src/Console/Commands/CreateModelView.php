<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateModelView extends Command
{
    protected array $starterFiles = [
        'index.blade.php',
        'create.blade.php',
        'edit.blade.php',
        'form-items.blade.php',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wncms:create-model-view {model_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quickly create custom view files for new Eloquent model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $starterPath = $this->resolveStarterPath();

        if ($starterPath === null) {
            $this->error('Starter blade files are not found. Checked these paths:');

            foreach ($this->getStarterPathCandidates() as $candidate) {
                $this->line("- {$candidate}");
            }

            return self::FAILURE;
        }

        // namings
        $model_name = str()->snake($this->argument('model_name'));
        $singular_camel = str($model_name)->camel()->singular();
        $singular_snake = str($model_name)->snake()->singular();
        $plural_snake = str($model_name)->snake()->plural();

        foreach ($this->starterFiles as $fileName) {
            $source = "{$starterPath}/{$fileName}";

            if (!File::exists($source)) {
                $this->error("Source view file not found: {$source}");
                continue;
            }

            $target = resource_path("views/backend/{$plural_snake}/" . basename($source));
            $directory = dirname($target);

            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info("created directory {$directory}");
            }

            if (File::exists($target)) {
                $this->error("File {$target} already exists");
                continue;
            }

            File::copy($source, $target);

            $content = File::get($target);

            $content = str_replace('backend.starters.', "backend.{$plural_snake}.", $content);
            $content = str_replace('starter', $singular_snake, $content);
            $content = str_replace('$' . $singular_snake, '$' . $singular_camel, $content);

            File::put($target, $content);

            $this->info("copied starter file to {$target}");
        }

        return self::SUCCESS;
    }

    protected function resolveStarterPath(): ?string
    {
        foreach ($this->getStarterPathCandidates() as $path) {
            $isValid = true;
            foreach ($this->starterFiles as $fileName) {
                if (!File::exists("{$path}/{$fileName}")) {
                    $isValid = false;
                    break;
                }
            }

            if ($isValid) {
                return $path;
            }
        }

        return null;
    }

    protected function getStarterPathCandidates(): array
    {
        $candidates = [
            wncms()->getPackageRootPath('resources/views/backend/starters'),
            wncms()->getPackageRootPath('../resources/views/backend/starters'),
            dirname(__DIR__, 3) . '/resources/views/backend/starters',
        ];

        return array_values(array_unique(array_filter($candidates)));
    }
}
