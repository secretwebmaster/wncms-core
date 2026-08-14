<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Auth\Api\V2\LegacyPersonalTokenAuthenticator;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class LegacyPersonalTokenCompatibilityTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Website $website;

    private array $snapshot = [];

    private bool $suspended = false;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->registerPermissions(Gate::getFacadeRoot());
        foreach (DB::getSchemaBuilder()->getTableListing() as $table) {
            $this->snapshot[$table] = DB::table($table)->get()->map(static fn ($row): array => (array) $row)->all();
        }
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        uss('api_legacy_personal_tokens_enabled', 1);
        uss('api_legacy_personal_tokens_cutoff_at', now('UTC')->addDay()->toIso8601String());
        $this->website = Website::firstOrFail();
        $this->user = User::create([
            'username' => 'legacy-'.uniqid(), 'email' => 'legacy-'.uniqid().'@example.test',
            'password' => Hash::make('password'), 'api_token' => 'v1-only-'.uniqid(), 'email_verified_at' => now(),
        ]);
        $this->user->websites()->syncWithoutDetaching([$this->website->id]);
        $this->user->assignRole('member');
        $this->user->givePermissionTo(Permission::findOrCreate('link_index', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        if ($this->suspended) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::statement('PRAGMA foreign_keys = OFF');
            foreach (array_reverse(array_keys($this->snapshot)) as $table) {
                DB::table($table)->delete();
            }
            foreach ($this->snapshot as $table => $rows) {
                if ($rows !== []) {
                    DB::table($table)->insert($rows);
                }
            }
            DB::statement('PRAGMA foreign_keys = ON');
            DB::beginTransaction();
            Cache::flush();
        }
        parent::tearDown();
    }

    public function test_legacy_pat_is_explicitly_bounded_and_receives_deprecation_headers(): void
    {
        $token = $this->legacyPat(['*']);
        $this->suspendTransaction();
        $this->assertTrue((bool) gss('enable_api_access'));
        $this->assertSame('', (string) gss('api_access_whitelist', ''));
        $this->assertTrue($this->user->fresh()->can('link_index'));
        $response = $this->withToken($token)->getJson('/api/v2/backend/links?website_id='.$this->website->id);
        $response->assertOk()->assertHeader('Deprecation', 'true')->assertHeader('X-WNCMS-Credential-Type', 'legacy_personal_access_token');
        $this->assertNotSame('', (string) $response->headers->get('Sunset'));
        $this->assertStringContainsString('rel="deprecation"', (string) $response->headers->get('Link'));

        uss('api_legacy_personal_tokens_cutoff_at', now('UTC')->subSecond()->toIso8601String());
        $this->withToken($token)->getJson('/api/v2/backend/links?website_id='.$this->website->id)->assertUnauthorized();
        $this->withToken((string) $this->user->api_token)->getJson('/api/v2/backend/links?website_id='.$this->website->id)->assertUnauthorized();
    }

    public function test_legacy_star_never_bypasses_permissions_or_single_website_boundary(): void
    {
        $token = $this->legacyPat(['*']);
        $this->suspendTransaction();
        $this->user->revokePermissionTo('link_index');
        $this->withToken($token)->getJson('/api/v2/backend/links?website_id='.$this->website->id)->assertForbidden();

        $this->user->givePermissionTo(Permission::findOrCreate('link_index', 'web'));
        $second = Website::create(['domain' => 'legacy-second-'.uniqid().'.test', 'site_name' => 'Legacy second']);
        $this->user->websites()->syncWithoutDetaching([$second->id]);
        $this->withToken($token)->getJson('/api/v2/backend/links?website_id='.$this->website->id)->assertUnauthorized();
    }

    public function test_schema_introspection_is_read_only_and_reports_optional_columns(): void
    {
        $before = DB::getSchemaBuilder()->getColumnListing('personal_access_tokens');
        $status = app(LegacyPersonalTokenAuthenticator::class)->schemaStatus();
        $this->assertTrue($status['compatible']);
        $this->assertContains('last_used_at', $status['optional_present']);
        $this->assertSame($before, DB::getSchemaBuilder()->getColumnListing('personal_access_tokens'));
    }

    public function test_legacy_pat_last_used_metadata_is_debounced_for_five_minutes(): void
    {
        $start = CarbonImmutable::parse('2026-08-14 00:00:00', 'UTC');
        CarbonImmutable::setTestNow($start);
        $token = $this->legacyPat(['*']);
        $tokenId = (int) explode('|', $token, 2)[0];
        $this->suspendTransaction();

        $this->withToken($token)->getJson('/api/v2/backend/links?website_id='.$this->website->id)->assertOk();
        $first = (string) DB::table('personal_access_tokens')->where('id', $tokenId)->value('last_used_at');

        CarbonImmutable::setTestNow($start->addMinute());
        $this->withToken($token)->getJson('/api/v2/backend/links?website_id='.$this->website->id)->assertOk();
        $this->assertSame($first, (string) DB::table('personal_access_tokens')->where('id', $tokenId)->value('last_used_at'));

        CarbonImmutable::setTestNow($start->addMinutes(6));
        $this->withToken($token)->getJson('/api/v2/backend/links?website_id='.$this->website->id)->assertOk();
        $this->assertNotSame($first, (string) DB::table('personal_access_tokens')->where('id', $tokenId)->value('last_used_at'));
    }

    public function test_legacy_pat_metadata_write_failure_does_not_break_authorized_read(): void
    {
        $token = $this->legacyPat(['*']);
        $this->suspendTransaction();
        DB::unprepared("CREATE TRIGGER deny_legacy_last_used BEFORE UPDATE OF last_used_at ON personal_access_tokens BEGIN SELECT RAISE(FAIL, 'simulated metadata outage'); END");

        try {
            $this->withToken($token)->getJson('/api/v2/backend/links?website_id='.$this->website->id)->assertOk();
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS deny_legacy_last_used');
        }
    }

    private function legacyPat(array $abilities): string
    {
        $secret = 'legacy-secret-'.bin2hex(random_bytes(12));
        $id = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => $this->user->getMorphClass(), 'tokenable_id' => $this->user->id,
            'name' => 'legacy', 'token' => hash('sha256', $secret), 'abilities' => json_encode($abilities),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id.'|'.$secret;
    }

    private function suspendTransaction(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::commit();
        }
        $this->suspended = true;
    }
}
