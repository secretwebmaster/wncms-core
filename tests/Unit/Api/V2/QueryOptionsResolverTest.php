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

    /**
     * Verify pagination inputs cannot wrap or coerce outside positive native integers.
     */
    #[DataProvider('invalidPositiveIntegerProvider')]
    public function test_it_rejects_invalid_or_overflowing_positive_integer_options(
        string $field,
        mixed $value
    ): void {
        try {
            (new QueryOptionsResolver)->resolve(
                Request::create('/api/v2/backend/posts', 'GET', [$field => $value]),
                $this->contract(),
            );
            $this->fail("{$field} should reject invalid positive integer input.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }

    /**
     * Provide invalid pagination values, including decimal strings beyond PHP_INT_MAX.
     *
     * @return array<string, array{string, mixed}>
     */
    public static function invalidPositiveIntegerProvider(): array
    {
        $overflow = (string) PHP_INT_MAX.'0';

        return [
            'page zero' => ['page', '0'],
            'page negative' => ['page', '-1'],
            'page decimal' => ['page', '1.5'],
            'page array' => ['page', ['1']],
            'page overflow' => ['page', $overflow],
            'per-page zero' => ['per_page', 0],
            'per-page negative' => ['per_page', -1],
            'per-page decimal' => ['per_page', '20.5'],
            'per-page array' => ['per_page', ['20']],
            'per-page overflow' => ['per_page', $overflow],
        ];
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
