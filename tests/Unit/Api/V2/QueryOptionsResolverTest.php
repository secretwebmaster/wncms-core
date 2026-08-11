<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Data\QueryOptions;
use Wncms\Api\V2\QueryOptionsResolver;
use Wncms\Tests\TestCase;

class QueryOptionsResolverTest extends TestCase
{
    public function test_it_normalizes_declared_query_options(): void
    {
        $request = Request::create('/api/v2/backend/posts', 'GET', [
            'page' => '2',
            'per_page' => '25',
            'keyword' => ' demo ',
            'filter' => ['status' => 'published'],
            'sort' => 'created_at',
            'direction' => 'DESC',
            'include' => 'tags, user',
            'fields' => 'id, title',
        ]);

        $options = (new QueryOptionsResolver)->resolve($request, $this->contract());

        $this->assertEquals(new QueryOptions(
            page: 2,
            perPage: 25,
            keyword: 'demo',
            filters: ['status' => 'published'],
            sort: 'created_at',
            direction: 'desc',
            includes: ['tags', 'user'],
            fields: ['id', 'title'],
        ), $options);
    }

    public function test_it_caps_per_page_at_one_hundred(): void
    {
        $request = Request::create('/api/v2/backend/posts', 'GET', ['per_page' => '101']);

        $options = (new QueryOptionsResolver)->resolve($request, $this->contract());

        $this->assertSame(100, $options->perPage);
    }

    public function test_it_defaults_optional_query_options(): void
    {
        $options = (new QueryOptionsResolver)->resolve(
            Request::create('/api/v2/backend/posts', 'GET'),
            $this->contract(),
        );

        $this->assertEquals(new QueryOptions, $options);
    }

    #[DataProvider('undeclaredOptionProvider')]
    public function test_it_rejects_undeclared_query_options(array $query): void
    {
        $this->expectException(ValidationException::class);

        (new QueryOptionsResolver)->resolve(
            Request::create('/api/v2/backend/posts', 'GET', $query),
            $this->contract(),
        );
    }

    /**
     * Provide undeclared query options.
     *
     * @return array<string, array{array<string, mixed>}>
     */
    public static function undeclaredOptionProvider(): array
    {
        return [
            'filter' => [['filter' => ['author' => '1']]],
            'sort' => [['sort' => 'title']],
            'include' => [['include' => 'comments']],
            'field' => [['fields' => 'id,body']],
        ];
    }

    /**
     * Create a contract fixture with declared query allowlists.
     */
    private function contract(): ApiOperationContract
    {
        return new ApiOperationContract(
            id: 'backend.posts.index',
            domain: 'posts',
            surface: 'backend',
            method: 'GET',
            path: '/api/v2/backend/posts',
            routeName: 'api.v2.backend.posts.index',
            permission: 'post_index',
            ability: 'posts:read',
            websiteScoped: true,
            risk: 'read',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
            filters: ['status'],
            sorts: ['created_at'],
            includes: ['tags', 'user'],
            fields: ['id', 'title'],
        );
    }
}
