<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Wncms\Services\Automation\LinkAutomationService;

class DeleteLink extends Command
{
    protected $signature = 'wncms:links:delete
        {identifier : Link ID or slug}
        {--website= : Website ID scope}
        {--actor-user= : Actor user ID for guarded write mode}
        {--dry-run : Preview only even when --force is set}
        {--force : Delete the link when guard and audit checks pass}
        {--json : Output result data as JSON}';

    protected $description = 'Delete links through guarded automation workflows.';

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle(): int
    {
        $result = app(LinkAutomationService::class)->delete((string) $this->argument('identifier'), [
            'surface' => 'cli',
            'command' => (string) $this->getName(),
            'website_id' => $this->option('website'),
            'actor_user_id' => $this->option('actor-user'),
            'dry_run' => (bool) $this->option('dry-run'),
            'force' => (bool) $this->option('force'),
        ]);

        return $this->outputResult($result);
    }

    /**
     * Output a command result.
     *
     * @param  array  $result
     * @return int
     */
    protected function outputResult(array $result): int
    {
        $isError = ($result['status'] ?? 'fail') !== 'success';

        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $isError ? self::FAILURE : self::SUCCESS;
        }

        if ($isError) {
            $this->error((string) ($result['message'] ?? 'Link delete failed.'));
            $this->renderErrors((array) ($result['errors'] ?? []));

            return self::FAILURE;
        }

        $this->line((string) ($result['message'] ?? 'Link delete completed.'));
        $this->renderSummary((array) ($result['data'] ?? []));

        return self::SUCCESS;
    }

    /**
     * Render a compact success summary.
     *
     * @param  array  $data
     * @return void
     */
    protected function renderSummary(array $data): void
    {
        $deleted = (array) ($data['deleted'] ?? []);
        if (! empty($deleted)) {
            $this->table(['Field', 'Value'], [
                ['id', (string) ($deleted['id'] ?? '')],
                ['slug', (string) ($deleted['slug'] ?? '')],
                ['name', (string) ($deleted['name'] ?? '')],
            ]);

            return;
        }

        $plan = (array) ($data['plan'] ?? []);
        if (empty($plan)) {
            return;
        }

        $this->table(['Field', 'Value'], [
            ['operation', (string) ($plan['operation'] ?? '')],
            ['validation', (string) ($plan['validation']['status'] ?? '')],
            ['guard', (string) ($plan['guard']['status'] ?? '')],
            ['will_write', ! empty($plan['will_write']) ? 'yes' : 'no'],
            ['audit_table', (string) ($plan['audit']['table'] ?? '')],
        ]);
    }

    /**
     * Render command errors as a small table.
     *
     * @param  array  $errors
     * @return void
     */
    protected function renderErrors(array $errors): void
    {
        if (empty($errors)) {
            return;
        }

        $rows = [];
        foreach ($errors as $field => $messages) {
            $rows[] = [
                (string) $field,
                is_array($messages) ? implode(', ', array_map('strval', $messages)) : (string) $messages,
            ];
        }

        $this->table(['Field', 'Errors'], $rows);
    }
}
