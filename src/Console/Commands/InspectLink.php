<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Wncms\Services\Automation\AutomationResult;
use Wncms\Services\Automation\LinkAutomationService;

class InspectLink extends Command
{
    protected $signature = 'wncms:links:inspect
        {identifier : Link ID or slug}
        {--website= : Website ID scope}
        {--json : Output result data as JSON}';

    protected $description = 'Inspect one link for read-only automation workflows.';

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle(): int
    {
        $identifier = (string) $this->argument('identifier');
        $service = app(LinkAutomationService::class);
        $item = $service->inspect($identifier, [
            'website_id' => $this->option('website'),
        ]);

        if (!$item) {
            $result = AutomationResult::fail('Link not found.', null, $this->resultMeta('inspect'), [
                'identifier' => [$identifier],
            ], 404);

            return $this->outputResult($result, true);
        }

        $result = AutomationResult::success('Link inspected.', [
            'item' => $item,
        ], $this->resultMeta('inspect'));

        return $this->outputResult($result, false);
    }

    /**
     * Output a command result.
     *
     * @param array $result
     * @param bool $isError
     * @return int
     */
    protected function outputResult(array $result, bool $isError): int
    {
        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $isError ? self::FAILURE : self::SUCCESS;
        }

        if ($isError) {
            $this->error((string) $result['message']);
            return self::FAILURE;
        }

        $this->renderTable((array) ($result['data']['item'] ?? []));

        return self::SUCCESS;
    }

    /**
     * Render one link as a key-value table.
     *
     * @param array $item
     * @return void
     */
    protected function renderTable(array $item): void
    {
        $rows = [];

        foreach ($item as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            }

            $rows[] = [
                (string) $key,
                (string) $value,
            ];
        }

        $this->line('WNCMS Link');
        $this->newLine();
        $this->table(['Field', 'Value'], $rows);
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
        ];
    }
}
