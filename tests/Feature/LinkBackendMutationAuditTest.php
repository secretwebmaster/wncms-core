<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
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

    public function test_enabled_create_removes_uploaded_media_when_audit_rolls_back(): void
    {
        Storage::fake('public');
        config([
            'media-library.disk_name' => 'public',
            'wncms.mutation_audit.enabled' => true,
        ]);
        $mock = Mockery::mock(BackendMutationAuditService::class, [app(MutationAuditService::class)])
            ->makePartial();
        $mock->shouldReceive('write')->once()->andThrow(new RuntimeException('audit failed'));
        $this->app->instance(BackendMutationAuditService::class, $mock);
        $payload = $this->linkData([
            'link_thumbnail' => UploadedFile::fake()->create('rollback-create.jpg', 4, 'image/jpeg'),
        ]);
        $slug = $this->lastSlug;
        $this->withoutExceptionHandling();

        try {
            $this->post(route('links.store'), $payload);
            $this->fail('Expected create audit persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit failed', $exception->getMessage());
        }

        $this->assertFalse(Link::where('slug', $slug)->exists());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_enabled_update_preserves_removed_media_when_audit_rolls_back(): void
    {
        Storage::fake('public');
        config([
            'media-library.disk_name' => 'public',
            'wncms.mutation_audit.enabled' => true,
        ]);
        $link = Link::create($this->linkData(['name' => 'Media rollback before']));
        $media = $link->addMedia(UploadedFile::fake()->create('rollback-existing.jpg', 4, 'image/jpeg'))
            ->toMediaCollection('link_thumbnail');
        $path = $media->getPathRelativeToRoot();
        Storage::disk('public')->assertExists($path);
        $mock = Mockery::mock(BackendMutationAuditService::class, [app(MutationAuditService::class)])
            ->makePartial();
        $mock->shouldReceive('write')->once()->andThrow(new RuntimeException('audit failed'));
        $this->app->instance(BackendMutationAuditService::class, $mock);
        $this->withoutExceptionHandling();

        try {
            $this->patch(route('links.update', $link->id), $this->linkData([
                'slug' => $link->slug,
                'name' => 'Media rollback after',
                'link_thumbnail_remove' => 1,
            ]));
            $this->fail('Expected update audit persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit failed', $exception->getMessage());
        }

        $this->assertSame('Media rollback before', $link->fresh()->name);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
        Storage::disk('public')->assertExists($path);
    }

    public function test_enabled_update_cleans_replaced_media_after_audited_commit(): void
    {
        Storage::fake('public');
        config([
            'media-library.disk_name' => 'public',
            'wncms.mutation_audit.enabled' => true,
        ]);
        $link = Link::create($this->linkData(['name' => 'Media replacement before']));
        $oldMedia = $link->addMedia(UploadedFile::fake()->create('replacement-old.jpg', 4, 'image/jpeg'))
            ->toMediaCollection('link_thumbnail');
        $oldPath = $oldMedia->getPathRelativeToRoot();

        $this->patch(route('links.update', $link->id), $this->linkData([
            'slug' => $link->slug,
            'name' => 'Media replacement after',
            'link_thumbnail' => UploadedFile::fake()->create('replacement-new.jpg', 4, 'image/jpeg'),
        ]))->assertRedirect(route('links.edit', ['id' => $link->id]));

        $newMedia = $link->fresh()->getFirstMedia('link_thumbnail');
        $this->assertNotNull($newMedia);
        $this->assertNotSame($oldMedia->id, $newMedia->id);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newMedia->getPathRelativeToRoot());
        $this->assertSame(
            [$newMedia->id],
            MutationAudit::latest('id')->firstOrFail()->input_summary['changes']['after']['relationships']['media']['link_thumbnail']
        );
    }

    public function test_enabled_single_mutations_do_not_audit_cancelled_model_events(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $updateTarget = Link::create($this->linkData(['name' => 'Cancelled update before']));
        $deleteTarget = Link::create($this->linkData(['name' => 'Cancelled delete target']));
        $beforeAuditCount = MutationAudit::count();
        Event::listen('eloquent.updating: ' . Link::class, fn (): bool => false);
        Event::listen('eloquent.deleting: ' . Link::class, fn (): bool => false);
        $this->withoutExceptionHandling();

        try {
            $this->patch(route('links.update', $updateTarget->id), $this->linkData([
                'slug' => $updateTarget->slug,
                'name' => 'Cancelled update after',
            ]));
            $this->fail('Expected cancelled Link update failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Link update was cancelled.', $exception->getMessage());
        }

        try {
            $this->delete(route('links.destroy', $deleteTarget->id));
            $this->fail('Expected cancelled Link delete failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Link delete was cancelled.', $exception->getMessage());
        }

        $this->assertSame('Cancelled update before', $updateTarget->fresh()->name);
        $this->assertTrue(Link::whereKey($deleteTarget->id)->exists());
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_disabled_bulk_mutations_write_without_audits(): void
    {
        config(['wncms.mutation_audit.enabled' => false]);
        $deleteTarget = Link::create($this->linkData());
        $updateTarget = Link::create($this->linkData(['sort' => 10]));
        $tagTarget = Link::create($this->linkData());
        $beforeAuditCount = MutationAudit::count();

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')->postJson(route('links.bulk_delete'), [
            'model_ids' => [$deleteTarget->id],
        ])->assertOk();
        $this->postJson(route('links.bulk_update'), [
            'data' => [['id' => $updateTarget->id, 'sort' => 20]],
        ])->assertOk()->assertJsonPath('data.count', 1);
        $this->postJson(route('links.bulk_sync_tags'), [
            'model_ids' => [$tagTarget->id],
            'formData' => $this->bulkTagFormData('sync', ['Disabled category']),
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertFalse(Link::whereKey($deleteTarget->id)->exists());
        $this->assertSame(20, $updateTarget->fresh()->sort);
        $this->assertSame(['Disabled category'], $this->tagNames($tagTarget->fresh(), 'link_category'));
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_enabled_bulk_delete_audits_each_link_with_shared_run_id(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $first = Link::create($this->linkData());
        $second = Link::create($this->linkData());
        $first->bindWebsites([$this->website->id]);
        $second->bindWebsites([$this->website->id]);
        $beforeAuditId = (int) MutationAudit::max('id');

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')->postJson(route('links.bulk_delete'), [
            'model_ids' => [$first->id, $second->id],
        ])->assertOk();

        $audits = MutationAudit::where('id', '>', $beforeAuditId)->orderBy('id')->get();
        $this->assertCount(2, $audits);
        $this->assertSame(['bulk_delete'], $audits->pluck('action')->unique()->values()->all());
        $this->assertSame(['link_bulk_delete'], $audits->pluck('permission')->unique()->values()->all());
        $this->assertCount(1, $audits->pluck('run_id')->unique());
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $audits->pluck('model_id')->all());
    }

    public function test_enabled_bulk_delete_preserves_query_delete_event_semantics(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $first = Link::create($this->linkData());
        $second = Link::create($this->linkData());
        $deletingEvents = 0;
        Event::listen('eloquent.deleting: ' . Link::class, function () use (&$deletingEvents): void {
            $deletingEvents++;
        });

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')->postJson(route('links.bulk_delete'), [
            'model_ids' => [$first->id, $second->id],
        ])->assertOk();

        $this->assertSame(0, $deletingEvents);
        $this->assertFalse(Link::whereKey($first->id)->exists());
        $this->assertFalse(Link::whereKey($second->id)->exists());
    }

    public function test_enabled_bulk_update_audits_changed_links_only(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $changed = Link::create($this->linkData(['sort' => 10]));
        $unchanged = Link::create($this->linkData(['sort' => 30]));
        $beforeAuditId = (int) MutationAudit::max('id');

        $this->postJson(route('links.bulk_update'), [
            'data' => [
                ['id' => $changed->id, 'sort' => 20],
                ['id' => $unchanged->id, 'sort' => 30],
                ['id' => 99999999, 'sort' => 40],
            ],
        ])->assertOk()->assertJsonPath('data.count', 1);

        $audits = MutationAudit::where('id', '>', $beforeAuditId)->get();
        $this->assertCount(1, $audits);
        $this->assertSame('bulk_update', $audits->first()->action);
        $this->assertSame('link_edit', $audits->first()->permission);
        $this->assertSame($changed->id, $audits->first()->model_id);
        $this->assertSame(20, $changed->fresh()->sort);
    }

    public function test_enabled_bulk_tag_actions_audit_changed_links_with_request_run_ids(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $first = Link::create($this->linkData());
        $second = Link::create($this->linkData());
        $beforeAuditId = (int) MutationAudit::max('id');

        $this->postJson(route('links.bulk_sync_tags'), [
            'model_ids' => [$first->id, $second->id],
            'formData' => $this->bulkTagFormData('sync', ['Alpha']),
        ])->assertOk()->assertJsonPath('status', 'success');
        $syncAudits = MutationAudit::where('id', '>', $beforeAuditId)->orderBy('id')->get();
        $this->assertCount(2, $syncAudits);
        $this->assertCount(1, $syncAudits->pluck('run_id')->unique());

        $this->postJson(route('links.bulk_sync_tags'), [
            'model_ids' => [$first->id],
            'formData' => $this->bulkTagFormData('attach', ['Beta']),
        ])->assertOk()->assertJsonPath('status', 'success');
        $this->postJson(route('links.bulk_sync_tags'), [
            'model_ids' => [$first->id],
            'formData' => $this->bulkTagFormData('detach', ['Alpha']),
        ])->assertOk()->assertJsonPath('status', 'success');

        $audits = MutationAudit::where('id', '>', $beforeAuditId)->orderBy('id')->get();
        $this->assertCount(4, $audits);
        $this->assertSame(['bulk_sync_tags'], $audits->pluck('action')->unique()->values()->all());
        $this->assertSame(['link_edit'], $audits->pluck('permission')->unique()->values()->all());
        $this->assertSame(['Beta'], $this->tagNames($first->fresh(), 'link_category'));
    }

    public function test_invalid_bulk_tag_requests_do_not_write_audits(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $link = Link::create($this->linkData());
        $beforeAuditCount = MutationAudit::count();

        $this->postJson(route('links.bulk_sync_tags'), [
            'formData' => $this->bulkTagFormData('sync', ['Alpha']),
        ])->assertOk()->assertJsonPath('status', 'fail');
        $this->postJson(route('links.bulk_sync_tags'), [
            'model_ids' => [$link->id],
            'formData' => $this->bulkTagFormData('invalid', ['Alpha']),
        ])->assertOk()->assertJsonPath('status', 'fail');

        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_enabled_bulk_update_rolls_back_entire_batch_when_second_audit_fails(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $first = Link::create($this->linkData(['sort' => 10]));
        $second = Link::create($this->linkData(['sort' => 20]));
        $beforeAuditCount = MutationAudit::count();
        $mock = Mockery::mock(BackendMutationAuditService::class, [app(MutationAuditService::class)])
            ->makePartial();
        $mock->shouldReceive('write')->once()->passthru()->ordered();
        $mock->shouldReceive('write')->once()->andThrow(new RuntimeException('second audit failed'))->ordered();
        $this->app->instance(BackendMutationAuditService::class, $mock);
        $this->withoutExceptionHandling();

        try {
            $this->postJson(route('links.bulk_update'), [
                'data' => [
                    ['id' => $first->id, 'sort' => 11],
                    ['id' => $second->id, 'sort' => 21],
                ],
            ]);
            $this->fail('Expected second audit persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('second audit failed', $exception->getMessage());
        }

        $this->assertSame(10, $first->fresh()->sort);
        $this->assertSame(20, $second->fresh()->sort);
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    public function test_enabled_bulk_update_rolls_back_when_model_event_cancels_write(): void
    {
        config(['wncms.mutation_audit.enabled' => true]);
        $link = Link::create($this->linkData(['sort' => 10]));
        $beforeAuditCount = MutationAudit::count();
        Event::listen('eloquent.updating: ' . Link::class, fn (): bool => false);
        $this->withoutExceptionHandling();

        try {
            $this->postJson(route('links.bulk_update'), [
                'data' => [['id' => $link->id, 'sort' => 20]],
            ]);
            $this->fail('Expected cancelled Link bulk update failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Link bulk update was cancelled.', $exception->getMessage());
        }

        $this->assertSame(10, $link->fresh()->sort);
        $this->assertSame($beforeAuditCount, MutationAudit::count());
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

    /**
     * Build the serialized bulk tag form payload.
     *
     * @param  string  $action
     * @param  array  $categories
     * @param  array  $tags
     * @return string
     */
    protected function bulkTagFormData(string $action, array $categories = [], array $tags = []): string
    {
        return http_build_query([
            'action' => $action,
            'link_categories' => json_encode(array_map(fn (string $name): array => ['name' => $name], $categories)),
            'link_tags' => json_encode(array_map(fn (string $name): array => ['name' => $name], $tags)),
        ]);
    }

    /**
     * Return sorted Link tag names for one type.
     *
     * @param  \Wncms\Models\Link  $link
     * @param  string  $type
     * @return array
     */
    protected function tagNames(Link $link, string $type): array
    {
        return $link->tagsWithType($type)->pluck('name')->sort()->values()->all();
    }
}
