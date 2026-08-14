<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Wncms\Tests\TestCase;

class SecurityEventRetentionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_prunes_all_expired_severities_and_records_completion(): void
    {
        config([
            'wncms-api-v2.auth_security.security_event_correlation.active_key_version' => 'v1',
            'wncms-api-v2.auth_security.security_event_correlation.keys.v1' => [
                'ip' => str_repeat('i', 32), 'login_identifier' => str_repeat('l', 32), 'user_agent' => str_repeat('u', 32),
            ],
        ]);
        uss('api_security_event_retention_days', 30);
        foreach ([['old-normal', 'info', now()->subDays(31)], ['old-critical', 'critical', now()->subDays(31)], ['recent', 'info', now()]] as [$id, $severity, $date]) {
            DB::table('api_security_events')->insert([
                'event_id' => $id, 'occurred_at' => $date, 'event_type' => 'auth.login.failed', 'severity' => $severity,
                'outcome' => 'denied', 'surface' => 'api_v2', 'correlation_key_version' => 'v1',
                'created_at' => $date, 'updated_at' => $date,
            ]);
        }

        $this->artisan('wncms:auth:prune-security-events', ['--json' => true])->assertSuccessful();
        $this->assertDatabaseMissing('api_security_events', ['event_id' => 'old-normal']);
        $this->assertDatabaseMissing('api_security_events', ['event_id' => 'old-critical']);
        $this->assertDatabaseHas('api_security_events', ['event_id' => 'recent']);
        $this->assertDatabaseHas('api_security_events', ['event_type' => 'security.retention.completed']);
    }

    public function test_retention_command_is_scheduled_daily_without_overlap(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'wncms:auth:prune-security-events'));
        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }
}
