<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Wncms\Services\Security\BladeAvailabilityService;

final class BladeEnable extends Command
{
    protected $signature = 'wncms:blade:enable {--json}';
    protected $description = 'Enable WNCMS Blade UI surfaces using the emergency recovery path';

    public function handle(BladeAvailabilityService $availability): int
    {
        try {
            $data = $availability->enable('cli')->toArray();
        } catch (\Throwable $exception) {
            report($exception);
            $message = 'Blade could not be enabled.';
            $this->option('json') ? $this->line(json_encode(['error' => $message], JSON_THROW_ON_ERROR)) : $this->error($message);
            return self::FAILURE;
        }
        $this->option('json') ? $this->line(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) : $this->info('WNCMS Blade UI enabled.');
        return self::SUCCESS;
    }
}
