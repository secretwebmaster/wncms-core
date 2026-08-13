<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ResourceController extends ApiV2Controller
{
    protected function extractWebsiteBindingInput(Request $request): array|string|int|null
    {
        if ($request->has('website_ids')) {
            $ids = $request->input('website_ids');
            if ($ids !== [] && $ids !== null && $ids !== '') {
                return $ids;
            }
        }

        if ($request->has('website_id')) {
            return $request->input('website_id');
        }

        $key = trim((string) $request->input('website_key', ''));
        if ($key !== '' && preg_match('/^website:([1-9][0-9]*)$/D', $key, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function buildMutationPayload(Request $request): array
    {
        return $request->except(['website_id', 'website_ids', 'website_key']);
    }

    protected function resolveResourceConfig(string $resource): ?array
    {
        $config = config("wncms-backend-api-v2.resources.{$resource}");

        return is_array($config) ? $config : null;
    }

    protected function authorizeResourceAction(?string $permission): void
    {
        if (! empty($permission)) {
            abort_unless(auth()->user()?->can($permission), Response::HTTP_FORBIDDEN);
        }
    }

    public function index(Request $request, string $resource)
    {
        try {
            $config = $this->resolveResourceConfig($resource);
            if (! $config) {
                return $this->error('resource_not_supported', Response::HTTP_NOT_FOUND);
            }

            $this->authorizeResourceAction($config['permissions']['index'] ?? null);

            $modelClass = $this->resolveModelClass($config['model_key']);
            if (! $modelClass) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $q = $modelClass::query()->orderByDesc('id');
            $perPage = $this->normalizePerPage($request);
            $paginator = $q->paginate($perPage);

            return $this->ok($paginator->items(), 'success', Response::HTTP_OK, [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function show(Request $request, string $resource, int|string $id)
    {
        try {
            $config = $this->resolveResourceConfig($resource);
            if (! $config) {
                return $this->error('resource_not_supported', Response::HTTP_NOT_FOUND);
            }

            $this->authorizeResourceAction($config['permissions']['show'] ?? null);

            $modelClass = $this->resolveModelClass($config['model_key']);
            if (! $modelClass) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $model = $this->resolveModelOrFail($modelClass, $id);
            if (! $model) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            return $this->ok($model);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function store(Request $request, string $resource)
    {
        try {
            $config = $this->resolveResourceConfig($resource);
            if (! $config) {
                return $this->error('resource_not_supported', Response::HTTP_NOT_FOUND);
            }

            $this->authorizeResourceAction($config['permissions']['store'] ?? null);

            $modelClass = $this->resolveModelClass($config['model_key']);
            if (! $modelClass) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $websiteIds = $this->mutationWebsiteIds($request, $modelClass);
            $model = $modelClass::query()->create($this->buildMutationPayload($request));
            $this->syncModelWebsites(
                $model,
                $websiteIds,
            );

            return $this->ok($model, 'successfully_created', Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function update(Request $request, string $resource, int|string $id)
    {
        try {
            $config = $this->resolveResourceConfig($resource);
            if (! $config) {
                return $this->error('resource_not_supported', Response::HTTP_NOT_FOUND);
            }

            $this->authorizeResourceAction($config['permissions']['update'] ?? null);

            $modelClass = $this->resolveModelClass($config['model_key']);
            if (! $modelClass) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $model = $this->resolveModelOrFail($modelClass, $id);
            if (! $model) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $websiteIds = $this->mutationWebsiteIds($request, $modelClass);
            $model->update($this->buildMutationPayload($request));
            $this->syncModelWebsites(
                $model,
                $websiteIds,
            );

            return $this->ok($model, 'successfully_updated');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function destroy(Request $request, string $resource, int|string $id)
    {
        try {
            $config = $this->resolveResourceConfig($resource);
            if (! $config) {
                return $this->error('resource_not_supported', Response::HTTP_NOT_FOUND);
            }

            $this->authorizeResourceAction($config['permissions']['destroy'] ?? null);

            $modelClass = $this->resolveModelClass($config['model_key']);
            if (! $modelClass) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $model = $this->resolveModelOrFail($modelClass, $id);
            if (! $model) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $model->delete();

            return $this->ok(null, 'successfully_deleted');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function bulkDelete(Request $request, string $resource)
    {
        try {
            $config = $this->resolveResourceConfig($resource);
            if (! $config) {
                return $this->error('resource_not_supported', Response::HTTP_NOT_FOUND);
            }

            if (($config['enable_bulk_delete'] ?? true) === false) {
                return $this->error('bulk_delete_not_supported', Response::HTTP_NOT_FOUND);
            }

            $this->authorizeResourceAction($config['permissions']['bulk_delete'] ?? null);

            $modelClass = $this->resolveModelClass($config['model_key']);
            if (! $modelClass) {
                return $this->error('model_not_found', Response::HTTP_NOT_FOUND);
            }

            $modelIds = $request->input('model_ids', []);
            if (! is_array($modelIds)) {
                $modelIds = array_filter(explode(',', (string) $modelIds));
            }

            $count = $modelClass::query()->whereIn('id', $modelIds)->delete();

            return $this->ok(['deleted' => $count], 'successfully_deleted');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    /**
     * Resolve a non-empty website binding for website-scoped mutations.
     *
     * @return array<int, int>
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function mutationWebsiteIds(Request $request, string $modelClass): array
    {
        $websiteScoped = method_exists($modelClass, 'isWebsiteScopedModel') && $modelClass::isWebsiteScopedModel();
        if ($websiteScoped && $request->has('website_ids') && $request->input('website_ids') === []) {
            throw ValidationException::withMessages(['website_ids' => ['A website binding is required.']]);
        }
        $ids = $this->resolveModelWebsiteIds($modelClass, $this->extractWebsiteBindingInput($request));
        if ($websiteScoped && $ids === []) {
            throw ValidationException::withMessages(['website_ids' => ['A website binding is required.']]);
        }

        return $ids;
    }
}
