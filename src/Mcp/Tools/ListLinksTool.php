<?php

namespace Wncms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Wncms\Services\Automation\AutomationResult;
use Wncms\Services\Automation\LinkAutomationService;

#[Name('wncms-links-list')]
#[Description('List WNCMS Links within a selected website. This tool never writes data.')]
#[IsReadOnly(true)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
#[IsIdempotent(true)]
class ListLinksTool extends Tool
{
    /**
     * Define the Link list input schema.
     *
     * @param  \Illuminate\Contracts\JsonSchema\JsonSchema  $schema
     * @return array
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(['active', 'inactive', 'all'])
                ->description('Filter by Link status.')
                ->default('active'),
            'keyword' => $schema->string()
                ->description('Filter by Link name keyword.'),
            'website_id' => $schema->integer()
                ->min(1)
                ->description('Required website ID scope for this trusted local read.')
                ->required(),
            'page' => $schema->integer()
                ->min(1)
                ->description('Result page number.')
                ->default(1),
            'per_page' => $schema->integer()
                ->min(1)
                ->max(100)
                ->description('Results per page.')
                ->default(20),
            'sort' => $schema->string()
                ->enum(['id', 'sort', 'name', 'clicks', 'created_at', 'updated_at'])
                ->description('Allowed Link sort column.')
                ->default('id'),
            'direction' => $schema->string()
                ->enum(['asc', 'desc'])
                ->description('Sort direction.')
                ->default('desc'),
        ];
    }

    /**
     * List website-scoped Links through the shared automation service.
     *
     * @param  \Laravel\Mcp\Request  $request
     * @return \Laravel\Mcp\ResponseFactory
     */
    public function handle(Request $request): ResponseFactory
    {
        $validator = Validator::make($request->all(), [
            'status' => ['nullable', Rule::in(['active', 'inactive', 'all'])],
            'keyword' => ['nullable', 'string'],
            'website_id' => ['required', 'integer', 'min:1', Rule::exists(wncms()->getModelClass('website'), 'id')],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'sort' => ['nullable', Rule::in(['id', 'sort', 'name', 'clicks', 'created_at', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        if ($validator->fails()) {
            return Response::structured(AutomationResult::fail(
                'Links list validation failed.',
                null,
                $this->resultMeta($request),
                $validator->errors()->toArray(),
                422
            ));
        }

        $options = array_merge([
            'status' => 'active',
            'page' => 1,
            'per_page' => 20,
            'sort' => 'id',
            'direction' => 'desc',
        ], $validator->validated());
        $websiteId = (int) $options['website_id'];
        $data = app(LinkAutomationService::class)->list($options);

        return Response::structured(AutomationResult::success(
            'Links listed.',
            $data,
            $this->resultMeta($request, $options, $websiteId)
        ));
    }

    /**
     * Build stable MCP list metadata.
     *
     * @param  \Laravel\Mcp\Request  $request
     * @param  array  $options
     * @param  int|null  $websiteId
     * @return array
     */
    protected function resultMeta(Request $request, array $options = [], ?int $websiteId = null): array
    {
        $status = $options['status'] ?? $request->get('status', 'active');
        $sort = $options['sort'] ?? $request->get('sort', 'id');
        $direction = $options['direction'] ?? $request->get('direction', 'desc');

        return [
            'surface' => 'mcp',
            'tool' => 'wncms-links-list',
            'domain' => 'links',
            'action' => 'list',
            'website_id' => $websiteId ?? $this->normalizeWebsiteId($request->get('website_id')),
            'status' => $this->normalizeChoice($status, ['active', 'inactive', 'all'], 'active'),
            'sort' => $this->normalizeChoice($sort, ['id', 'sort', 'name', 'clicks', 'created_at', 'updated_at'], 'id'),
            'direction' => $this->normalizeChoice($direction, ['asc', 'desc'], 'desc'),
        ];
    }

    /**
     * Normalize an untrusted metadata choice to an allowed string.
     *
     * @param  mixed  $value
     * @param  array  $allowed
     * @param  string  $default
     * @return string
     */
    protected function normalizeChoice(mixed $value, array $allowed, string $default): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Normalize an untrusted website ID for failure metadata.
     *
     * @param  mixed  $value
     * @return int|null
     */
    protected function normalizeWebsiteId(mixed $value): ?int
    {
        if (! is_int($value) && ! is_string($value)) {
            return null;
        }

        $websiteId = filter_var($value, FILTER_VALIDATE_INT);

        return $websiteId !== false && $websiteId >= 1 ? $websiteId : null;
    }
}
