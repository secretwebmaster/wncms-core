<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Wncms\Services\Automation\AutomationResult;
use Wncms\Services\Automation\LinkAutomationService;
use Wncms\Services\Automation\MutationGuardService;

class LinkController extends ApiV2Controller
{
    /**
     * Create the Links API v2 transport adapter.
     *
     * @param  \Wncms\Services\Automation\LinkAutomationService  $service
     * @param  \Wncms\Services\Automation\MutationGuardService  $guard
     */
    public function __construct(
        protected LinkAutomationService $service,
        protected MutationGuardService $guard
    ) {
    }

    /**
     * List Links through the shared automation service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorizeLinkAction('index', $request);
            $validated = $request->validate([
                'status' => ['nullable', Rule::in(['active', 'inactive', 'all'])],
                'keyword' => ['nullable', 'string'],
                'website_id' => ['nullable', 'integer', 'min:1'],
                'page' => ['nullable', 'integer', 'min:1'],
                'per_page' => ['nullable', 'integer', 'between:1,100'],
                'sort' => ['nullable', Rule::in(['id', 'sort', 'name', 'clicks', 'created_at', 'updated_at'])],
                'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            ]);
            $scope = $this->resolveReadWebsite($request, 'list');
            if ($scope['error'] !== null) {
                return $this->automationResponse($scope['error']);
            }
            $websiteId = $scope['website_id'];
            $data = $this->service->list(array_merge([
                'status' => 'active',
                'page' => 1,
                'per_page' => 20,
                'sort' => 'id',
                'direction' => 'desc',
                'website_id' => $websiteId,
            ], $validated, [
                'website_id' => $websiteId,
            ]));
            $result = AutomationResult::success(
                'Links listed.',
                $data,
                $this->readMeta('list', $websiteId)
            );

            return $this->automationResponse($result);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Inspect one website-scoped Link by ID or slug.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        try {
            $this->authorizeLinkAction('show', $request);
            $request->validate([
                'website_id' => ['nullable', 'integer', 'min:1'],
            ]);
            $scope = $this->resolveReadWebsite($request, 'inspect');
            if ($scope['error'] !== null) {
                return $this->automationResponse($scope['error']);
            }
            $websiteId = $scope['website_id'];
            $item = $this->service->inspect($id, [
                'website_id' => $websiteId,
            ]);
            $result = $item
                ? AutomationResult::success('Link inspected.', [
                    'item' => $item,
                ], $this->readMeta('inspect', $websiteId))
                : AutomationResult::fail('Link not found.', null, $this->readMeta('inspect', $websiteId), [
                    'identifier' => [(string) $id],
                ], 404);

            return $this->automationResponse($result);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Preview or execute a guarded Link create mutation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->authorizeLinkAction('store', $request);
            $result = $this->service->create(
                $this->mutationInput($request),
                $this->mutationOptions($request)
            );

            return $this->automationResponse($result);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Preview or execute a guarded Link patch mutation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        try {
            $this->authorizeLinkAction('update', $request);
            $result = $this->service->update(
                $id,
                $this->mutationInput($request),
                $this->mutationOptions($request)
            );

            return $this->automationResponse($result);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Preview or execute a guarded Link delete mutation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        try {
            $this->authorizeLinkAction('destroy', $request);
            $result = $this->service->delete($id, $this->mutationOptions($request));

            return $this->automationResponse($result);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Preview or execute an atomic guarded Link bulk update.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $this->authorizeLinkAction('update', $request);
            $validated = $this->validateBulkUpdateTransport($request);
            $result = $this->service->bulkUpdate(
                $validated['items'],
                $this->mutationOptions($request)
            );

            return $this->automationResponse($result);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Validate and normalize the bulk update transport payload.
     *
     * JSON objects with numeric keys are rejected instead of being treated as lists.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    protected function validateBulkUpdateTransport(Request $request): array
    {
        return $request->validate([
            'items' => ['required', 'array', 'list', function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                if (! $this->isJsonListInput($request, $attribute)) {
                    $fail('The ' . str_replace('_', ' ', $attribute) . ' field must be a JSON list.');
                }
            }],
            'items.*' => ['array'],
        ]);
    }

    /**
     * Preview or execute an atomic guarded Link tag synchronization.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkSyncTags(Request $request): JsonResponse
    {
        try {
            $this->authorizeLinkAction('update', $request);
            $validated = $this->validateBulkSyncTagsTransport($request);
            $result = $this->service->bulkSyncTags(
                $validated['identifiers'],
                $validated['action'] ?? 'sync',
                [
                    'link_categories' => $validated['link_categories'] ?? [],
                    'link_tags' => $validated['link_tags'] ?? [],
                ],
                $this->mutationOptions($request)
            );

            return $this->automationResponse($result);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Validate and normalize the bulk tag transport payload.
     *
     * JSON objects with numeric keys are rejected instead of being treated as lists.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    protected function validateBulkSyncTagsTransport(Request $request): array
    {
        $jsonList = function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
            if (! $this->isJsonListInput($request, $attribute)) {
                $fail('The ' . str_replace('_', ' ', $attribute) . ' field must be a JSON list.');
            }
        };

        return $request->validate([
            'identifiers' => ['required', 'array', 'list', $jsonList],
            'action' => ['nullable', 'string', Rule::in(['sync', 'attach', 'detach'])],
            'link_categories' => ['nullable', 'array', 'list', $jsonList],
            'link_tags' => ['nullable', 'array', 'list', $jsonList],
        ]);
    }

    /**
     * Determine whether a JSON field was encoded as a list rather than an object.
     *
     * Laravel decodes numeric-key JSON objects into PHP arrays, so raw JSON shape is
     * checked before the validated transport data reaches the automation service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $attribute
     *
     * @return bool
     */
    protected function isJsonListInput(Request $request, string $attribute): bool
    {
        if (! $request->isJson()) {
            return true;
        }

        $payload = json_decode($request->getContent());

        if (json_last_error() !== JSON_ERROR_NONE || ! is_object($payload) || ! property_exists($payload, $attribute)) {
            return true;
        }

        return is_array($payload->{$attribute});
    }

    /**
     * Build guarded mutation options from the authenticated API request.
     *
     * Explicit dry-run mode always overrides force mode.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function mutationOptions(Request $request): array
    {
        $dryRun = $request->boolean('dry_run');
        $website = $request->input('website_id') ?? wncms()->website()->get()?->getKey();

        return [
            'surface' => 'api_v2',
            'actor_user_id' => (int) $request->user()->getKey(),
            'website_id' => $website === null ? null : (int) $website,
            'force' => !$dryRun && $request->boolean('force'),
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Remove API transport controls from a single-mutation payload.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function mutationInput(Request $request): array
    {
        return $request->except(['api_token', 'dry_run', 'force', 'website_id']);
    }

    /**
     * Resolve and authorize the explicit or current WNCMS website for a read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $action
     * @return array{website_id: int|null, error: array|null}
     */
    protected function resolveReadWebsite(Request $request, string $action): array
    {
        $website = $request->input('website_id') ?? wncms()->website()->get()?->getKey();
        if ($website === null) {
            return [
                'website_id' => null,
                'error' => AutomationResult::fail(
                    'Website context is not available',
                    null,
                    $this->readMeta($action, null),
                    ['website_id' => ['required']],
                    409
                ),
            ];
        }

        $websiteId = (int) $website;
        $guard = $this->guard->preview([
            'safety' => [
                'permission' => '',
            ],
            'relationships' => [
                'website_ids' => [$websiteId],
            ],
        ], [
            'write_mode' => true,
            'actor_user_id' => (int) $request->user()->getKey(),
        ]);

        if (($guard['status'] ?? 'fail') === 'fail') {
            $code = (int) ($guard['code'] ?? 403);

            return [
                'website_id' => $websiteId,
                'error' => AutomationResult::fail(
                    $code === 422 ? 'Website validation failed.' : 'Website access denied.',
                    null,
                    $this->readMeta($action, $websiteId),
                    (array) ($guard['errors'] ?? []),
                    $code
                ),
            ];
        }

        return [
            'website_id' => $websiteId,
            'error' => null,
        ];
    }

    /**
     * Build standard read-result metadata.
     *
     * @param  string  $action
     * @param  int|null  $websiteId
     * @return array
     */
    protected function readMeta(string $action, ?int $websiteId): array
    {
        return [
            'surface' => 'api_v2',
            'domain' => 'links',
            'action' => $action,
            'website_id' => $websiteId,
        ];
    }

    /**
     * Authorize one Links resource action from API v2 configuration.
     *
     * @param  string  $action
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function authorizeLinkAction(string $action, Request $request): void
    {
        $permission = config("wncms-backend-api-v2.resources.links.permissions.{$action}");
        if (!empty($permission) && !$request->user()?->can($permission)) {
            throw new AuthorizationException();
        }
    }

    /**
     * Return an automation envelope using its declared HTTP result code.
     *
     * @param  array  $result
     * @return \Illuminate\Http\JsonResponse
     */
    protected function automationResponse(array $result): JsonResponse
    {
        return response()->json($result, (int) $result['code']);
    }
}
