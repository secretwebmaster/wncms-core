<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Wncms\Services\Automation\AutomationResult;
use Wncms\Services\Automation\LinkAutomationService;

class BulkUpdateLinks extends Command
{
    protected $signature = 'wncms:links:bulk-update
        {--items= : JSON array of Link update items}
        {--website= : Website ID lookup scope}
        {--actor-user= : Actor user ID for guarded write mode}
        {--dry-run : Preview only even when --force is set}
        {--force : Update links when guard and audit checks pass}
        {--json : Output result data as JSON}';

    protected $description = 'Atomically update Link URL and sort fields through guarded automation workflows.';

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle(): int
    {
        $items = $this->decodeItems();
        $options = [
            'surface' => 'cli',
            'command' => (string) $this->getName(),
            'website_id' => $this->option('website'),
            'actor_user_id' => $this->option('actor-user'),
            'dry_run' => (bool) $this->option('dry-run'),
            'force' => (bool) $this->option('force'),
        ];

        if ($items === null) {
            $result = AutomationResult::fail('Link bulk update validation failed.', null, [
                'surface' => 'cli',
                'command' => (string) $this->getName(),
                'domain' => 'links',
                'action' => 'bulk_update',
                'dry_run' => true,
                'force' => (bool) $this->option('force'),
            ], [
                'items' => ['invalid_json'],
            ], 422);

            return $this->outputResult($result);
        }

        return $this->outputResult(app(LinkAutomationService::class)->bulkUpdate($items, $options));
    }

    /**
     * Decode the items option into a JSON list.
     *
     * @return array|null
     */
    protected function decodeItems(): ?array
    {
        $json = $this->option('items');
        if (!is_string($json) || trim($json) === '') {
            return null;
        }

        $items = json_decode($json, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($items) ? $items : null;
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
            $this->error((string) ($result['message'] ?? 'Link bulk update failed.'));
            $this->renderErrors((array) ($result['errors'] ?? []));

            return self::FAILURE;
        }

        $this->line((string) ($result['message'] ?? 'Link bulk update completed.'));
        $summary = (array) ($result['data']['summary'] ?? $result['data']['plan']['summary'] ?? []);
        if (!empty($summary)) {
            $this->table(['Requested', 'Changed', 'No-op'], [[
                (string) ($summary['requested'] ?? 0),
                (string) ($summary['changed'] ?? 0),
                (string) ($summary['noop'] ?? 0),
            ]]);
        }

        return self::SUCCESS;
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
