<?php

namespace Wncms\Tests\Unit;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\ApiContractValidator;
use Wncms\Api\V2\Contracts\ApiContractProvider;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Exceptions\ApiContractException;
use Wncms\Tests\TestCase;

class CheckBackendApiV2ParityCommandTest extends TestCase
{
    /**
     * Verify the runtime registry, routes, and OpenAPI document pass the contract check.
     *
     * @return void
     */
    public function test_it_outputs_the_valid_api_v2_contract_as_json(): void
    {
        $exitCode = Artisan::call('wncms:check-backend-api-v2-parity', [
            '--contract' => true,
            '--json' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame('success', $decoded['status']);
        $this->assertSame('api-v2-contract', $decoded['meta']['mode']);
        $this->assertGreaterThan(0, $decoded['data']['operation_count']);
        $this->assertSame([], $decoded['data']['errors']);
        $this->assertIsArray($decoded['data']['warnings']);
    }

    /**
     * Verify invalid contract fixtures return exit one with grouped machine errors.
     *
     * @return void
     */
    public function test_contract_mode_returns_machine_readable_errors_and_exit_one(): void
    {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('posts', 'Posts'));
        $registry->registerOperation(new ApiOperationContract(
            id: 'backend.posts.update',
            domain: 'posts',
            surface: 'backend',
            method: 'PATCH',
            path: '/api/v2/backend/posts/{id}',
            routeName: 'api.v2.backend.posts.update',
            permission: null,
            ability: null,
            websiteScoped: true,
            risk: 'write',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
        ));
        $routes = new RouteCollection();
        $route = new Route(['PUT'], 'api/v2/backend/posts/{id}', static fn (): null => null);
        $route->name('api.v2.backend.posts.update');
        $routes->add($route);
        app()->instance(ApiContractValidator::class, new ApiContractValidator(
            $registry,
            $routes,
            [
                'openapi' => '3.1.0',
                'paths' => [
                    '/api/v2/backend/posts/{id}' => [
                        'patch' => ['operationId' => 'backend.posts.update'],
                    ],
                ],
            ],
        ));

        $exitCode = Artisan::call('wncms:check-backend-api-v2-parity', [
            '--contract' => true,
            '--json' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);
        $this->assertSame(1, $exitCode);
        $this->assertSame('fail', $decoded['status']);
        $this->assertSame('api-v2-contract', $decoded['meta']['mode']);
        $this->assertArrayHasKey('contract.permission_missing', $decoded['data']['errors']);
        $this->assertArrayHasKey('route.method_mismatch', $decoded['data']['errors']);
        $this->assertSame($decoded['data']['errors'], $decoded['errors']);
    }

    /**
     * Verify registry and OpenAPI bootstrap exceptions remain machine-readable.
     *
     * @param  class-string<\Wncms\Api\V2\Contracts\ApiContractProvider>  $providerClass
     * @return void
     */
    #[DataProvider('contractBootstrapFailureProvider')]
    public function test_contract_mode_wraps_registry_and_openapi_bootstrap_failures(string $providerClass): void
    {
        config(['wncms-api-v2.providers' => [$providerClass]]);
        app()->forgetInstance(ApiContractRegistry::class);

        $exitCode = Artisan::call('wncms:check-backend-api-v2-parity', [
            '--contract' => true,
            '--json' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);

        $expectedErrors = [
            'contract.bootstrap_failed' => [
                [
                    'exception_class' => ApiContractException::class,
                    'reason' => 'API v2 contract dependencies could not be constructed.',
                ],
            ],
        ];

        $this->assertSame(1, $exitCode);
        $this->assertSame('fail', $decoded['status']);
        $this->assertSame('api-v2-contract', $decoded['meta']['mode']);
        $this->assertSame(0, $decoded['data']['operation_count']);
        $this->assertFalse($decoded['data']['v7_parity_eligible']);
        $this->assertSame([], $decoded['data']['warnings']);
        $this->assertSame($expectedErrors, $decoded['data']['errors']);
        $this->assertSame($expectedErrors, $decoded['errors']);
    }

    /**
     * Provide real contract providers that fail during container bootstrap.
     *
     * @return array<string, array{class-string<\Wncms\Api\V2\Contracts\ApiContractProvider>}>
     */
    public static function contractBootstrapFailureProvider(): array
    {
        return [
            'duplicate domain' => [DuplicateDomainContractProvider::class],
            'duplicate operation id' => [DuplicateOperationContractProvider::class],
            'OpenAPI method and path collision' => [CollidingOpenApiContractProvider::class],
        ];
    }

    /**
     * Verify adding contract mode does not change the default parity JSON shape.
     *
     * @return void
     */
    public function test_default_parity_mode_remains_backward_compatible(): void
    {
        $exitCode = Artisan::call('wncms:check-backend-api-v2-parity', [
            '--json' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(1, $exitCode);
        $this->assertSame('fail', $decoded['status']);
        $this->assertSame('backend-api-v2-parity', $decoded['meta']['mode']);
        $this->assertSame(['links.bulk_delete'], $decoded['data']['missing_backend_route_names']);
    }

    public function test_it_outputs_v7_ai_first_coverage_as_json(): void
    {
        $exitCode = Artisan::call('wncms:check-backend-api-v2-parity', [
            '--coverage' => true,
            '--json' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($decoded);
        $this->assertSame('success', $decoded['status']);
        $this->assertSame('v7-ai-first-coverage', $decoded['meta']['mode']);
        $this->assertSame('links', $decoded['data']['reference_domain']);

        $domains = $this->indexDomainsByKey($decoded['data']['domains']);

        $this->assertArrayHasKey('links', $domains);
        $this->assertTrue($domains['links']['reference']);
        $this->assertSame('Complete', $domains['links']['surfaces']['backend_ui']['status']);
        $this->assertSame('Partial', $domains['links']['surfaces']['api_v2']['status']);
        $this->assertSame('Complete', $domains['links']['surfaces']['cli']['status']);
        $this->assertSame('Complete', $domains['links']['surfaces']['docs']['status']);
        $this->assertSame('Complete', $domains['links']['surfaces']['tests']['status']);
        $this->assertContains('documentations/manual/developer/command/overview.md', $domains['links']['surfaces']['docs']['found']);
        $this->assertContains('tests/Feature/LinkAutomationCommandTest.php', $domains['links']['surfaces']['tests']['found']);
        $this->assertContains('tests/Feature/LinkApiV2ControllerTest.php', $domains['links']['surfaces']['tests']['found']);
        $this->assertContains('tests/Feature/Mcp/LinksToolsTest.php', $domains['links']['surfaces']['tests']['found']);
        $this->assertContains('documentations/manual/developer/mcp/overview.md', $domains['links']['surfaces']['docs']['found']);
        $this->assertContains('documentations/manual/zh-CN/developer/mcp/overview.md', $domains['links']['surfaces']['docs']['found']);
        $this->assertContains('documentations/manual/zh-TW/developer/mcp/overview.md', $domains['links']['surfaces']['docs']['found']);
        $this->assertSame('Complete', $domains['links']['surfaces']['mcp']['status']);
        $this->assertSame([
            'wncms-links-list',
            'wncms-links-inspect',
        ], $domains['links']['surfaces']['mcp']['found']);

        $this->assertArrayHasKey('api_v2_backend_resources', $domains);
        $this->assertSame('Not applicable', $domains['api_v2_backend_resources']['surfaces']['backend_ui']['status']);
        $this->assertSame('Complete', $domains['api_v2_backend_resources']['surfaces']['cli']['status']);
        $this->assertSame('Needs design', $domains['api_v2_backend_resources']['surfaces']['mcp']['status']);
    }

    /**
     * Index decoded coverage domains by domain key.
     *
     * @param array $domains
     * @return array
     */
    protected function indexDomainsByKey(array $domains): array
    {
        $indexed = [];

        foreach ($domains as $domain) {
            $indexed[(string) $domain['key']] = $domain;
        }

        return $indexed;
    }
}

final class DuplicateDomainContractProvider implements ApiContractProvider
{
    /**
     * Register a duplicate domain fixture.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @return void
     */
    public function register(ApiContractRegistry $registry): void
    {
        $registry->registerDomain(new ApiDomainContract('posts', 'Posts'));
        $registry->registerDomain(new ApiDomainContract('posts', 'Duplicate Posts'));
    }
}

trait BuildsCommandApiOperation
{
    /**
     * Build one read operation fixture.
     *
     * @param  string  $id
     * @param  string  $path
     * @param  string  $routeName
     * @return \Wncms\Api\V2\Data\ApiOperationContract
     */
    private function operation(string $id, string $path, string $routeName): ApiOperationContract
    {
        return new ApiOperationContract(
            id: $id,
            domain: 'posts',
            surface: 'backend',
            method: 'GET',
            path: $path,
            routeName: $routeName,
            permission: 'post_show',
            ability: null,
            websiteScoped: true,
            risk: 'read',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
        );
    }
}

final class DuplicateOperationContractProvider implements ApiContractProvider
{
    use BuildsCommandApiOperation;

    /**
     * Register a duplicate operation identifier fixture.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @return void
     */
    public function register(ApiContractRegistry $registry): void
    {
        $registry->registerDomain(new ApiDomainContract('posts', 'Posts'));
        $operation = $this->operation(
            'backend.posts.show',
            '/api/v2/backend/posts/{id}',
            'api.v2.backend.posts.show',
        );
        $registry->registerOperation($operation);
        $registry->registerOperation($operation);
    }

}

final class CollidingOpenApiContractProvider implements ApiContractProvider
{
    use BuildsCommandApiOperation;

    /**
     * Register two operations that collide in the OpenAPI path map.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @return void
     */
    public function register(ApiContractRegistry $registry): void
    {
        $registry->registerDomain(new ApiDomainContract('posts', 'Posts'));
        $registry->registerOperation($this->operation(
            'backend.posts.show',
            '/api/v2/backend/posts/{id}',
            'api.v2.backend.posts.show',
        ));
        $registry->registerOperation($this->operation(
            'backend.posts.inspect',
            '/api/v2/backend/posts/{id}',
            'api.v2.backend.posts.inspect',
        ));
    }
}
