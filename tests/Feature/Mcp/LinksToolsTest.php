<?php

namespace Wncms\Tests\Feature\Mcp;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Facades\Mcp;
use Symfony\Component\Process\Process;
use Wncms\Mcp\Servers\WncmsServer;
use Wncms\Mcp\Tools\InspectLinkTool;
use Wncms\Mcp\Tools\ListLinksTool;
use Wncms\Models\Link;
use Wncms\Models\MutationAudit;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class LinksToolsTest extends TestCase
{
    use DatabaseTransactions;

    protected Website $website;

    protected Website $otherWebsite;

    /**
     * Prepare website-scoped Links for each MCP contract test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['wncms.models.link.website_mode' => 'multi']);

        $this->website = Website::firstOrFail();
        $this->otherWebsite = Website::query()->whereKeyNot($this->website->id)->first()
            ?: Website::create([
                'domain' => 'links-mcp-'.uniqid().'.test',
                'site_name' => 'Links MCP website',
            ]);
    }

    /**
     * Verify the WNCMS local server is absent without explicit enablement.
     *
     * @return void
     */
    public function test_wncms_local_server_is_disabled_by_default(): void
    {
        $this->assertFalse((bool) config('wncms.mcp.enabled'));
        $this->assertNull(Mcp::getLocalServer('wncms'));
    }

    /**
     * Verify the enabled route registers the named local server without a web transport.
     *
     * @return void
     */
    public function test_wncms_local_server_registers_when_enabled(): void
    {
        config([
            'wncms.mcp.enabled' => true,
            'wncms.mcp.server' => 'wncms',
        ]);

        require __DIR__.'/../../../routes/ai.php';

        $this->assertIsCallable(Mcp::getLocalServer('wncms'));
        $this->assertNull(Mcp::getWebServer('wncms'));
        $this->assertSame(['wncms'], array_keys(Mcp::servers()));
    }

    /**
     * Verify provider boot registers the local handle only when the environment enables it.
     *
     * @return void
     */
    public function test_provider_registers_local_server_only_when_enabled(): void
    {
        $packageRoot = dirname(__DIR__, 3);
        $command = [
            PHP_BINARY,
            $packageRoot . '/vendor/bin/testbench',
            'mcp:start',
            'wncms',
        ];

        $enabled = new Process($command, $packageRoot, [
            'WNCMS_MCP_ENABLED' => 'true',
        ]);
        $enabled->setInput('');
        $enabled->run();

        $this->assertTrue($enabled->isSuccessful(), $enabled->getErrorOutput());

        $disabled = new Process($command, $packageRoot, [
            'WNCMS_MCP_ENABLED' => 'false',
        ]);
        $disabled->setInput('');
        $disabled->run();

        $this->assertFalse($disabled->isSuccessful());
        $this->assertStringContainsString(
            'MCP Server with name [wncms] not found',
            $disabled->getOutput() . $disabled->getErrorOutput()
        );
    }

    /**
     * Verify list filters return the complete structured automation envelope.
     *
     * @return void
     */
    public function test_links_list_tool_returns_a_structured_automation_envelope(): void
    {
        $keyword = 'MCP scoped '.uniqid();
        $active = $this->websiteLink($this->website, [
            'name' => $keyword.' Active',
            'status' => 'active',
        ]);
        $this->websiteLink($this->website, [
            'name' => $keyword.' Inactive',
            'status' => 'inactive',
        ]);
        $this->websiteLink($this->otherWebsite, [
            'name' => $keyword.' Other Website',
            'status' => 'active',
        ]);

        $response = WncmsServer::tool(ListLinksTool::class, [
            'status' => 'active',
            'keyword' => $keyword,
            'website_id' => $this->website->id,
            'page' => 1,
            'per_page' => 10,
            'sort' => 'id',
            'direction' => 'asc',
        ]);

        $response
            ->assertOk()
            ->assertName('wncms-links-list')
            ->assertStructuredContent(function (AssertableJson $json) use ($active): void {
                $json
                    ->where('code', 200)
                    ->where('status', 'success')
                    ->where('message', 'Links listed.')
                    ->where('data.items.0.id', $active->id)
                    ->where('data.pagination.page', 1)
                    ->where('data.pagination.per_page', 10)
                    ->where('data.pagination.total', 1)
                    ->where('meta.surface', 'mcp')
                    ->where('meta.tool', 'wncms-links-list')
                    ->where('meta.domain', 'links')
                    ->where('meta.action', 'list')
                    ->where('meta.website_id', $this->website->id)
                    ->where('meta.status', 'active')
                    ->where('meta.sort', 'id')
                    ->where('meta.direction', 'asc')
                    ->where('errors', [])
                    ->etc();
            });
    }

    /**
     * Verify inspect supports ID and slug and returns a structured not-found envelope.
     *
     * @return void
     */
    public function test_links_inspect_tool_returns_item_or_not_found_envelope(): void
    {
        $link = $this->websiteLink($this->website);

        WncmsServer::tool(InspectLinkTool::class, [
            'identifier' => $link->id,
            'website_id' => $this->website->id,
        ])->assertOk()
            ->assertName('wncms-links-inspect')
            ->assertStructuredContent(function (AssertableJson $json) use ($link): void {
                $json
                    ->where('code', 200)
                    ->where('status', 'success')
                    ->where('message', 'Link inspected.')
                    ->where('data.item.id', $link->id)
                    ->where('meta.surface', 'mcp')
                    ->where('meta.tool', 'wncms-links-inspect')
                    ->where('meta.domain', 'links')
                    ->where('meta.action', 'inspect')
                    ->where('meta.website_id', $this->website->id)
                    ->where('errors', [])
                    ->etc();
            });

        WncmsServer::tool(InspectLinkTool::class, [
            'identifier' => $link->slug,
            'website_id' => $this->website->id,
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('code', 200)
                ->where('data.item.slug', $link->slug)
                ->etc());

        $missingIdentifier = 'missing-mcp-link-'.uniqid();

        WncmsServer::tool(InspectLinkTool::class, [
            'identifier' => $missingIdentifier,
            'website_id' => $this->website->id,
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('code', 404)
                ->where('status', 'fail')
                ->where('message', 'Link not found.')
                ->where('data', null)
                ->where('meta.tool', 'wncms-links-inspect')
                ->where('meta.website_id', $this->website->id)
                ->where('errors.identifier.0', $missingIdentifier)
                ->etc());
    }

    /**
     * Verify annotations, validation, website isolation, and storage remain read-only.
     *
     * @return void
     */
    public function test_links_mcp_tools_are_read_only_and_website_scoped(): void
    {
        $scoped = $this->websiteLink($this->website);
        $crossWebsite = $this->websiteLink($this->otherWebsite);
        $beforeCounts = [
            'links' => Link::count(),
            'website_pivots' => DB::table('model_has_websites')->count(),
            'tag_pivots' => DB::table('taggables')->count(),
            'audits' => MutationAudit::count(),
        ];

        foreach ([app(ListLinksTool::class), app(InspectLinkTool::class)] as $tool) {
            $definition = $tool->toArray();

            $this->assertSame([
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'openWorldHint' => false,
                'idempotentHint' => true,
            ], $definition['annotations']);
            $this->assertContains('website_id', $definition['inputSchema']['required']);
        }

        $this->assertContains('identifier', app(InspectLinkTool::class)->toArray()['inputSchema']['required']);

        WncmsServer::tool(ListLinksTool::class, [
            'website_id' => $this->website->id,
            'status' => 'all',
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('data.pagination.total', 1)
                ->where('data.items.0.id', $scoped->id)
                ->etc());

        WncmsServer::tool(InspectLinkTool::class, [
            'identifier' => $crossWebsite->id,
            'website_id' => $this->website->id,
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('code', 404)
                ->where('status', 'fail')
                ->etc());

        WncmsServer::tool(ListLinksTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('code', 422)
                ->where('status', 'fail')
                ->has('errors.website_id')
                ->etc());

        WncmsServer::tool(ListLinksTool::class, [
            'website_id' => $this->website->id,
            'status' => 'archived',
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('code', 422)
                ->where('status', 'fail')
                ->has('errors.status')
                ->etc());

        $this->assertSame($beforeCounts, [
            'links' => Link::count(),
            'website_pivots' => DB::table('model_has_websites')->count(),
            'tag_pivots' => DB::table('taggables')->count(),
            'audits' => MutationAudit::count(),
        ]);
    }

    /**
     * Create one Link and bind it to the selected website.
     *
     * @param  \Wncms\Models\Website  $website
     * @param  array  $overrides
     * @return \Wncms\Models\Link
     */
    protected function websiteLink(Website $website, array $overrides = []): Link
    {
        $link = Link::create(array_merge([
            'status' => 'active',
            'tracking_code' => 'mcp-code-'.uniqid(),
            'slug' => 'mcp-link-'.uniqid(),
            'name' => 'MCP Link',
            'url' => 'https://example.com/mcp-link',
            'description' => 'MCP description',
            'clicks' => 0,
            'sort' => 10,
        ], $overrides));
        $link->bindWebsites([$website->id]);

        return $link;
    }
}
