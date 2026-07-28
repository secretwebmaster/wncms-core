<?php

namespace Wncms\Console\Commands;

use Wncms\Services\Automation\LinkAutomationService;

class DeleteLink extends AutomationCommand
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

        return $this->outputAutomationResult($result, function (array $result): void {
            $this->renderSuccessSummary($result);
        });
    }

    /**
     * Render the human-readable success summary.
     *
     * @param  array  $result
     * @return void
     */
    protected function renderSuccessSummary(array $result): void
    {
        $this->line((string) ($result['message'] ?? 'Link delete completed.'));
        $this->renderSummary((array) ($result['data'] ?? []));
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

        $this->renderMutationPlanSummary($plan);
    }
}
