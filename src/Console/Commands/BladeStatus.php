<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Wncms\Services\Security\BladeAvailabilityService;

final class BladeStatus extends Command
{
    protected $signature = 'wncms:blade:status {--json}';
    protected $description = 'Show WNCMS Blade availability state';

    public function handle(BladeAvailabilityService $availability): int
    {
        $data = $availability->state()->toArray();
        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['status', 'enabled', 'installed', 'warnings'], [[
                $data['status'], $data['enabled'] ? 'yes' : 'no', $data['installed'] ? 'yes' : 'no', implode(',', $data['warnings']),
            ]]);
        }

        return self::SUCCESS;
    }
}
