<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\StepUpException;
use Wncms\Auth\Api\V2\StepUpService;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class StepUpAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => 'v1',
            'keys' => ['v1' => [
                'ip' => 'step-up-ip-key-12345678901234567890',
                'login_identifier' => 'step-up-login-key-1234567890123456',
                'user_agent' => 'step-up-agent-key-1234567890123456',
            ]],
        ]]);
        CarbonImmutable::setTestNow('2026-08-14 00:00:00 UTC');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_proof_is_hash_only_session_and_purpose_bound_and_single_use(): void
    {
        [$session, $context] = $this->sessionContext();
        $proof = app(StepUpService::class)->issue($session, ['service_token.create']);

        $this->assertStringNotContainsString($proof, json_encode($session->fresh()->getAttributes(), JSON_THROW_ON_ERROR));
        $this->expectStepUpCode('risk.step_up_invalid', fn () => app(StepUpService::class)->consume($context, $proof, 'password.change'));

        app(StepUpService::class)->consume($context, $proof, 'service_token.create');
        $this->expectStepUpCode('risk.step_up_invalid', fn () => app(StepUpService::class)->consume($context, $proof, 'service_token.create'));
    }

    public function test_proof_expires_and_is_invalidated_by_password_security_event(): void
    {
        [$session, $context] = $this->sessionContext();
        $proof = app(StepUpService::class)->issue($session, ['password.change']);

        DB::table('api_security_events')->insert([
            'event_id' => 'password-event-'.uniqid(),
            'occurred_at' => now(),
            'event_type' => 'auth.password.changed',
            'severity' => 'critical',
            'outcome' => 'succeeded',
            'surface' => 'api_v2',
            'actor_id' => $context->actorId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->expectStepUpCode('risk.step_up_invalid', fn () => app(StepUpService::class)->consume($context, $proof, 'password.change'));

        $proof = app(StepUpService::class)->issue($session->fresh(), ['password.change']);
        CarbonImmutable::setTestNow('2026-08-14 00:05:01 UTC');
        $this->expectStepUpCode('risk.step_up_expired', fn () => app(StepUpService::class)->consume($context, $proof, 'password.change'));
    }

    public function test_service_credentials_cannot_issue_or_consume_step_up_proofs(): void
    {
        [$session, $context] = $this->sessionContext();
        $proof = app(StepUpService::class)->issue($session, ['service_token.create']);
        $serviceContext = new AuthenticationContext($context->actor(), ApiCredential::TYPE_SERVICE_TOKEN, 'service-public-id', null, ['tokens.create'], [1]);

        $this->expectStepUpCode('risk.credential_type_denied', fn () => app(StepUpService::class)->consume($serviceContext, $proof, 'service_token.create'));
    }

    public function test_proof_reservation_blocks_replay_releases_on_failure_and_consumes_after_success(): void
    {
        [$session, $context] = $this->sessionContext();
        $proof = app(StepUpService::class)->issue($session, ['service_token.create']);
        $reservation = app(StepUpService::class)->reserve($context, $proof, 'service_token.create');

        $this->expectStepUpCode('risk.step_up_invalid', fn () => app(StepUpService::class)->reserve($context, $proof, 'service_token.create'));
        app(StepUpService::class)->releaseReservation($reservation);
        $retry = app(StepUpService::class)->reserve($context, $proof, 'service_token.create');
        app(StepUpService::class)->confirmReservation($retry, $context);

        $this->expectStepUpCode('risk.step_up_invalid', fn () => app(StepUpService::class)->reserve($context, $proof, 'service_token.create'));
    }

    private function sessionContext(): array
    {
        $user = User::query()->firstOrFail();
        $session = ApiSession::create([
            'session_id' => 'STEPUP'.strtoupper(substr(hash('sha256', uniqid()), 0, 20)),
            'user_id' => $user->id,
            'refresh_transport' => 'json',
            'remembered' => false,
            'expires_at' => now()->addDay(),
        ]);
        $context = new AuthenticationContext($user, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'access-public-id', $session->session_id, ['tokens.create'], [1]);

        return [$session, $context];
    }

    private function expectStepUpCode(string $code, callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected step-up denial.');
        } catch (StepUpException $exception) {
            $this->assertSame($code, $exception->errorCode);
        }
    }
}
