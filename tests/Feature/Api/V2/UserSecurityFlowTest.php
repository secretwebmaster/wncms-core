<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Models\ApiServiceToken;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Notifications\ApiSecurityLink;
use Wncms\Notifications\ResetPassword;
use Wncms\Tests\TestCase;

class UserSecurityFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private string $password = 'Old-Password-123!';
    private array $snapshot = [];
    private bool $suspended = false;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (DB::getSchemaBuilder()->getTableListing() as $table) {
            $this->snapshot[$table] = DB::table($table)->get()->map(static fn ($row): array => (array) $row)->all();
        }
        config([
            'wncms-api-v2.idempotency.store' => 'array',
            'wncms-api-v2.auth_security.client_callback_url' => 'https://client.example.test/auth/callback',
            'wncms-api-v2.auth_security.security_event_correlation' => [
                'active_key_version' => 'v1', 'keys' => ['v1' => [
                    'ip' => 'task10-ip-correlation-key-123456789',
                    'login_identifier' => 'task10-login-correlation-key-123456789',
                    'user_agent' => 'task10-agent-correlation-key-123456789',
                ]],
            ],
            'wncms.auth_security.login_account_attempts' => 50,
            'wncms.auth_security.login_ip_attempts' => 50,
            'wncms.auth_security.login_progressive_delay_seconds' => [0],
        ]);
        Cache::flush();
        Cache::flushLocks();
        Notification::fake();
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        $this->user = User::create([
            'username' => 'security-flow-'.uniqid(), 'email' => 'security-flow-'.uniqid().'@example.test',
            'password' => Hash::make($this->password), 'api_token' => 'legacy-v1-'.uniqid(), 'email_verified_at' => now(),
        ]);
        $this->user->websites()->syncWithoutDetaching([Website::firstOrFail()->id]);
    }

    protected function tearDown(): void
    {
        if ($this->suspended) {
            while (DB::transactionLevel() > 0) DB::rollBack();
            DB::statement('PRAGMA foreign_keys = OFF');
            foreach (array_reverse(array_keys($this->snapshot)) as $table) DB::table($table)->delete();
            foreach ($this->snapshot as $table => $rows) if ($rows !== []) DB::table($table)->insert($rows);
            DB::statement('PRAGMA foreign_keys = ON');
            DB::beginTransaction();
            Cache::flush();
        }
        parent::tearDown();
    }

    public function test_password_change_revokes_every_exactly_attributable_credential(): void
    {
        $login = $this->login();
        $sessionId = DB::table('api_sessions')->where('session_id', $login['session']['id'])->value('id');
        $material = app(TokenHasher::class)->issue('wncms_st');
        ApiServiceToken::create([
            'token_id' => $material['public_id'], 'token_hash' => $material['hash'], 'user_id' => $this->user->id,
            'name' => 'automation', 'ability_template' => 'read_only', 'abilities' => ['posts.read'],
            'website_ids' => [Website::firstOrFail()->id], 'expires_at' => now()->addDays(30),
        ]);
        $ownedPat = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => $this->user->getMorphClass(), 'tokenable_id' => $this->user->id,
            'name' => 'owned', 'token' => hash('sha256', 'owned-'.uniqid()), 'abilities' => '["*"]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherPat = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => 'Other\\Actor', 'tokenable_id' => $this->user->id,
            'name' => 'other-type', 'token' => hash('sha256', 'other-'.uniqid()), 'abilities' => '["*"]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->suspendTransaction();
        $proof = $this->proof($login['access_token'], 'backend.auth.password.update', 'password.change');

        $this->withToken($login['access_token'])
            ->withHeader('Idempotency-Key', 'password-change-0001')
            ->withHeader('X-WNCMS-Step-Up', $proof)
            ->patchJson('/api/v2/backend/auth/password', [
                'current_password' => $this->password,
                'password' => 'New-Strong-Password-456!',
                'password_confirmation' => 'New-Strong-Password-456!',
            ])->assertOk()->assertJsonPath('data.reauthentication_required', true);

        $this->assertTrue(Hash::check('New-Strong-Password-456!', (string) $this->user->fresh()->password));
        $this->assertNull($this->user->fresh()->api_token);
        $this->assertNotNull(DB::table('api_sessions')->where('id', $sessionId)->value('revoked_at'));
        $this->assertNotNull(ApiServiceToken::query()->where('token_id', $material['public_id'])->value('revoked_at'));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $ownedPat]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherPat]);
        $this->withToken($login['access_token'])->getJson('/api/v2/backend/auth/me')->assertUnauthorized();
        $this->withToken($material['plain_text'])->getJson('/api/v2/backend/auth/me')->assertUnauthorized();
    }

    public function test_forgot_is_generic_and_reset_token_is_single_use(): void
    {
        $this->postJson('/api/v2/backend/auth/password/forgot', ['email' => 'missing@example.test'])->assertAccepted();
        $this->postJson('/api/v2/backend/auth/password/forgot', ['email' => $this->user->email])->assertAccepted();
        $token = null;
        Notification::assertSentTo($this->user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            parse_str((string) parse_url($notification->toMail($this->user)->actionUrl, PHP_URL_QUERY), $query);
            $token = $query['token'] ?? null;
            return $query['flow'] === 'password_reset';
        });
        $this->assertIsString($token);
        $payload = ['email' => $this->user->email, 'token' => $token, 'password' => 'Reset-Password-789!', 'password_confirmation' => 'Reset-Password-789!'];
        $this->postJson('/api/v2/backend/auth/password/reset', $payload)->assertOk()->assertJsonPath('data.reauthentication_required', true);
        $this->postJson('/api/v2/backend/auth/password/reset', $payload)->assertUnprocessable();
    }

    public function test_email_change_keeps_old_address_until_single_use_confirmation(): void
    {
        $login = $this->login();
        $oldEmail = $this->user->email;
        $newEmail = 'changed-'.uniqid().'@example.test';
        $this->suspendTransaction();
        $proof = $this->proof($login['access_token'], 'backend.auth.email.change', 'email.change');
        $this->withToken($login['access_token'])
            ->withHeader('Idempotency-Key', 'email-change-0001')
            ->withHeader('X-WNCMS-Step-Up', $proof)
            ->postJson('/api/v2/backend/auth/email/change', ['email' => $newEmail])->assertAccepted();
        $this->assertSame($oldEmail, $this->user->fresh()->email);

        $token = null;
        Notification::assertSentOnDemand(ApiSecurityLink::class, function (ApiSecurityLink $notification, array $channels, object $notifiable) use (&$token, $newEmail): bool {
            if (($notifiable->routes['mail'] ?? null) !== $newEmail) return false;
            parse_str((string) parse_url($notification->toMail($notifiable)->actionUrl, PHP_URL_QUERY), $query);
            $token = $query['token'] ?? null;
            return ($query['flow'] ?? null) === 'email_change';
        });
        $this->assertIsString($token);
        $this->postJson('/api/v2/backend/auth/email/change/confirm', ['token' => $token])->assertOk();
        $this->assertSame($newEmail, $this->user->fresh()->email);
        $this->assertNotNull($this->user->fresh()->email_verified_at);
        $this->postJson('/api/v2/backend/auth/email/change/confirm', ['token' => $token])->assertUnprocessable();
    }

    public function test_email_verification_credential_is_hash_only_expiring_and_single_use(): void
    {
        $this->user->forceFill(['email_verified_at' => null])->save();
        $login = $this->login();
        $this->withToken($login['access_token'])
            ->postJson('/api/v2/backend/auth/email-verification/send')
            ->assertAccepted();

        $token = null;
        Notification::assertSentTo($this->user, ApiSecurityLink::class, function (ApiSecurityLink $notification) use (&$token): bool {
            parse_str((string) parse_url($notification->toMail($this->user)->actionUrl, PHP_URL_QUERY), $query);
            $token = $query['token'] ?? null;
            return ($query['flow'] ?? null) === 'email_verification';
        });
        $this->assertIsString($token);
        $this->assertStringNotContainsString($token, json_encode(Cache::store('array')->getStore()->all(false), JSON_THROW_ON_ERROR));
        $this->postJson('/api/v2/backend/auth/email-verification/verify', ['token' => $token])->assertOk();
        $this->assertNotNull($this->user->fresh()->email_verified_at);
        $this->postJson('/api/v2/backend/auth/email-verification/verify', ['token' => $token])->assertUnprocessable();
    }

    /** @return array<string, mixed> */
    private function login(): array
    {
        auth()->forgetGuards();
        return $this->postJson('/api/v2/backend/auth/login', [
            'email' => $this->user->email, 'password' => $this->password, 'device_name' => 'security-tests',
        ])->assertOk()->json('data');
    }

    private function proof(string $access, string $operation, string $purpose): string
    {
        return (string) $this->withToken($access)->postJson('/api/v2/backend/auth/reauthenticate', [
            'password' => $this->password, 'operation' => $operation, 'purpose' => $purpose,
        ])->assertOk()->json('data.proof');
    }

    private function suspendTransaction(): void
    {
        while (DB::transactionLevel() > 0) DB::commit();
        $this->suspended = true;
    }
}
