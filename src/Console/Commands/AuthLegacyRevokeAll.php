<?php

namespace Wncms\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Wncms\Services\Security\SecurityEventService;

class AuthLegacyRevokeAll extends Command
{
    protected $signature = 'wncms:auth:legacy-revoke-all {--force} {--json}';
    protected $description = 'Disable WNCMS legacy personal-token acceptance without deleting host rows';

    public function handle(SecurityEventService $events): int
    {
        if (! $this->option('force')) {
            $message = 'The --force option is required.';
            $this->option('json') ? $this->line(json_encode(['error' => $message], JSON_THROW_ON_ERROR)) : $this->error($message);
            return self::FAILURE;
        }
        $now = CarbonImmutable::now('UTC');
        $events->withinTransaction(function () use ($now): void {
            uss('api_legacy_personal_tokens_enabled', false);
            uss('api_legacy_personal_tokens_cutoff_at', $now->toIso8601String());
        }, [
            'type' => 'auth.legacy.disabled', 'severity' => 'critical', 'outcome' => 'succeeded',
            'context' => ['surface' => 'cli', 'context' => ['reason' => 'legacy_acceptance_disabled']],
        ], null, $events->modelConnectionNames(['setting']));

        $data = ['enabled' => false, 'cutoff_at' => $now->toIso8601String(), 'host_rows_deleted' => 0];
        $this->option('json') ? $this->line(json_encode($data, JSON_THROW_ON_ERROR)) : $this->info('Legacy acceptance disabled; host token rows were not modified.');
        return self::SUCCESS;
    }
}
