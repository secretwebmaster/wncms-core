<?php

namespace Wncms\Console\Commands;

use Wncms\Services\Automation\LinkAutomationService;

class UpdateLink extends AutomationCommand
{
    protected $signature = 'wncms:links:update
        {identifier : Link ID or slug}
        {--status= : Link status}
        {--tracking-code= : Tracking code}
        {--slug= : Link slug}
        {--name= : Link name}
        {--url= : Link URL}
        {--slogan= : Link slogan}
        {--description= : Link description}
        {--external-thumbnail= : External thumbnail URL}
        {--remark= : Internal remark}
        {--sort= : Sort value}
        {--color= : Color value}
        {--background= : Background value}
        {--is-pinned= : Mark as pinned (true or false)}
        {--is-recommended= : Mark as recommended (true or false)}
        {--expired-at= : Expiration datetime}
        {--hit-at= : Hit datetime}
        {--clicks= : Click count}
        {--contact= : Contact value}
        {--website= : Website ID lookup scope}
        {--actor-user= : Actor user ID for guarded write mode}
        {--dry-run : Preview only even when --force is set}
        {--force : Update the link when guard and audit checks pass}
        {--json : Output result data as JSON}';

    protected $description = 'Update links through guarded automation workflows.';

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle(): int
    {
        $result = app(LinkAutomationService::class)->update((string) $this->argument('identifier'), $this->inputData(), [
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
     * Collect patch input from command options.
     *
     * @return array
     */
    protected function inputData(): array
    {
        $data = [];

        foreach ($this->optionMap() as $option => $field) {
            if ($this->input->hasParameterOption('--' . $option)) {
                $data[$field] = $this->option($option);
            }
        }

        return $data;
    }

    /**
     * Map CLI option names to Link patch fields.
     *
     * @return array
     */
    protected function optionMap(): array
    {
        return [
            'status' => 'status',
            'tracking-code' => 'tracking_code',
            'slug' => 'slug',
            'name' => 'name',
            'url' => 'url',
            'slogan' => 'slogan',
            'description' => 'description',
            'external-thumbnail' => 'external_thumbnail',
            'remark' => 'remark',
            'sort' => 'sort',
            'color' => 'color',
            'background' => 'background',
            'is-pinned' => 'is_pinned',
            'is-recommended' => 'is_recommended',
            'expired-at' => 'expired_at',
            'hit-at' => 'hit_at',
            'clicks' => 'clicks',
            'contact' => 'contact',
        ];
    }

    /**
     * Render the human-readable success summary.
     *
     * @param  array  $result
     * @return void
     */
    protected function renderSuccessSummary(array $result): void
    {
        $this->line((string) ($result['message'] ?? 'Link update completed.'));
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
        $item = (array) ($data['item'] ?? []);
        if (!empty($item)) {
            $this->renderLinkItemSummary($item);

            return;
        }

        $plan = (array) ($data['plan'] ?? []);
        if (empty($plan)) {
            return;
        }

        $this->renderMutationPlanSummary($plan);
    }

}
