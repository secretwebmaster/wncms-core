<?php

namespace Wncms\Console\Commands;

use Wncms\Services\Automation\AutomationResult;
use Wncms\Services\Automation\LinkAutomationService;

class ListLinks extends AutomationCommand
{
    protected $signature = 'wncms:links:list
        {--status=active : Filter by status, or all for every status}
        {--keyword= : Filter by link name keyword}
        {--website= : Website ID scope}
        {--page=1 : Page number}
        {--per-page=20 : Items per page}
        {--sort=id : Sort column}
        {--direction=desc : Sort direction}
        {--json : Output result data as JSON}';

    protected $description = 'List links for read-only automation workflows.';

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle(): int
    {
        $service = app(LinkAutomationService::class);
        $data = $service->list([
            'status' => $this->option('status'),
            'keyword' => $this->option('keyword'),
            'website_id' => $this->option('website'),
            'page' => $this->option('page'),
            'per_page' => $this->option('per-page'),
            'sort' => $this->option('sort'),
            'direction' => $this->option('direction'),
        ]);

        $result = AutomationResult::success('Links listed.', $data, $this->resultMeta('list'));

        return $this->outputAutomationResult($result, function (array $result): void {
            $this->renderTable((array) ($result['data'] ?? []));
        });
    }

    /**
     * Render link list data as a table.
     *
     * @param array $data
     * @return void
     */
    protected function renderTable(array $data): void
    {
        $rows = [];

        foreach ((array) ($data['items'] ?? []) as $item) {
            $rows[] = [
                (string) ($item['id'] ?? ''),
                (string) ($item['status'] ?? ''),
                (string) ($item['slug'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['url'] ?? ''),
            ];
        }

        $this->line('WNCMS Links');
        $pagination = (array) ($data['pagination'] ?? []);
        $this->line('Page ' . ($pagination['page'] ?? 1) . ' / ' . ($pagination['last_page'] ?? 1) . ', total: ' . ($pagination['total'] ?? count($rows)));
        $this->newLine();

        if (empty($rows)) {
            $this->line('No links matched the selected filters.');
            return;
        }

        $this->table(['ID', 'Status', 'Slug', 'Name', 'URL'], $rows);
    }

    /**
     * Build consistent CLI automation metadata.
     *
     * @param string $action
     * @return array
     */
    protected function resultMeta(string $action): array
    {
        return [
            'surface' => 'cli',
            'command' => (string) $this->getName(),
            'domain' => 'links',
            'action' => $action,
            'website_id' => $this->option('website') === null ? null : (int) $this->option('website'),
            'status' => (string) $this->option('status'),
            'sort' => (string) $this->option('sort'),
            'direction' => (string) $this->option('direction'),
        ];
    }
}
