<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Wncms\Services\Automation\AutomationResult;
use Wncms\Services\Automation\LinkAutomationService;

class LinkController extends ApiV2Controller
{
    /**
     * Create the Links API v2 transport adapter.
     *
     * @param  \Wncms\Services\Automation\LinkAutomationService  $service
     */
    public function __construct(protected LinkAutomationService $service)
    {
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
            $websiteId = $this->websiteId($request);
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
            $websiteId = $this->websiteId($request);
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $this->authorizeLinkAction('update', $request);
            $result = $this->service->bulkUpdate(
                (array) $request->input('items', []),
                $this->mutationOptions($request)
            );

            return $this->automationResponse($result);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
    }

    /**
     * Preview or execute an atomic guarded Link tag synchronization.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkSyncTags(Request $request): JsonResponse
    {
        try {
            $this->authorizeLinkAction('update', $request);
            $result = $this->service->bulkSyncTags(
                (array) $request->input('identifiers', []),
                (string) $request->input('action', 'sync'),
                [
                    'link_categories' => (array) $request->input('link_categories', []),
                    'link_tags' => (array) $request->input('link_tags', []),
                ],
                $this->mutationOptions($request)
            );

            return $this->automationResponse($result);
        } catch (\Throwable $exception) {
            return $this->fromThrowable($exception);
        }
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
     * Resolve the explicit or current WNCMS website ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int|null
     */
    protected function websiteId(Request $request): ?int
    {
        $website = $request->input('website_id') ?? wncms()->website()->get()?->getKey();

        return $website === null ? null : (int) $website;
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
