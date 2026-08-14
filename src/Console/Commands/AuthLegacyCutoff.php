<?php

namespace Wncms\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Wncms\Services\Security\SecurityEventService;

class AuthLegacyCutoff extends Command
{
    protected $signature = 'wncms:auth:legacy-cutoff {datetime} {--override-max} {--force} {--json}';
    protected $description = 'Set the UTC cutoff for legacy personal-token acceptance';

    public function handle(SecurityEventService $events): int
    {
        $raw = trim((string) $this->argument('datetime'));
        if (preg_match('/(?:Z|[+-][0-9]{2}:[0-9]{2})$/D', $raw) !== 1) {
            return $this->failCommand('Datetime must include an explicit timezone.');
        }
        try {
            $cutoff = CarbonImmutable::parse($raw)->utc();
        } catch (\Throwable) {
            return $this->failCommand('Datetime is invalid.');
        }
        if ($cutoff->isAfter(CarbonImmutable::now('UTC')->addDays(365))
            && (! $this->option('override-max') || ! $this->option('force'))) {
            return $this->failCommand('A cutoff beyond 365 days requires --override-max and --force.');
        }

        $events->withinTransaction(function () use ($cutoff): void {
            uss('api_legacy_personal_tokens_cutoff_at', $cutoff->toIso8601String());
        }, [
            'type' => 'auth.legacy.cutoff_changed', 'severity' => 'warning', 'outcome' => 'succeeded',
            'context' => ['surface' => 'cli', 'context' => ['reason' => 'legacy_cutoff_changed']],
        ], null, $events->modelConnectionNames(['setting']));

        $data = ['cutoff_at' => $cutoff->toIso8601String()];
        $this->option('json') ? $this->line(json_encode($data, JSON_THROW_ON_ERROR)) : $this->info('Legacy cutoff updated: '.$data['cutoff_at']);

        return self::SUCCESS;
    }

    private function failCommand(string $message): int
    {
        $this->option('json') ? $this->line(json_encode(['error' => $message], JSON_THROW_ON_ERROR)) : $this->error($message);
        return self::FAILURE;
    }
}
