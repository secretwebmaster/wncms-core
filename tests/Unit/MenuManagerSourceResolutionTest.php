<?php

namespace Wncms\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Wncms\Http\Resources\MenuItemResource;
use Wncms\Models\Menu;
use Wncms\Models\MenuItem;
use Wncms\Tests\TestCase;

class MenuManagerSourceResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MenuItem::query()->delete();
        Menu::query()->delete();
    }

    protected function tearDown(): void
    {
        Event::forget('wncms.backend.menus.sources.resolve');
        MenuItem::query()->delete();
        Menu::query()->delete();

        parent::tearDown();
    }

    public function test_it_resolves_linked_model_name_when_override_is_empty(): void
    {
        $linkedMenu = Menu::create(['name' => 'Alpha Source']);
        $menu = Menu::create(['name' => 'Main Menu']);
        $this->registerMenuSource();

        $menuItem = $menu->menu_items()->create([
            'type' => 'linked_menus',
            'model_type' => 'menu',
            'model_id' => $linkedMenu->id,
            'name' => null,
        ]);

        $this->assertSame('Alpha Source', wncms()->menu()->getMenuItemResolvedName($menuItem));
        $this->assertSame('Alpha Source', $menuItem->resolved_name);
    }

    public function test_it_prefers_custom_name_override_over_linked_model_name(): void
    {
        $linkedMenu = Menu::create(['name' => 'Alpha Source']);
        $menu = Menu::create(['name' => 'Main Menu']);
        $this->registerMenuSource();

        $menuItem = $menu->menu_items()->create([
            'type' => 'linked_menus',
            'model_type' => 'menu',
            'model_id' => $linkedMenu->id,
            'name' => 'Custom Override',
        ]);

        $this->assertSame('Custom Override', wncms()->menu()->getMenuItemResolvedName($menuItem));
    }

    public function test_it_resolves_linked_model_url_via_registered_source_resolver(): void
    {
        $linkedMenu = Menu::create(['name' => 'Alpha Source']);
        $menu = Menu::create(['name' => 'Main Menu']);
        $this->registerMenuSource();

        $menuItem = $menu->menu_items()->create([
            'type' => 'linked_menus',
            'model_type' => 'menu',
            'model_id' => $linkedMenu->id,
            'name' => null,
        ]);

        $this->assertNotNull(wncms()->menu()->getMenuSource('linked_menus'));
        $this->assertSame('/linked-menus/' . $linkedMenu->id, wncms()->menu()->getMenuItemUrl($menuItem));
    }

    public function test_it_builds_default_menu_sources_from_base_model_opt_in(): void
    {
        $sources = wncms()->menu()->resolveMenuSources();

        $this->assertArrayHasKey('page', $sources);
        $this->assertArrayHasKey('post', $sources);
        $this->assertArrayNotHasKey('menu', $sources);
        $this->assertSame('model_search', $sources['page']['type']);
        $this->assertSame('page', $sources['page']['model_key']);
        $this->assertSame('post', $sources['post']['model_key']);
    }

    public function test_hook_can_remove_default_menu_source(): void
    {
        Event::listen('wncms.backend.menus.sources.resolve', function (&$sources, $request) {
            $sources = collect($sources)
                ->reject(fn ($source) => ($source['key'] ?? null) === 'page')
                ->values()
                ->all();
        });

        $sources = wncms()->menu()->resolveMenuSources();

        $this->assertArrayNotHasKey('page', $sources);
        $this->assertArrayHasKey('post', $sources);
    }

    public function test_menu_item_resource_uses_resolved_name_and_url(): void
    {
        $linkedMenu = Menu::create(['name' => 'Alpha Source']);
        $menu = Menu::create(['name' => 'Main Menu']);
        $this->registerMenuSource();

        $menuItem = $menu->menu_items()->create([
            'type' => 'linked_menus',
            'model_type' => 'menu',
            'model_id' => $linkedMenu->id,
            'name' => null,
        ]);

        $resource = (new MenuItemResource($menuItem))->toArray(request());

        $this->assertSame('Alpha Source', $resource['name']);
        $this->assertNull($resource['name_override']);
        $this->assertSame('/linked-menus/' . $linkedMenu->id, $resource['url']);
    }

    protected function registerMenuSource(): void
    {
        Event::listen('wncms.backend.menus.sources.resolve', function (&$sources, $request) {
            $sources[] = [
                'key' => 'linked_menus',
                'label' => 'Linked Menus',
                'type' => 'model_search',
                'model_class' => Menu::class,
                'model_key' => 'menu',
                'search_fields' => ['name'],
                'result_limit' => 20,
                'query' => fn ($query) => $query->orderBy('id'),
                'label_resolver' => fn (Menu $menu) => (string) $menu->getRawOriginal('name'),
                'url_resolver' => fn (Menu $menu) => '/linked-menus/' . $menu->id,
            ];
        });
    }
}
