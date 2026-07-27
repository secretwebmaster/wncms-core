<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\View;
use Wncms\Http\Middleware\HasWebsite;
use Wncms\Http\Middleware\IsInstalled;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([HasWebsite::class, IsInstalled::class]);
        View::prependNamespace('wncms', __DIR__ . '/../Fixtures/wncms');
    }

    public function test_manager_dashboard_receives_default_result_payload(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('wncms::backend.dashboards.manager_dashboard');
        $response->assertViewHas('result', []);
    }
}
