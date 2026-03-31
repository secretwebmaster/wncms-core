<?php

namespace Wncms\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Wncms\Http\Middleware\HasWebsite;
use Wncms\Http\Middleware\IsInstalled;
use Wncms\Models\Menu;
use Wncms\Models\MenuItem;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class MenuSourceControllerTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([HasWebsite::class, IsInstalled::class]);

        MenuItem::query()->delete();
        Menu::query()->delete();

        $unique = uniqid('menu_test_', true);
        $this->user = User::create([
            'username' => $unique,
            'email' => $unique . '@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
        Gate::define('menu_edit', fn (User $user) => $user->is($this->user));
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Event::forget('wncms.backend.menus.sources.resolve');
        MenuItem::query()->delete();
        Menu::query()->delete();

        parent::tearDown();
    }

    public function test_edit_view_exposes_registered_menu_sources(): void
    {
        $menu = Menu::create(['name' => 'Main Menu']);
        $this->registerMenuSource();

        $response = app(\Wncms\Http\Controllers\Backend\MenuController::class)->edit($menu->id);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $menuSources = $response->getData()['menuSources'] ?? [];
        $this->assertTrue((function (array $menuSources) {
            return isset($menuSources['linked_menus'])
                && $menuSources['linked_menus']['label'] === 'Linked Menus'
                && $menuSources['linked_menus']['type'] === 'model_search';
        })($menuSources));

        $this->assertSame('wncms::backend.menus.edit', $response->getName());
    }

    public function test_search_source_items_returns_limited_filtered_results(): void
    {
        Menu::create(['name' => 'Main Menu']);
        Menu::create(['name' => 'Alpha One']);
        Menu::create(['name' => 'Alpha Two']);
        Menu::create(['name' => 'Alpha Three']);
        Menu::create(['name' => 'Beta One']);

        $this->registerMenuSource(resultLimit: 2);

        $response = $this->post(route('menus.search_source_items'), [
            'source_key' => 'linked_menus',
            'keyword' => 'Alpha',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $this->assertCount(2, $response->json('items'));
        $this->assertSame('linked_menus', $response->json('items.0.type'));
        $this->assertSame('menu', $response->json('items.0.model_type'));
    }

    public function test_search_source_items_returns_not_found_for_unknown_source(): void
    {
        $response = $this->post(route('menus.search_source_items'), [
            'source_key' => 'unknown_source',
            'keyword' => 'Alpha',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('status', 'fail');
    }

    public function test_search_source_items_requires_menu_edit_permission(): void
    {
        $unique = uniqid('menu_test_other_', true);
        $otherUser = User::create([
            'username' => $unique,
            'email' => $unique . '@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($otherUser);

        $response = $this->post(route('menus.search_source_items'), [
            'source_key' => 'linked_menus',
            'keyword' => 'Alpha',
        ]);

        $response->assertForbidden();
    }

    public function test_update_stores_model_identity_without_forcing_untitled_name(): void
    {
        $menu = Menu::create(['name' => 'Main Menu']);
        $linkedMenu = Menu::create(['name' => 'Source Menu']);
        $this->registerMenuSource();

        $response = $this->patch(route('menus.update', ['id' => $menu->id]), [
            'name' => 'Main Menu',
            'new_menu' => json_encode([
                [
                    'id' => null,
                    'name' => '',
                    'type' => 'linked_menus',
                    'modelType' => 'menu',
                    'modelId' => (string) $linkedMenu->id,
                    'url' => null,
                    'is_new_window' => 0,
                    'children' => [],
                ],
            ]),
        ]);

        $response->assertRedirect(route('menus.edit', ['id' => $menu->id]));

        $menuItem = $menu->menu_items()->first();

        $this->assertNotNull($menuItem);
        $this->assertSame('menu', $menuItem->model_type);
        $this->assertSame((string) $linkedMenu->id, (string) $menuItem->model_id);
        $this->assertNull($menuItem->url);
        $this->assertNull($menuItem->getRawOriginal('name'));
        $this->assertSame('Source Menu', $menuItem->resolved_name);
    }

    protected function registerMenuSource(int $resultLimit = 20): void
    {
        Event::listen('wncms.backend.menus.sources.resolve', function (&$sources, $request) use ($resultLimit) {
            $sources[] = [
                'key' => 'linked_menus',
                'label' => 'Linked Menus',
                'type' => 'model_search',
                'model_class' => Menu::class,
                'model_key' => 'menu',
                'search_fields' => ['name'],
                'result_limit' => $resultLimit,
                'query' => fn ($query) => $query->orderBy('id'),
                'label_resolver' => fn (Menu $menu) => (string) $menu->getRawOriginal('name'),
                'url_resolver' => fn (Menu $menu) => '/linked-menus/' . $menu->id,
            ];
        });
    }
}
