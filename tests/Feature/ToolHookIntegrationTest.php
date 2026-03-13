<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class ToolHookIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $databasePath = dirname(__DIR__, 2) . '/database/testing.sqlite';
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', $databasePath);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        View::addLocation(__DIR__ . '/../Fixtures/views');
        View::prependNamespace('wncms', __DIR__ . '/../Fixtures/wncms');
        View::share('errors', new ViewErrorBag);

        $user = User::query()->firstOrCreate(
            ['email' => 'tool-hooks@example.com'],
            [
                'username' => 'tool-hooks',
                'password' => bcrypt('password'),
            ]
        );

        $this->actingAs($user);
    }

    public function test_index_resolve_hook_can_change_view_and_params(): void
    {
        Event::listen('wncms.backend.tools.index.resolve', function (&$view, &$params, $request): void {
            $view = 'tools.custom-index';
            $params['hooked'] = 'tools';
            $params['request_path'] = $request->path();
        });

        $response = $this->get(route('tools.index'));

        $response->assertOk();
        $response->assertSee('custom-tools-index:tools:panel/tools');
    }

    public function test_index_cards_hook_renders_in_tools_view(): void
    {
        Event::listen('wncms.view.backend.tools.index.cards', function (): string {
            return '<div class="col-12 col-md-6 col-lg-3 d-flex" id="hook-tool-card"><div class="tool-item card d-flex flex-column p-3 h-100 w-100">tool-card-hook</div></div>';
        });

        $response = $this->get(route('tools.index'));

        $response->assertOk();
        $response->assertSee('hook-tool-card', false);
        $response->assertSee('tool-card-hook');
    }
}
