<?php

namespace Wncms\Api\V2\Data;

final class QueryOptions
{
    /**
     * Create normalized list query options.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @param  string|null  $keyword
     * @param  array<string, mixed>  $filters
     * @param  string|null  $sort
     * @param  string  $direction
     * @param  array<int, string>  $includes
     * @param  array<int, string>  $fields
     *
     * @return void
     */
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly ?string $keyword = null,
        public readonly array $filters = [],
        public readonly ?string $sort = null,
        public readonly string $direction = 'asc',
        public readonly array $includes = [],
        public readonly array $fields = [],
    ) {}
}
