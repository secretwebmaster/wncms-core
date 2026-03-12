<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Wncms\Http\Controllers\Backend\LinkController as BackendLinkController;
use Wncms\Models\Link;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class LinkHookIntegrationTest extends TestCase
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
        config(['media-library.media_model' => \Spatie\MediaLibrary\MediaCollections\Models\Media::class]);
        View::addLocation(__DIR__ . '/../Fixtures/views');
        View::share('errors', new ViewErrorBag);

        $user = User::query()->firstOrCreate(
            ['email' => 'link-hooks@example.com'],
            [
                'username' => 'link-hooks',
                'password' => bcrypt('password'),
            ]
        );
        $this->actingAs($user);
    }

    public function test_index_query_before_hook_can_modify_list_query(): void
    {
        DB::table('links')->insert([
            [
                'status' => 'active',
                'tracking_code' => 'active-code',
                'slug' => 'active-link',
                'name' => 'Active Link',
                'url' => 'https://example.com/active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'status' => 'inactive',
                'tracking_code' => 'inactive-code',
                'slug' => 'inactive-link',
                'name' => 'Inactive Link',
                'url' => 'https://example.com/inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Event::listen('wncms.backend.links.index.query.before', function ($request, &$q): void {
            $q->where('status', 'active');
        });

        $request = Request::create('/panel/links', 'GET');
        $response = $this->app->make(BackendLinkController::class)->index($request);

        $this->assertSame(1, $response->getData()['links']->total());
        $this->assertSame('Active Link', $response->getData()['links']->first()?->name);
    }

    public function test_create_resolve_hook_can_change_view_and_params(): void
    {
        Event::listen('wncms.backend.links.create.resolve', function (&$view, &$params): void {
            $view = 'links.custom-create';
            $params['hooked'] = 'create';
        });

        $response = $this->get(route('links.create'));

        $response->assertOk();
        $response->assertSee('custom-create:create');
    }

    public function test_edit_resolve_hook_can_change_view_and_params(): void
    {
        $link = Link::create($this->getLinkData());

        Event::listen('wncms.backend.links.edit.resolve', function (&$view, &$params) use ($link): void {
            $view = 'links.custom-edit';
            $params['hooked'] = 'edit';
            $params['link_id'] = $link->id;
        });

        $response = $this->get(route('links.edit', $link->id));

        $response->assertOk();
        $response->assertSee('custom-edit:edit:' . $link->id);
    }

    public function test_store_hooks_can_mutate_attributes_and_run_after(): void
    {
        $beforeCalled = false;
        $attributesCalled = false;
        $afterCalled = false;
        $storedLinkId = null;

        Event::listen('wncms.backend.links.store.before', function ($request, &$rules, &$messages) use (&$beforeCalled): void {
            $beforeCalled = true;
            $rules['name'] = 'required';
        });

        Event::listen('wncms.backend.links.store.attributes.before', function ($request, &$attributes) use (&$attributesCalled): void {
            $attributesCalled = true;
            $attributes['remark'] = 'created-via-hook';
        });

        Event::listen('wncms.backend.links.store.after', function ($link) use (&$afterCalled, &$storedLinkId): void {
            $afterCalled = true;
            $storedLinkId = $link->id;
        });

        $response = $this->post(route('links.store'), $this->getLinkData());

        $link = Link::latest('id')->first();

        $response->assertRedirect(route('links.edit', ['id' => $link->id]));
        $this->assertTrue($beforeCalled);
        $this->assertTrue($attributesCalled);
        $this->assertTrue($afterCalled);
        $this->assertSame($link->id, $storedLinkId);
        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'remark' => 'created-via-hook',
        ]);
    }

    public function test_update_hooks_can_mutate_attributes_and_run_after(): void
    {
        $link = Link::create($this->getLinkData());
        $beforeCalled = false;
        $attributesCalled = false;
        $afterCalled = false;
        $updatedLinkId = null;

        Event::listen('wncms.backend.links.update.before', function ($hookLink, $request, &$rules, &$messages) use (&$beforeCalled, $link): void {
            $beforeCalled = true;
            $this->assertSame($link->id, $hookLink->id);
            $rules['name'] = 'required';
        });

        Event::listen('wncms.backend.links.update.attributes.before', function ($hookLink, $request, &$attributes) use (&$attributesCalled, $link): void {
            $attributesCalled = true;
            $this->assertSame($link->id, $hookLink->id);
            $attributes['remark'] = 'updated-via-hook';
        });

        Event::listen('wncms.backend.links.update.after', function ($hookLink) use (&$afterCalled, &$updatedLinkId): void {
            $afterCalled = true;
            $updatedLinkId = $hookLink->id;
        });

        $response = $this->patch(route('links.update', $link->id), $this->getLinkData([
            'name' => 'Updated Link',
        ]));

        $response->assertRedirect(route('links.edit', ['id' => $link->id]));
        $this->assertTrue($beforeCalled);
        $this->assertTrue($attributesCalled);
        $this->assertTrue($afterCalled);
        $this->assertSame($link->id, $updatedLinkId);
        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'name' => 'Updated Link',
            'remark' => 'updated-via-hook',
        ]);
    }

    public function test_create_and_edit_field_hooks_render_in_form(): void
    {
        $linkId = DB::table('links')->insertGetId([
            'status' => 'active',
            'tracking_code' => 'field-hook-code',
            'slug' => 'field-hook-link',
            'name' => 'Field Hook Link',
            'url' => 'https://example.com/field-hook',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $link = Link::query()->findOrFail($linkId);

        Event::listen('wncms.view.backend.links.create.fields', fn() => '<div id="hook-create-field">create-hook</div>');
        Event::listen('wncms.view.backend.links.edit.fields', fn() => '<div id="hook-edit-field">edit-hook</div>');

        $this->setCurrentRequestRouteName('links.create', '/panel/links/create');
        $createHtml = view('wncms::backend.links.form-items', [
            'link' => new Link,
            'statuses' => Link::STATUSES,
        ])->render();

        $this->setCurrentRequestRouteName('links.edit', '/panel/links/' . $link->id . '/edit');
        $editHtml = view('wncms::backend.links.form-items', [
            'link' => $link,
            'statuses' => Link::STATUSES,
        ])->render();

        $this->assertStringContainsString('hook-create-field', $createHtml);
        $this->assertStringContainsString('hook-edit-field', $editHtml);
    }

    public function test_link_store_and_update_still_work_without_hook_listeners(): void
    {
        $storeResponse = $this->post(route('links.store'), $this->getLinkData());
        $link = Link::latest('id')->first();

        $storeResponse->assertRedirect(route('links.edit', ['id' => $link->id]));

        $updateResponse = $this->patch(route('links.update', $link->id), $this->getLinkData([
            'name' => 'Updated Without Hooks',
        ]));

        $updateResponse->assertRedirect(route('links.edit', ['id' => $link->id]));
        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'name' => 'Updated Without Hooks',
        ]);
    }

    protected function getLinkData(array $overrides = []): array
    {
        $defaults = [
            'status' => 'active',
            'tracking_code' => 'tracking-code',
            'slug' => 'test-link',
            'name' => 'Test Link',
            'url' => 'https://example.com',
            'slogan' => 'Test slogan',
            'description' => 'Test description',
            'external_thumbnail' => 'https://example.com/thumb.jpg',
            'remark' => 'Test remark',
            'sort' => 10,
            'color' => '#ffffff',
            'background' => '#000000',
            'is_pinned' => 0,
            'is_recommended' => 0,
            'expired_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'hit_at' => now()->format('Y-m-d H:i:s'),
            'clicks' => 5,
            'contact' => '@testlink',
        ];

        return array_merge($defaults, $overrides);
    }

    protected function setCurrentRequestRouteName(string $routeName, string $uri): void
    {
        $request = Request::create($uri, 'GET');
        $request->setRouteResolver(function () use ($uri, $routeName) {
            $route = new \Illuminate\Routing\Route('GET', $uri, []);
            $route->name($routeName);

            return $route;
        });

        $this->app->instance('request', $request);
    }
}
