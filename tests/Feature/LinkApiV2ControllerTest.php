<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Models\Link;
use Wncms\Models\MutationAudit;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class LinkApiV2ControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $databaseSnapshot = [];

    private bool $suspendedTestTransaction = false;

    /**
     * Prepare API access and website-scoped Links for each contract test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        foreach (DB::getSchemaBuilder()->getTableListing() as $table) {
            $this->databaseSnapshot[$table] = DB::table($table)->get()->map(static fn ($row): array => (array) $row)->all();
        }

        auth()->forgetGuards();
        app(PermissionRegistrar::class)->registerPermissions(Gate::getFacadeRoot());
        config([
            'wncms-api-v2.auth_security.security_event_correlation' => [
                'active_key_version' => 'v1',
                'keys' => ['v1' => [
                    'ip' => 'links-ip-correlation-key-123456789012345',
                    'login_identifier' => 'links-login-correlation-key-123456789012345',
                    'user_agent' => 'links-agent-correlation-key-123456789012345',
                ]],
            ],
            'wncms.auth_security.legacy_personal_tokens_enabled' => true,
            'wncms.auth_security.legacy_personal_tokens_cutoff_at' => now('UTC')->addDay()->toIso8601String(),
        ]);
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        config(['wncms.models.link.website_mode' => 'multi']);
        config(['wncms.mutation_audit.enabled' => true]);

        while (DB::transactionLevel() > 0) {
            DB::commit();
        }
        $this->suspendedTestTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->suspendedTestTransaction) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::statement('PRAGMA foreign_keys = OFF');
            foreach (array_reverse(array_keys($this->databaseSnapshot)) as $table) {
                DB::table($table)->delete();
            }
            foreach ($this->databaseSnapshot as $table => $rows) {
                if ($rows !== []) {
                    DB::table($table)->insert($rows);
                }
            }
            DB::statement('PRAGMA foreign_keys = ON');
            DB::beginTransaction();
        }

        parent::tearDown();
    }

    /**
     * Verify Links API v2 rejects missing authentication and route permission.
     */
    public function test_links_api_v2_requires_authentication_and_permission(): void
    {
        $website = Website::firstOrFail();

        $unauthenticated = $this->getJson('/api/v2/backend/links?website_id='.$website->id);
        $unauthenticated
            ->assertUnauthorized()
            ->assertJsonPath('code', 401);
        $this->assertAutomationEnvelope($unauthenticated);

        [, $token] = $this->tokenUser([], $website);

        $readForbidden = $this->withToken($token)
            ->getJson('/api/v2/backend/links?website_id='.$website->id);
        $readForbidden
            ->assertForbidden()
            ->assertJsonPath('code', 403);
        $this->assertAutomationEnvelope($readForbidden);

        $mutationForbidden = $this->withToken($token)
            ->postJson('/api/v2/backend/links/bulk_update', [
                'items' => [
                    ['identifier' => 99999999, 'sort' => 10],
                ],
                'website_id' => $website->id,
            ]);
        $mutationForbidden
            ->assertForbidden()
            ->assertJsonPath('code', 403);
        $this->assertAutomationEnvelope($mutationForbidden);
    }

    /**
     * Verify list filters and ID or slug inspection never leak another website.
     */
    public function test_links_api_v2_lists_and_inspects_only_the_selected_website(): void
    {
        $website = Website::firstOrFail();
        $otherWebsite = $this->otherWebsite();
        [, $token] = $this->tokenUser(['link_index', 'link_edit'], $website);
        $keyword = 'API scoped '.uniqid();

        $first = $this->websiteLink($website, [
            'status' => 'active',
            'name' => $keyword.' First',
            'slug' => 'api-scoped-first-'.uniqid(),
        ]);
        $second = $this->websiteLink($website, [
            'status' => 'active',
            'name' => $keyword.' Second',
            'slug' => 'api-scoped-second-'.uniqid(),
        ]);
        $this->websiteLink($website, [
            'status' => 'inactive',
            'name' => $keyword.' Inactive',
        ]);
        $crossWebsite = $this->websiteLink($otherWebsite, [
            'status' => 'active',
            'name' => $keyword.' Cross Website',
        ]);

        $list = $this->withToken($token)->getJson('/api/v2/backend/links?'.http_build_query([
            'website_id' => $website->id,
            'status' => 'active',
            'keyword' => $keyword,
            'sort' => 'id',
            'direction' => 'asc',
            'page' => 1,
            'per_page' => 10,
        ]));

        $list->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('code', 200)
            ->assertJsonPath('meta.surface', 'api_v2')
            ->assertJsonPath('meta.website_id', $website->id)
            ->assertJsonPath('data.pagination.page', 1)
            ->assertJsonPath('data.pagination.per_page', 10)
            ->assertJsonPath('data.pagination.total', 2);
        $this->assertSame(
            [$first->id, $second->id],
            array_column($list->json('data.items'), 'id')
        );
        $this->assertSame(
            ['code', 'status', 'message', 'data', 'meta', 'errors'],
            array_keys($list->json())
        );

        $inspectById = $this->withToken($token)
            ->getJson("/api/v2/backend/links/{$first->id}?website_id={$website->id}");
        $inspectById
            ->assertOk()
            ->assertJsonPath('data.item.id', $first->id);
        $this->assertAutomationEnvelope($inspectById);

        $inspectBySlug = $this->withToken($token)
            ->getJson("/api/v2/backend/links/{$second->slug}?website_id={$website->id}");
        $inspectBySlug
            ->assertOk()
            ->assertJsonPath('data.item.slug', $second->slug);
        $this->assertAutomationEnvelope($inspectBySlug);

        $crossWebsiteTarget = $this->withToken($token)
            ->getJson("/api/v2/backend/links/{$crossWebsite->id}?website_id={$website->id}");
        $crossWebsiteTarget
            ->assertNotFound()
            ->assertJsonPath('code', 404);
        $this->assertAutomationEnvelope($crossWebsiteTarget);

        $invalidQueries = [
            'status=archived',
            'sort=tracking_code',
            'direction=sideways',
            'page=0',
            'per_page=0',
            'per_page=101',
        ];

        foreach ($invalidQueries as $query) {
            $invalid = $this->withToken($token)
                ->getJson("/api/v2/backend/links?website_id={$website->id}&{$query}");
            $invalid
                ->assertUnprocessable()
                ->assertJsonPath('code', 422);
            $this->assertAutomationEnvelope($invalid);
        }

        $deniedList = $this->withToken($token)
            ->getJson("/api/v2/backend/links?website_id={$otherWebsite->id}");
        $deniedList
            ->assertForbidden()
            ->assertJsonPath('code', 403)
            ->assertJsonPath('meta.error_code', 'website.scope_denied');
        $this->assertAutomationEnvelope($deniedList);

        $deniedInspect = $this->withToken($token)
            ->getJson("/api/v2/backend/links/{$crossWebsite->id}?website_id={$otherWebsite->id}");
        $deniedInspect
            ->assertForbidden()
            ->assertJsonPath('code', 403)
            ->assertJsonPath('meta.error_code', 'website.scope_denied');
        $this->assertAutomationEnvelope($deniedInspect);

        $unknownWebsiteId = (int) Website::max('id') + 1000;
        $unknownWebsite = $this->withToken($token)
            ->getJson("/api/v2/backend/links?website_id={$unknownWebsiteId}");
        $unknownWebsite
            ->assertForbidden()
            ->assertJsonPath('code', 403)
            ->assertJsonPath('meta.error_code', 'website.scope_denied');
        $this->assertAutomationEnvelope($unknownWebsite);
    }

    /**
     * Verify Links API v2 requires one explicit stable website selector.
     */
    public function test_links_api_v2_requires_current_website_context_when_none_is_explicit(): void
    {
        $website = Website::firstOrFail();
        [, $token] = $this->tokenUser(['link_index'], $website);

        Website::query()->delete();
        wncms()->cache()->flush(['websites']);

        $missingContext = $this->withToken($token)->getJson('/api/v2/backend/links');
        $missingContext
            ->assertForbidden()
            ->assertJsonPath('code', 403)
            ->assertJsonPath('meta.error_code', 'website.scope_missing');
        $this->assertAutomationEnvelope($missingContext);
    }

    /**
     * Verify every mutation previews without model, tag, or audit writes by default.
     */
    public function test_links_api_v2_mutations_preview_by_default(): void
    {
        $website = Website::firstOrFail();
        [, $token] = $this->tokenUser([
            'link_create',
            'link_edit',
            'link_delete',
        ], $website);
        $updateTarget = $this->websiteLink($website, ['sort' => 10]);
        $deleteTarget = $this->websiteLink($website);
        $bulkUpdateTarget = $this->websiteLink($website, ['sort' => 20]);
        $bulkTagTarget = $this->websiteLink($website);
        $beforeLinkCount = Link::count();
        $beforeTagCount = DB::table('tags')->count();
        $beforeAuditCount = MutationAudit::count();

        $responses = [
            $this->withToken($token)->postJson('/api/v2/backend/links', [
                'name' => 'API preview create',
                'url' => 'https://example.com/api-preview-create',
                'slug' => 'api-preview-create-'.uniqid(),
                'website_id' => $website->id,
                'link_categories' => ['Preview category'],
                'force' => true,
                'dry_run' => true,
            ]),
            $this->withToken($token)->patchJson("/api/v2/backend/links/{$updateTarget->id}", [
                'name' => 'API preview update',
                'website_id' => $website->id,
            ]),
            $this->withToken($token)->deleteJson("/api/v2/backend/links/{$deleteTarget->id}", [
                'website_id' => $website->id,
            ]),
            $this->withToken($token)->postJson('/api/v2/backend/links/bulk_update', [
                'items' => [
                    ['identifier' => $bulkUpdateTarget->id, 'sort' => 21],
                ],
                'website_id' => $website->id,
            ]),
            $this->withToken($token)->postJson('/api/v2/backend/links/bulk_sync_tags', [
                'identifiers' => [$bulkTagTarget->id],
                'action' => 'sync',
                'link_categories' => ['Preview category'],
                'website_id' => $website->id,
            ]),
        ];

        foreach ($responses as $response) {
            $response->assertStatus(202)
                ->assertJsonPath('code', 202)
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('meta.surface', 'api_v2')
                ->assertJsonPath('meta.dry_run', true);
            $this->assertAutomationEnvelope($response);
        }

        $this->assertSame($beforeLinkCount, Link::count());
        $this->assertSame($beforeTagCount, DB::table('tags')->count());
        $this->assertSame($beforeAuditCount, MutationAudit::count());
        $this->assertSame(10, $updateTarget->fresh()->sort);
        $this->assertSame(20, $bulkUpdateTarget->fresh()->sort);
        $this->assertTrue(Link::whereKey($deleteTarget->id)->exists());
        $this->assertSame([], $this->tagNames($bulkTagTarget->fresh(), 'link_category'));
    }

    /**
     * Verify forced mutations use the token user for hooks, audits, and writes.
     */
    public function test_links_api_v2_forced_mutations_use_the_token_user_as_actor(): void
    {
        $website = Website::firstOrFail();
        [$actor, $token] = $this->tokenUser([
            'link_create',
            'link_edit',
            'link_delete',
        ], $website);
        $updateTarget = $this->websiteLink($website, ['sort' => 10]);
        $bulkUpdateTarget = $this->websiteLink($website, ['sort' => 20]);
        $bulkTagTarget = $this->websiteLink($website);
        $deleteTarget = $this->websiteLink($website);
        $hookActors = [];
        $beforeAuditCount = MutationAudit::count();
        $beforeAuditId = (int) MutationAudit::max('id');
        $cacheKey = 'links-api-v2-cache-'.uniqid();

        Event::listen('wncms.backend.links.store.after', function () use (&$hookActors): void {
            $hookActors['store'] = auth()->id();
        });
        Event::listen('wncms.backend.links.update.after', function () use (&$hookActors): void {
            $hookActors['update'] = auth()->id();
        });

        wncms()->cache()->put($cacheKey, 'cached', 60, ['links']);
        $createdSlug = 'api-forced-create-'.uniqid();
        $create = $this->withToken($token)->postJson('/api/v2/backend/links', [
            'name' => 'API forced create',
            'url' => 'https://example.com/api-forced-create',
            'slug' => $createdSlug,
            'website_id' => $website->id,
            'force' => true,
        ]);
        $create->assertCreated()
            ->assertJsonPath('meta.actor_user_id', $actor->id)
            ->assertJsonPath('meta.surface', 'api_v2');
        $this->assertAutomationEnvelope($create);
        $this->assertFalse(wncms()->cache()->tags(['links'])->has($cacheKey));

        $update = $this->withToken($token)->patchJson("/api/v2/backend/links/{$updateTarget->id}", [
            'sort' => 11,
            'website_id' => $website->id,
            'force' => true,
        ]);
        $update->assertOk()->assertJsonPath('meta.actor_user_id', $actor->id);
        $this->assertAutomationEnvelope($update);

        $bulkUpdate = $this->withToken($token)->postJson('/api/v2/backend/links/bulk_update', [
            'items' => [
                ['identifier' => $bulkUpdateTarget->id, 'sort' => 21],
            ],
            'website_id' => $website->id,
            'force' => true,
        ]);
        $bulkUpdate->assertOk()->assertJsonPath('data.summary.changed', 1);
        $this->assertAutomationEnvelope($bulkUpdate);

        $bulkSyncTags = $this->withToken($token)->postJson('/api/v2/backend/links/bulk_sync_tags', [
            'identifiers' => [$bulkTagTarget->slug],
            'action' => 'sync',
            'link_categories' => ['Partners'],
            'website_id' => $website->id,
            'force' => true,
        ]);
        $bulkSyncTags->assertOk()->assertJsonPath('data.summary.changed', 1);
        $this->assertAutomationEnvelope($bulkSyncTags);

        $delete = $this->withToken($token)->deleteJson("/api/v2/backend/links/{$deleteTarget->id}", [
            'website_id' => $website->id,
            'force' => true,
        ]);
        $delete->assertOk()->assertJsonPath('meta.actor_user_id', $actor->id);
        $this->assertAutomationEnvelope($delete);

        $this->assertTrue(Link::where('slug', $createdSlug)->exists());
        $this->assertSame(11, $updateTarget->fresh()->sort);
        $this->assertSame(21, $bulkUpdateTarget->fresh()->sort);
        $this->assertSame(['Partners'], $this->tagNames($bulkTagTarget->fresh(), 'link_category'));
        $this->assertFalse(Link::whereKey($deleteTarget->id)->exists());
        $this->assertSame([
            'store' => $actor->id,
            'update' => $actor->id,
        ], $hookActors);

        $audits = MutationAudit::query()
            ->where('id', '>', $beforeAuditId)
            ->orderBy('id')
            ->get();
        $this->assertCount(5, $audits);
        $this->assertSame(['create', 'update', 'bulk_update', 'bulk_sync_tags', 'delete'], $audits->pluck('action')->all());
        $this->assertSame([$actor->id], $audits->pluck('actor_id')->unique()->values()->all());
        $this->assertSame(['api_v2'], $audits->pluck('surface')->unique()->values()->all());

        wncms()->cache()->put($cacheKey, 'cached', 60, ['links']);
        $noChange = $this->withToken($token)->postJson('/api/v2/backend/links/bulk_update', [
            'items' => [
                ['identifier' => $bulkUpdateTarget->id, 'sort' => 21],
            ],
            'website_id' => $website->id,
            'force' => true,
        ]);
        $noChange->assertOk()->assertJsonPath('data.summary.changed', 0);
        $this->assertAutomationEnvelope($noChange);
        $this->assertTrue(wncms()->cache()->tags(['links'])->has($cacheKey));
        $this->assertSame($beforeAuditCount + 5, MutationAudit::count());
    }

    /**
     * Verify API writes remain successful and return stable audit metadata when disabled.
     */
    public function test_links_api_v2_forced_create_writes_without_audit_when_disabled(): void
    {
        config(['wncms.mutation_audit.enabled' => false]);
        $website = Website::firstOrFail();
        [, $token] = $this->tokenUser(['link_create'], $website);
        $slug = 'api-forced-create-no-audit-'.uniqid();
        $beforeAuditCount = MutationAudit::count();

        $response = $this->withToken($token)->postJson('/api/v2/backend/links', [
            'name' => 'API forced create without audit',
            'url' => 'https://example.com/api-forced-create-without-audit',
            'slug' => $slug,
            'website_id' => $website->id,
            'force' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.audit', [
            'enabled' => false,
            'id' => null,
        ]);
        $this->assertTrue(Link::where('slug', $slug)->exists());
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    /**
     * Verify disabled API bulk writes return an empty stable audit ID list.
     */
    public function test_links_api_v2_forced_bulk_update_returns_empty_audit_ids_when_disabled(): void
    {
        config(['wncms.mutation_audit.enabled' => false]);
        $website = Website::firstOrFail();
        [, $token] = $this->tokenUser(['link_edit'], $website);
        $link = $this->websiteLink($website, ['sort' => 10]);
        $beforeAuditCount = MutationAudit::count();

        $response = $this->withToken($token)->postJson('/api/v2/backend/links/bulk_update', [
            'items' => [
                ['identifier' => $link->id, 'sort' => 20],
            ],
            'website_id' => $website->id,
            'force' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.audit', [
            'enabled' => false,
            'ids' => [],
        ]);
        $this->assertSame(20, $link->fresh()->sort);
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    /**
     * Verify scoped, missing, and stale bulk targets never produce partial writes.
     */
    public function test_links_api_v2_bulk_mutations_are_atomic(): void
    {
        $website = Website::firstOrFail();
        $otherWebsite = $this->otherWebsite();
        [, $token] = $this->tokenUser(['link_edit'], $website);
        $first = $this->websiteLink($website, ['sort' => 10]);
        $crossWebsite = $this->websiteLink($otherWebsite, ['sort' => 20]);
        $beforeAuditCount = MutationAudit::count();

        $this->withToken($token)->postJson('/api/v2/backend/links/bulk_update', [
            'items' => [
                ['identifier' => $first->id, 'sort' => 11],
                ['identifier' => $crossWebsite->id, 'sort' => 21],
            ],
            'website_id' => $website->id,
            'force' => true,
        ])->assertNotFound();
        $this->assertSame(10, $first->fresh()->sort);
        $this->assertSame(20, $crossWebsite->fresh()->sort);

        $this->withToken($token)->postJson('/api/v2/backend/links/bulk_sync_tags', [
            'identifiers' => [$first->id, 99999999],
            'action' => 'sync',
            'link_categories' => ['Partners'],
            'website_id' => $website->id,
            'force' => true,
        ])->assertNotFound();
        $this->assertSame([], $this->tagNames($first->fresh(), 'link_category'));

        $staleFirst = $this->websiteLink($website);
        $staleSecond = $this->websiteLink($website);
        $eventCount = 0;
        $phase = 'stale';
        Event::listen('eloquent.retrieved: '.Link::class, function () use (&$eventCount, &$phase, $staleSecond): void {
            $eventCount++;
            if ($phase === 'stale' && $eventCount === 3) {
                $staleSecond->syncTagsWithType(['Stale category'], 'link_category');
            }
        });

        $this->withToken($token)->postJson('/api/v2/backend/links/bulk_sync_tags', [
            'identifiers' => [$staleFirst->id, $staleSecond->id],
            'action' => 'sync',
            'link_categories' => ['Partners'],
            'website_id' => $website->id,
            'force' => true,
        ])->assertStatus(409)->assertJsonPath('data.legacy.errors.items.0', 'stale');
        $phase = 'done';

        $this->assertSame([], $this->tagNames($staleFirst->fresh(), 'link_category'));
        $this->assertSame([], $this->tagNames($staleSecond->fresh(), 'link_category'));
        $this->assertSame($beforeAuditCount, MutationAudit::count());
    }

    /**
     * Verify bulk tag transport rejects malformed JSON shapes before any writes.
     */
    public function test_links_api_v2_bulk_sync_tags_rejects_malformed_transport_shapes_without_writes(): void
    {
        $website = Website::firstOrFail();
        [, $token] = $this->tokenUser(['link_edit'], $website);
        $link = $this->websiteLink($website);
        $link->syncTagsWithType(['Existing category'], 'link_category');
        $beforeCounts = [
            'links' => Link::count(),
            'tag_pivots' => DB::table('taggables')->count(),
            'audits' => MutationAudit::count(),
        ];
        $payloads = [
            [
                'identifiers' => (object) ['0' => $link->id],
                'action' => 'sync',
                'link_categories' => ['Partners'],
            ],
            [
                'identifiers' => [$link->id],
                'action' => 'sync',
                'link_tags' => 'Featured',
            ],
            [
                'identifiers' => [$link->id],
                'action' => ['sync'],
                'link_categories' => ['Partners'],
            ],
        ];

        foreach ($payloads as $payload) {
            $response = $this->withToken($token)->postJson('/api/v2/backend/links/bulk_sync_tags', array_merge($payload, [
                'website_id' => $website->id,
                'force' => true,
            ]));

            $response->assertUnprocessable()->assertJsonPath('code', 422);
            $this->assertAutomationEnvelope($response);
            $this->assertSame(['Existing category'], $this->tagNames($link->fresh(), 'link_category'));
            $this->assertSame($beforeCounts, [
                'links' => Link::count(),
                'tag_pivots' => DB::table('taggables')->count(),
                'audits' => MutationAudit::count(),
            ]);
        }
    }

    /**
     * Verify bulk update transport rejects malformed JSON shapes without writes.
     */
    public function test_links_api_v2_bulk_update_rejects_malformed_transport_shapes_without_writes(): void
    {
        $website = Website::firstOrFail();
        [, $token] = $this->tokenUser(['link_edit'], $website);
        $link = $this->websiteLink($website, ['sort' => 10]);
        $beforeCounts = [
            'links' => Link::count(),
            'website_pivots' => DB::table('model_has_websites')->count(),
            'audits' => MutationAudit::count(),
        ];
        $payloads = [
            [
                'items' => (object) ['0' => ['identifier' => $link->id, 'sort' => 11]],
            ],
            [
                'items' => 'invalid',
            ],
        ];

        foreach ($payloads as $payload) {
            $response = $this->withToken($token)->postJson('/api/v2/backend/links/bulk_update', array_merge($payload, [
                'website_id' => $website->id,
                'force' => true,
            ]));

            $response->assertUnprocessable()->assertJsonPath('code', 422);
            $this->assertAutomationEnvelope($response);
            $this->assertSame(10, $link->fresh()->sort);
            $this->assertSame($beforeCounts, [
                'links' => Link::count(),
                'website_pivots' => DB::table('model_has_websites')->count(),
                'audits' => MutationAudit::count(),
            ]);
        }

        $valid = $this->withToken($token)->postJson('/api/v2/backend/links/bulk_update', [
            'items' => [
                ['identifier' => $link->id, 'sort' => 11],
            ],
            'website_id' => $website->id,
            'force' => true,
        ]);

        $valid->assertOk()->assertJsonPath('data.summary.changed', 1);
        $this->assertAutomationEnvelope($valid);
        $this->assertSame(11, $link->fresh()->sort);
        $this->assertSame($beforeCounts['audits'] + 1, MutationAudit::count());
    }

    /**
     * Verify the unguarded Links bulk-delete route is not registered.
     */
    public function test_links_api_v2_bulk_delete_is_unavailable(): void
    {
        $website = Website::firstOrFail();
        [, $token] = $this->tokenUser(['link_bulk_delete'], $website);

        $this->assertFalse(Route::has('api.v2.backend.links.bulk_delete'));
        $this->withToken($token)->postJson('/api/v2/backend/links/bulk_delete', [
            'model_ids' => [99999999],
            'website_id' => $website->id,
        ])->assertStatus(405);
    }

    /**
     * Create a token user with the selected permissions and website access.
     *
     * @return array{0: \Wncms\Models\User, 1: string}
     */
    protected function tokenUser(array $permissions, Website $website): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $password = 'api-v2-password';
        $user = User::create([
            'username' => 'api-v2-user-'.uniqid(),
            'email' => 'api-v2-user-'.uniqid().'@example.com',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('member');
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }
        $user->websites()->syncWithoutDetaching([$website->id]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($permissions as $permission) {
            $this->assertTrue($user->fresh()->can($permission));
        }

        $response = $this->postJson('/api/v2/backend/auth/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'links-api-v2-test',
        ]);
        $response->assertOk();
        auth()->forgetGuards();

        return [$user, (string) $response->json('data.token')];
    }

    /**
     * Create or return a second website.
     */
    protected function otherWebsite(): Website
    {
        return Website::query()->whereKeyNot(Website::firstOrFail()->id)->first()
            ?: Website::create([
                'domain' => 'links-api-v2-'.uniqid().'.test',
                'site_name' => 'Links API v2 website',
            ]);
    }

    /**
     * Create one Link and bind it to the selected website.
     */
    protected function websiteLink(Website $website, array $overrides = []): Link
    {
        $link = Link::create($this->linkData($overrides));
        $link->bindWebsites([$website->id]);

        return $link;
    }

    /**
     * Build valid Link attributes with unique identifiers.
     */
    protected function linkData(array $overrides = []): array
    {
        return array_merge([
            'status' => 'active',
            'tracking_code' => 'api-v2-code-'.uniqid(),
            'slug' => 'api-v2-link-'.uniqid(),
            'name' => 'API v2 Link',
            'url' => 'https://example.com/api-v2-link',
            'description' => 'API v2 description',
            'external_thumbnail' => 'https://example.com/thumbnail.jpg',
            'clicks' => 0,
            'remark' => 'API v2 remark',
            'sort' => 10,
            'color' => '#ffffff',
            'background' => '#000000',
            'is_pinned' => false,
            'expired_at' => null,
            'slogan' => 'API v2 slogan',
            'contact' => '@api-v2',
            'is_recommended' => false,
            'hit_at' => null,
        ], $overrides);
    }

    /**
     * Return sorted tag names for one Link tag type.
     */
    protected function tagNames(Link $link, string $type): array
    {
        return $link->tagsWithType($type)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Assert the stable automation envelope keys in their canonical order.
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     */
    protected function assertAutomationEnvelope($response): void
    {
        $this->assertSame(
            ['code', 'status', 'message', 'data', 'meta', 'errors'],
            array_keys($response->json())
        );
    }
}
