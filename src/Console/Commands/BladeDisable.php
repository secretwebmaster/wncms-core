<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Wncms\Services\Security\BladeAvailabilityService;

final class BladeDisable extends Command
{
    protected $signature = 'wncms:blade:disable {--force} {--json}';
    protected $description = 'Disable all WNCMS Blade UI surfaces';

    public function handle(BladeAvailabilityService $availability): int
    {
        if (! $this->option('force')) {
            return $this->failure('Disabling Blade requires --force.');
        }
        try {
            $data = $availability->disable('cli')->toArray();
        } catch (\Throwable $exception) {
            report($exception);
            return $this->failure('Blade could not be disabled because security audit is unavailable.');
        }
        $this->output($data);
        return self::SUCCESS;
    }

    private function failure(string $message): int
    {
        $this->option('json') ? $this->line(json_encode(['error' => $message], JSON_THROW_ON_ERROR)) : $this->error($message);
        return self::FAILURE;
    }

    /** @param array<string, mixed> $data */
    private function output(array $data): void
    {
        $this->option('json') ? $this->line(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) : $this->info('WNCMS Blade UI disabled.');
    }
}
