<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;

abstract class AutomationCommand extends Command
{
    /**
     * Output an automation command result.
     *
     * @param  array  $result
     * @param  callable|null  $humanSuccessRenderer
     * @return int
     */
    protected function outputAutomationResult(array $result, ?callable $humanSuccessRenderer = null): int
    {
        $isSuccess = ($result['status'] ?? 'fail') === 'success';

        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $isSuccess ? self::SUCCESS : self::FAILURE;
        }

        if (! $isSuccess) {
            $this->error((string) ($result['message'] ?? 'Automation command failed.'));
            $this->renderValidationErrors((array) ($result['errors'] ?? []));

            return self::FAILURE;
        }

        if ($humanSuccessRenderer !== null) {
            $humanSuccessRenderer($result);
        }

        return self::SUCCESS;
    }

    /**
     * Render validation errors as a table.
     *
     * @param  array  $errors
     * @return void
     */
    protected function renderValidationErrors(array $errors): void
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

    /**
     * Render a Link item summary as a table.
     *
     * @param  array  $item
     * @return void
     */
    protected function renderLinkItemSummary(array $item): void
    {
        $this->table(['Field', 'Value'], [
            ['id', (string) ($item['id'] ?? '')],
            ['status', (string) ($item['status'] ?? '')],
            ['slug', (string) ($item['slug'] ?? '')],
            ['name', (string) ($item['name'] ?? '')],
            ['url', (string) ($item['url'] ?? '')],
        ]);
    }

    /**
     * Render a mutation plan summary as a table.
     *
     * @param  array  $plan
     * @return void
     */
    protected function renderMutationPlanSummary(array $plan): void
    {
        $this->table(['Field', 'Value'], [
            ['operation', (string) ($plan['operation'] ?? '')],
            ['validation', (string) ($plan['validation']['status'] ?? '')],
            ['guard', (string) ($plan['guard']['status'] ?? '')],
            ['will_write', ! empty($plan['will_write']) ? 'yes' : 'no'],
            ['audit_table', (string) ($plan['audit']['table'] ?? '')],
        ]);
    }
}
