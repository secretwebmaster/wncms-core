<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Models\ApiServiceToken;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class ServiceTokenManagementTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Website $website;

    private string $password = 'service-token-password';

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $databaseSnapshot = [];

    private bool $suspendedTestTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (DB::getSchemaBuilder()->getTableListing() as $table) {
            $this->databaseSnapshot[$table] = DB::table($table)->get()->map(static fn ($row): array => (array) $row)->all();
        }

        config([
            'wncms-api-v2.idempotency.store' => 'array',
            'wncms-api-v2.auth_security.security_event_correlation' => [
                'active_key_version' => 'v1',
                'keys' => ['v1' => [
                    'ip' => 'task9-service-ip-correlation-key-123456',
                    'login_identifier' => 'task9-service-login-correlation-key-123456',
                    'user_agent' => 'task9-service-agent-correlation-key-123456',
                ]],
            ],
            'wncms.auth_security.login_account_attempts' => 50,
            'wncms.auth_security.login_ip_attempts' => 50,
            'wncms.auth_security.login_progressive_delay_seconds' => [0],
        ]);
        Cache::flush();
        Cache::flushLocks();
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');

        $this->user = User::create([
            'username' => 'service-token-'.uniqid(),
            'email' => 'service-token-'.uniqid().'@example.test',
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
        ]);
        $this->website = Website::firstOrFail();
        $this->user->websites()->syncWithoutDetaching([$this->website->id]);
        foreach ([
            'api_token_create', 'api_token_index', 'api_token_show', 'api_token_rotate', 'api_token_revoke',
            'post_index', 'post_edit',
        ] as $permission) {
            $this->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
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
            Cache::flush();
        }

        parent::tearDown();
    }

    public function test_create_list_show_rotate_and_revoke_keep_plaintext_at_response_boundaries(): void
    {
        $access = $this->login()['access_token'];
        $this->suspendHarnessTransaction();
        $payload = [
            'name' => 'deploy',
            'template' => 'read_only',
            'website_ids' => [$this->website->id],
            'expires_in_days' => 30,
        ];

        $createProof = $this->proof($access, 'backend.auth.service_tokens.store', 'service_token.create');
        $created = $this->withToken($access)
            ->withHeader('Idempotency-Key', 'service-create-0001')
            ->withHeader('X-WNCMS-Step-Up', $createProof)
            ->postJson('/api/v2/backend/auth/service-tokens', $payload)
            ->assertCreated()
            ->assertJsonStructure(['data' => ['token', 'service_token' => ['id', 'abilities', 'website_ids']]])
            ->json('data');

        $plainText = $created['token'];
        $tokenId = $created['service_token']['id'];
        $replayed = $this->withToken($access)
            ->withHeader('Idempotency-Key', 'service-create-0001')
            ->withHeader('X-WNCMS-Step-Up', $createProof)
            ->postJson('/api/v2/backend/auth/service-tokens', $payload)
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->json('data');
        $this->assertSame($plainText, $replayed['token']);
        $this->assertStringNotContainsString(
            $plainText,
            json_encode(Cache::store('array')->getStore()->all(false), JSON_THROW_ON_ERROR),
        );
        $row = ApiServiceToken::query()->where('token_id', $tokenId)->firstOrFail();
        $this->assertSame(hash('sha256', $plainText), $row->token_hash);
        $this->assertStringNotContainsString($plainText, json_encode($row->toArray(), JSON_THROW_ON_ERROR));

        foreach (['/api/v2/backend/auth/service-tokens', '/api/v2/backend/auth/service-tokens/'.$tokenId] as $uri) {
            $json = $this->withToken($access)->getJson($uri)->assertOk()->getContent();
            $this->assertStringNotContainsString($plainText, $json);
            $this->assertStringNotContainsString($row->token_hash, $json);
        }

        $rotated = $this->withToken($access)
            ->withHeader('Idempotency-Key', 'service-rotate-0001')
            ->withHeader('X-WNCMS-Step-Up', $this->proof($access, 'backend.auth.service_tokens.rotate', 'service_token.rotate'))
            ->postJson('/api/v2/backend/auth/service-tokens/'.$tokenId.'/rotate')
            ->assertOk()
            ->json('data');
        $this->assertNotSame($plainText, $rotated['token']);
        $this->assertDatabaseMissing('api_service_tokens', ['token_id' => $tokenId]);

        $newId = $rotated['service_token']['id'];
        $this->withToken($access)
            ->withHeader('Idempotency-Key', 'service-revoke-0001')
            ->withHeader('X-WNCMS-Step-Up', $this->proof($access, 'backend.auth.service_tokens.destroy', 'service_token.revoke'))
            ->deleteJson('/api/v2/backend/auth/service-tokens/'.$newId)
            ->assertOk();
        $this->assertNotNull(ApiServiceToken::query()->where('token_id', $newId)->value('revoked_at'));
    }

    public function test_scope_expiry_and_credential_management_boundaries_fail_closed(): void
    {
        $access = $this->login()['access_token'];
        $this->suspendHarnessTransaction();
        $base = ['name' => 'deploy', 'template' => 'full_admin', 'expires_in_days' => 90];

        $this->withToken($access)
            ->withHeader('Idempotency-Key', 'service-empty-scope')
            ->withHeader('X-WNCMS-Step-Up', $this->proof($access, 'backend.auth.service_tokens.store', 'service_token.create'))
            ->postJson('/api/v2/backend/auth/service-tokens', $base + ['website_ids' => []])
            ->assertUnprocessable();
        $this->withToken($access)
            ->withHeader('Idempotency-Key', 'service-permanent-denied')
            ->withHeader('X-WNCMS-Step-Up', $this->proof($access, 'backend.auth.service_tokens.store', 'service_token.create'))
            ->postJson('/api/v2/backend/auth/service-tokens', array_merge($base, [
                'website_ids' => [$this->website->id], 'expires_in_days' => 'permanent',
            ]))->assertUnprocessable();
        $this->withToken($access)
            ->withHeader('Idempotency-Key', 'service-unknown-ability')
            ->withHeader('X-WNCMS-Step-Up', $this->proof($access, 'backend.auth.service_tokens.store', 'service_token.create'))
            ->postJson('/api/v2/backend/auth/service-tokens', $base + [
                'website_ids' => [$this->website->id], 'additions' => ['credentials.write'],
            ])->assertUnprocessable();

        $material = app(TokenHasher::class)->issue('wncms_st');
        ApiServiceToken::create([
            'token_id' => $material['public_id'],
            'token_hash' => $material['hash'],
            'user_id' => $this->user->id,
            'name' => 'cannot-manage-tokens',
            'ability_template' => 'full_admin',
            'abilities' => ['*'],
            'website_ids' => [$this->website->id],
            'expires_at' => now()->addDay(),
        ]);
        $this->withToken($material['plain_text'])
            ->getJson('/api/v2/backend/auth/service-tokens')
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'risk.credential_type_denied');
    }

    /** @return array<string, mixed> */
    private function login(): array
    {
        auth()->forgetGuards();

        return $this->postJson('/api/v2/backend/auth/login', [
            'email' => $this->user->email,
            'password' => $this->password,
            'device_name' => 'service-token-tests',
        ])->assertOk()->json('data');
    }

    private function proof(string $accessToken, string $operation, string $purpose): string
    {
        return (string) $this->withToken($accessToken)->postJson('/api/v2/backend/auth/reauthenticate', [
            'password' => $this->password,
            'operation' => $operation,
            'purpose' => $purpose,
        ])->assertOk()->json('data.proof');
    }

    private function suspendHarnessTransaction(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::commit();
        }

        $this->suspendedTestTransaction = true;
    }
}
