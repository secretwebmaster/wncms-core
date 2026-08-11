<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Wncms\Models\Link;
use Wncms\Models\MutationAudit;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Services\Automation\BackendMutationAuditService;
use Wncms\Services\Automation\MutationAuditService;
use Wncms\Tests\TestCase;

class LinkBackendMutationAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $actor;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        config([
            'media-library.media_model' => \Spatie\MediaLibrary\MediaCollections\Models\Media::class,
            'wncms.models.link.website_mode' => 'multi',
        ]);
        uss('multi_website', 1);
        $this->actor = User::firstOrFail();
        $this->website = Website::firstOrFail();
        $this->actingAs($this->actor);
    }

    public function test_disabled_single_mutations_skip_audit_queries_and_rows(): void
    {
        config(['wncms.mutation_audit.enabled' => false]);
        $beforeAuditCount = MutationAudit::count();
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $store = $this->post(route('links.store'), $this->linkData([
            'website_ids' => [$this->website->id],
        ]));
        $link = Link::where('slug', $this->lastSlug)->firstOrFail();
        $store->assertRedirect(route('links.edit', ['id' => $link->id]));

        $update = $this->patch(route('links.update', $link->id), $this->linkData([
            'slug' => $link->slug,
            'name' => 'Disabled audit update',
            'website_ids' => [$this->website->id],
        ]));
        $update->assertRedirect(route('links.edit', ['id' => $link->id]));

        $this->delete(route('links.destroy', $link->id))->assertRedirect();

        $this->assertFalse(Link::whereKey($link->id)->exists());
        $this->assertFalse(collect($queries)->contains(
            fn (string $sql): bool => str_contains(strtolower($sql), 'mutation_audits')
        ));
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_enabled_store_audits_final_website_tag_and_media_state(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $beforeAuditCount = MutationAudit::count();
        $payload = $this->linkData([
            'website_ids' => [$this->website->id],
            'link_categories' => json_encode([['value' => 'Audit category']]),
            'link_thumbnail' => UploadedFile::fake()->create('audit-thumbnail.jpg', 4, 'image/jpeg'),
        ]);

        $response = $this->post(route('links.store'), $payload);
        $link = Link::where('slug', $this->lastSlug)->firstOrFail();
        $audit = MutationAudit::latest('id')->firstOrFail();

        $response->assertRedirect(route('links.edit', ['id' => $link->id]));
        $this->assertSame($beforeAuditCount + 1, MutationAudit::count());
        $this->assertSame('ui', $audit->surface);
        $this->assertSame('create', $audit->action);
        $this->assertSame('link_create', $audit->permission);
        $this->assertSame($this->actor->id, $audit->actor_id);
        $this->assertSame([$this->website->id], $audit->website_ids);
        $this->assertSame(['Audit category'], $audit->input_summary['changes']['after']['relationships']['link_categories']);
        $this->assertCount(1, $audit->input_summary['changes']['after']['relationships']['media']['link_thumbnail']);
    }

    public function test_enabled_update_audits_actual_changes_and_skips_no_op(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $link = Link::create($this->linkData());
        $link->bindWebsites([$this->website->id]);
        $beforeAuditCount = MutationAudit::count();
        $payload = $this->linkData([
            'slug' => $link->slug,
            'name' => 'Audited update',
            'website_ids' => [$this->website->id],
        ]);

        $this->patch(route('links.update', $link->id), $payload)
            ->assertRedirect(route('links.edit', ['id' => $link->id]));
        $audit = MutationAudit::latest('id')->firstOrFail();

        $this->assertSame($beforeAuditCount + 1, MutationAudit::count());
        $this->assertSame('update', $audit->action);
        $this->assertSame('link_edit', $audit->permission);
        $this->assertSame('Audited update', $audit->input_summary['changes']['after']['attributes']['name']);

        $this->patch(route('links.update', $link->id), $payload)
            ->assertRedirect(route('links.edit', ['id' => $link->id]));
        $this->assertSame($beforeAuditCount + 1, MutationAudit::count());
    }

    public function test_enabled_destroy_audits_pre_delete_snapshot(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $link = Link::create($this->linkData(['name' => 'Delete audit target']));
        $link->bindWebsites([$this->website->id]);
        $beforeAuditCount = MutationAudit::count();

        $this->delete(route('links.destroy', $link->id))->assertRedirect();
        $audit = MutationAudit::latest('id')->firstOrFail();

        $this->assertFalse(Link::whereKey($link->id)->exists());
        $this->assertSame($beforeAuditCount + 1, MutationAudit::count());
        $this->assertSame('delete', $audit->action);
        $this->assertSame('link_delete', $audit->permission);
        $this->assertSame('Delete audit target', $audit->input_summary['changes']['before']['attributes']['name']);
    }

    public function test_enabled_update_rolls_back_when_audit_persistence_fails(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $link = Link::create($this->linkData(['name' => 'Before rollback']));
        $mock = Mockery::mock(BackendMutationAuditService::class, [app(MutationAuditService::class)])
            ->makePartial();
        $mock->shouldReceive('write')->once()->andThrow(new RuntimeException('audit failed'));
        $this->app->instance(BackendMutationAuditService::class, $mock);

        $this->withoutExceptionHandling();

        try {
            $this->patch(route('links.update', $link->id), $this->linkData([
                'slug' => $link->slug,
                'name' => 'After rollback',
                'website_ids' => [$this->website->id],
            ]));
            $this->fail('Expected audit persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit failed', $exception->getMessage());
        }

        $this->assertSame('Before rollback', $link->fresh()->name);
    }

    protected string $lastSlug = '';

    /**
     * Build valid Link request attributes with unique identifiers.
     *
     * @param  array  $overrides
     * @return array
     */
    protected function linkData(array $overrides = []): array
    {
        $this->lastSlug = (string) ($overrides['slug'] ?? ('backend-ui-audit-' . uniqid()));

        return array_merge([
            'status' => 'active',
            'tracking_code' => 'backend-ui-audit-code-' . uniqid(),
            'slug' => $this->lastSlug,
            'name' => 'Backend UI audit link',
            'url' => 'https://example.com/backend-ui-audit',
            'slogan' => 'Audit slogan',
            'description' => 'Audit description',
            'external_thumbnail' => 'https://example.com/audit-thumbnail.jpg',
            'remark' => 'Audit remark',
            'sort' => 10,
            'color' => '#ffffff',
            'background' => '#000000',
            'is_pinned' => 0,
            'is_recommended' => 0,
            'expired_at' => null,
            'hit_at' => null,
            'clicks' => 0,
            'contact' => '@audit',
        ], $overrides);
    }
}
