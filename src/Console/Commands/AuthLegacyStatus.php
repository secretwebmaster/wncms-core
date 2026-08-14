<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\LegacyPersonalTokenAuthenticator;

class AuthLegacyStatus extends Command
{
    protected $signature = 'wncms:auth:legacy-status {--json}';
    protected $description = 'Show bounded legacy personal-token compatibility status';

    public function handle(LegacyPersonalTokenAuthenticator $tokens): int
    {
        $config = AuthSecurityConfig::fromRuntime();
        $schema = $tokens->schemaStatus();
        $data = [
            'enabled' => $config->legacyPersonalTokensEnabled(),
            'cutoff_at' => $config->legacyPersonalTokensCutoffAt(),
            'schema' => $schema,
            'token_count' => $schema['compatible'] ? DB::table('personal_access_tokens')->count() : null,
        ];
        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['enabled', 'cutoff_at', 'schema', 'token_count'], [[
                $data['enabled'] ? 'yes' : 'no', $data['cutoff_at'] ?? 'none', $schema['compatible'] ? 'compatible' : 'incompatible', $data['token_count'] ?? 'unknown',
            ]]);
        }

        return self::SUCCESS;
    }
}
