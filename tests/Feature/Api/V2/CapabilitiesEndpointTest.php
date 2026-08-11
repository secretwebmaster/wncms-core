<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Contracts\ApiContractProvider;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class CapabilitiesEndpointTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Prepare API authentication and permissions for each capability test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        auth()->forgetGuards();
        app(PermissionRegistrar::class)->registerPermissions(Gate::getFacadeRoot());
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
    }

    /**
     * Verify capability discovery rejects missing and invalid credentials.
     */
    public function test_capabilities_require_a_valid_access_token(): void
    {
        $missing = $this->getJson('/api/v2/capabilities');
        $missing
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.missing_token');
        $this->assertAutomationEnvelope($missing);

        $invalid = $this->withToken('invalid-token')->getJson('/api/v2/capabilities');
        $invalid
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.invalid_token');
        $this->assertAutomationEnvelope($invalid);
    }

    /**
     * Verify discovery exposes only operations permitted for the current actor.
     */
    public function test_capabilities_filter_operations_by_the_authenticated_user_permissions(): void
    {
        $website = Website::firstOrFail();
        [, $token] = $this->tokenUser(['link_index'], $website);

        $response = $this->withToken($token)->getJson('/api/v2/capabilities');

        $response
            ->assertOk()
            ->assertJsonPath('data.schema_version', '2.0.0')
            ->assertJsonPath('data.domains.links.key', 'links');
        $operations = $response->json('data.domains.links.operations');
        $this->assertTrue($operations['backend.links.index']['available']);
        $this->assertSame('link_index', $operations['backend.links.index']['permission']);
        $this->assertTrue($operations['backend.links.index']['website_scoped']);
        $this->assertSame([], $operations['backend.links.index']['disabled_reasons']);
        $this->assertArrayNotHasKey('backend.links.show', $operations);
        $this->assertSame(
            [
                'method',
                'path',
                'permission',
                'ability',
                'website_scoped',
                'risk',
                'implementation',
                'idempotent',
                'filters',
                'sorts',
                'includes',
                'fields',
                'available',
                'disabled_reasons',
                'request_schema',
                'response_schema',
            ],
            array_keys($operations['backend.links.index'])
        );
        $this->assertAutomationEnvelope($response);
        $this->assertTrue(Route::has('api.v2.capabilities'));
    }

    /**
     * Verify website-scoped operations explain unavailable current context.
     */
    public function test_authorized_website_scoped_operations_remain_visible_without_a_website(): void
    {
        $website = Website::firstOrFail();
        [, $token] = $this->tokenUser(['link_index'], $website);

        Website::query()->delete();
        wncms()->cache()->flush(['websites']);

        $response = $this->withToken($token)->getJson('/api/v2/capabilities');

        $response->assertOk();
        $operation = $response->json('data.domains.links.operations')['backend.links.index'];
        $this->assertFalse($operation['available']);
        $this->assertSame(['website.context_missing'], $operation['disabled_reasons']);
        $this->assertAutomationEnvelope($response);
    }

    /**
     * Verify providers added to the container registry appear without route changes.
     */
    public function test_container_registered_provider_operations_appear_dynamically(): void
    {
        $website = Website::firstOrFail();
        [, $token] = $this->tokenUser([], $website);

        config(['wncms-api-v2.providers' => [CapabilitiesEndpointTestProvider::class]]);
        app()->forgetInstance(ApiContractRegistry::class);

        $response = $this->withToken($token)->getJson('/api/v2/capabilities');

        $response
            ->assertOk()
            ->assertJsonPath('data.domains.plugin_demo.key', 'plugin_demo')
            ->assertJsonPath('data.domains.plugin_demo.label', 'Plugin Demo');
        $operation = $response->json('data.domains.plugin_demo.operations')['plugin.demo.inspect'];
        $this->assertTrue($operation['available']);
        $this->assertSame(['status'], $operation['filters']);
        $this->assertSame(['id'], $operation['sorts']);
        $this->assertSame(['owner'], $operation['includes']);
        $this->assertSame(['id', 'name'], $operation['fields']);
        $this->assertAutomationEnvelope($response);
    }

    /**
     * Create a token user with selected permissions and website access.
     *
     * @param  array<int, string>  $permissions
     * @return array{0: \Wncms\Models\User, 1: string}
     */
    protected function tokenUser(array $permissions, Website $website): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $password = 'capabilities-password';
        $user = User::create([
            'username' => 'capabilities-user-'.uniqid(),
            'email' => 'capabilities-user-'.uniqid().'@example.com',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('member');
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }
        $user->websites()->syncWithoutDetaching([$website->id]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->postJson('/api/v2/backend/auth/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'capabilities-test',
        ]);
        $response->assertOk();
        auth()->forgetGuards();

        return [$user, (string) $response->json('data.token')];
    }

    /**
     * Assert the stable automation envelope keys in canonical order.
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     */
    protected function assertAutomationEnvelope($response): void
    {
        $this->assertSame(
            ['code', 'status', 'message', 'data', 'meta', 'errors'],
            array_keys($response->json())
        );
        $this->assertSame(
            $response->headers->get('X-Request-ID'),
            $response->json('meta.request_id')
        );
    }
}

class CapabilitiesEndpointTestProvider implements ApiContractProvider
{
    /**
     * Register a plugin-style test contract.
     */
    public function register(ApiContractRegistry $registry): void
    {
        $registry->registerDomain(new ApiDomainContract('plugin_demo', 'Plugin Demo'));
        $registry->registerOperation(new ApiOperationContract(
            id: 'plugin.demo.inspect',
            domain: 'plugin_demo',
            surface: 'plugin',
            method: 'GET',
            path: '/api/v2/plugin-demo',
            routeName: 'api.v2.plugin_demo.inspect',
            permission: null,
            ability: 'plugin-demo:read',
            websiteScoped: false,
            risk: 'read',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
            filters: ['status'],
            sorts: ['id'],
            includes: ['owner'],
            fields: ['id', 'name'],
        ));
    }
}
