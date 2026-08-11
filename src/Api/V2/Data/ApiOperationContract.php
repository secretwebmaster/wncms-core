<?php

namespace Wncms\Api\V2\Data;

final class ApiOperationContract
{
    /**
     * Create an API operation contract.
     *
     * @param  string  $id
     * @param  string  $domain
     * @param  string  $surface
     * @param  string  $method
     * @param  string  $path
     * @param  string  $routeName
     * @param  string|null  $permission
     * @param  string|null  $ability
     * @param  bool  $websiteScoped
     * @param  string  $risk
     * @param  string  $implementation
     * @param  \Wncms\Api\V2\Data\ApiSchema  $request
     * @param  \Wncms\Api\V2\Data\ApiSchema  $response
     * @param  array<int, string>  $filters
     * @param  array<int, string>  $sorts
     * @param  array<int, string>  $includes
     * @param  array<int, string>  $fields
     * @param  bool  $idempotent
     * @return void
     */
    public function __construct(
        public readonly string $id,
        public readonly string $domain,
        public readonly string $surface,
        public readonly string $method,
        public readonly string $path,
        public readonly string $routeName,
        public readonly ?string $permission,
        public readonly ?string $ability,
        public readonly bool $websiteScoped,
        public readonly string $risk,
        public readonly string $implementation,
        public readonly ApiSchema $request,
        public readonly ApiSchema $response,
        public readonly array $filters = [],
        public readonly array $sorts = [],
        public readonly array $includes = [],
        public readonly array $fields = [],
        public readonly bool $idempotent = false,
    ) {
    }

    /**
     * Export the operation contract.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'surface' => $this->surface,
            'method' => $this->method,
            'path' => $this->path,
            'route_name' => $this->routeName,
            'permission' => $this->permission,
            'ability' => $this->ability,
            'website_scoped' => $this->websiteScoped,
            'risk' => $this->risk,
            'implementation' => $this->implementation,
            'request_schema' => $this->request->toArray(),
            'response_schema' => $this->response->toArray(),
            'filters' => $this->filters,
            'sorts' => $this->sorts,
            'includes' => $this->includes,
            'fields' => $this->fields,
            'idempotent' => $this->idempotent,
        ];
    }
}
