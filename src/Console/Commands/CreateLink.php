<?php

namespace Wncms\Console\Commands;

use Wncms\Services\Automation\LinkAutomationService;

class CreateLink extends AutomationCommand
{
    protected $signature = 'wncms:links:create
        {--name= : Link name}
        {--url= : Link URL}
        {--status=active : Link status}
        {--slug= : Link slug}
        {--tracking-code= : Tracking code}
        {--website= : Website ID scope}
        {--actor-user= : Actor user ID for guarded write mode}
        {--dry-run : Preview only even when --force is set}
        {--force : Create the link when guard and audit checks pass}
        {--description= : Link description}
        {--slogan= : Link slogan}
        {--external-thumbnail= : External thumbnail URL}
        {--remark= : Internal remark}
        {--sort= : Sort value}
        {--color= : Color value}
        {--background= : Background value}
        {--is-pinned : Mark as pinned}
        {--is-recommended : Mark as recommended}
        {--expired-at= : Expiration datetime}
        {--hit-at= : Hit datetime}
        {--clicks= : Initial click count}
        {--contact= : Contact value}
        {--link-categories= : Comma-separated link categories}
        {--link-tags= : Comma-separated link tags}
        {--json : Output result data as JSON}';

    protected $description = 'Create links through guarded automation workflows.';

    /**
     * Execute the command.
     *
     * @return int
     */
    public function handle(): int
    {
        $result = app(LinkAutomationService::class)->create($this->inputData(), [
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
     * Collect mutation input from command options.
     *
     * @return array
     */
    protected function inputData(): array
    {
        $data = [];

        foreach ($this->optionMap() as $option => $field) {
            $value = $this->option($option);
            if ($this->hasValue($value)) {
                $data[$field] = $value;
            }
        }

        if ((bool) $this->option('is-pinned')) {
            $data['is_pinned'] = true;
        }

        if ((bool) $this->option('is-recommended')) {
            $data['is_recommended'] = true;
        }

        return $data;
    }

    /**
     * Map CLI option names to Link mutation fields.
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
            'expired-at' => 'expired_at',
            'hit-at' => 'hit_at',
            'clicks' => 'clicks',
            'contact' => 'contact',
            'link-categories' => 'link_categories',
            'link-tags' => 'link_tags',
        ];
    }

    /**
     * Render the human-readable success summary.
     *
     * @param array $result
     * @return void
     */
    protected function renderSuccessSummary(array $result): void
    {
        $this->line((string) ($result['message'] ?? 'Link create completed.'));

        if ((int) ($result['code'] ?? 200) === 202) {
            $this->line('Dry-run only. Use --force with --actor-user=ID to write after guard checks pass.');
        }

        $this->renderSummary((array) ($result['data'] ?? []));
    }

    /**
     * Render a compact success summary.
     *
     * @param array $data
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

    /**
     * Check whether an option value should be applied.
     *
     * @param mixed $value
     * @return bool
     */
    protected function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }
}
