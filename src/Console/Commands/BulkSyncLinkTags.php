<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Wncms\Services\Automation\AutomationResult;
use Wncms\Services\Automation\LinkAutomationService;

class BulkSyncLinkTags extends Command
{
    protected $signature = 'wncms:links:bulk-sync-tags
        {--identifiers= : JSON array of Link IDs or slugs}
        {--action=sync : Tag action: sync, attach, or detach}
        {--categories= : JSON array of Link category names}
        {--tags= : JSON array of Link tag names}
        {--website= : Website ID lookup scope}
        {--actor-user= : Actor user ID for guarded write mode}
        {--dry-run : Preview only even when --force is set}
        {--force : Synchronize tags when guard and audit checks pass}
        {--json : Output result data as JSON}';

    protected $description = 'Atomically synchronize Link categories and tags through guarded automation workflows.';

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle(): int
    {
        $identifiers = $this->decodeListOption('identifiers');
        $categories = $this->decodeListOption('categories');
        $tagNames = $this->decodeListOption('tags');
        $options = [
            'surface' => 'cli',
            'command' => (string) $this->getName(),
            'website_id' => $this->option('website'),
            'actor_user_id' => $this->option('actor-user'),
            'dry_run' => (bool) $this->option('dry-run'),
            'force' => (bool) $this->option('force'),
        ];

        if ($identifiers === null || $categories === null || $tagNames === null) {
            return $this->outputResult(AutomationResult::fail('Link bulk tag synchronization validation failed.', null, [
                'surface' => 'cli',
                'command' => (string) $this->getName(),
                'domain' => 'links',
                'action' => 'bulk_sync_tags',
                'dry_run' => true,
                'force' => (bool) $this->option('force'),
            ], [
                'input' => ['invalid_json'],
            ], 422));
        }

        $tags = [
            'link_categories' => $categories,
            'link_tags' => $tagNames,
        ];

        return $this->outputResult(app(LinkAutomationService::class)->bulkSyncTags($identifiers, (string) $this->option('action'), $tags, $options));
    }

    /**
     * Decode one optional JSON list option.
     *
     * @param  string  $option
     * @return array|null
     */
    protected function decodeListOption(string $option): ?array
    {
        $json = $this->option($option);
        if ($json === null || $json === '') {
            return [];
        }

        if (!is_string($json)) {
            return null;
        }

        $decoded = json_decode($json);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) && array_is_list($decoded) ? $decoded : null;
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
            $this->error((string) ($result['message'] ?? 'Link bulk tag synchronization failed.'));
            $this->renderErrors((array) ($result['errors'] ?? []));

            return self::FAILURE;
        }

        $this->line((string) ($result['message'] ?? 'Link bulk tag synchronization completed.'));
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
