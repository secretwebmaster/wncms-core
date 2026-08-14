<?php

namespace Wncms\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Services\Security\SecurityEventRetentionService;
use Wncms\Services\Security\SecurityEventService;

final class PruneApiSecurityEvents extends Command
{
    protected $signature = 'wncms:auth:prune-security-events {--json}';
    protected $description = 'Prune API security events according to the configured retention period';

    public function handle(SecurityEventRetentionService $retention, SecurityEventService $events): int
    {
        $days = AuthSecurityConfig::fromRuntime()->securityEventRetentionDays();
        $deleted = DB::transaction(function () use ($retention, $events, $days): int {
            $deleted = $retention->prune(CarbonImmutable::now('UTC')->subDays($days), 500);
            $events->record('security.retention.completed', 'info', 'succeeded', [
                'surface' => 'cli',
                'context' => ['reason' => 'retention_completed', 'retention_days' => $days, 'deleted_count' => $deleted],
            ]);

            return $deleted;
        });
        $data = ['retention_days' => $days, 'deleted_count' => $deleted];
        $this->option('json') ? $this->line(json_encode($data, JSON_THROW_ON_ERROR)) : $this->info("Pruned {$deleted} security events older than {$days} days.");

        return self::SUCCESS;
    }
}
