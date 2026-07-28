<?php

namespace Wncms\Mcp\Tools;

use Closure;
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

#[Name('wncms-links-inspect')]
#[Description('Inspect one WNCMS Link by ID or slug within a selected website. This tool never writes data.')]
#[IsReadOnly(true)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
#[IsIdempotent(true)]
class InspectLinkTool extends Tool
{
    /**
     * Define the Link inspection input schema.
     *
     * @param  \Illuminate\Contracts\JsonSchema\JsonSchema  $schema
     * @return array
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'identifier' => $schema->union(['string', 'integer'])
                ->description('Required Link ID or slug.')
                ->required(),
            'website_id' => $schema->integer()
                ->min(1)
                ->description('Required website ID scope for this trusted local read.')
                ->required(),
        ];
    }

    /**
     * Inspect one website-scoped Link through the shared automation service.
     *
     * @param  \Laravel\Mcp\Request  $request
     * @return \Laravel\Mcp\ResponseFactory
     */
    public function handle(Request $request): ResponseFactory
    {
        $validator = Validator::make($request->all(), [
            'identifier' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) && ! is_int($value)) {
                        $fail("The {$attribute} must be a Link ID or slug.");
                    }
                },
            ],
            'website_id' => ['required', 'integer', 'min:1', Rule::exists(wncms()->getModelClass('website'), 'id')],
        ]);

        if ($validator->fails()) {
            return Response::structured(AutomationResult::fail(
                'Link inspect validation failed.',
                null,
                $this->resultMeta($request),
                $validator->errors()->toArray(),
                422
            ));
        }

        $validated = $validator->validated();
        $identifier = (string) $validated['identifier'];
        $websiteId = (int) $validated['website_id'];
        $item = app(LinkAutomationService::class)->inspect($identifier, [
            'website_id' => $websiteId,
        ]);

        $result = $item
            ? AutomationResult::success('Link inspected.', [
                'item' => $item,
            ], $this->resultMeta($request, $websiteId))
            : AutomationResult::fail('Link not found.', null, $this->resultMeta($request, $websiteId), [
                'identifier' => [$identifier],
            ], 404);

        return Response::structured($result);
    }

    /**
     * Build stable MCP inspection metadata.
     *
     * @param  \Laravel\Mcp\Request  $request
     * @param  int|null  $websiteId
     * @return array
     */
    protected function resultMeta(Request $request, ?int $websiteId = null): array
    {
        return [
            'surface' => 'mcp',
            'tool' => 'wncms-links-inspect',
            'domain' => 'links',
            'action' => 'inspect',
            'website_id' => $websiteId ?? ($request->get('website_id') === null ? null : (int) $request->get('website_id')),
        ];
    }
}
