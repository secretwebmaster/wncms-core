<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\ModelPermissionResolver;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Http\Middleware\RequireApiV2ModelPermission;

class ModelController extends Controller
{
    /**
     * Create the generic model controller.
     *
     * @param  \Wncms\Api\V2\ModelPermissionResolver  $modelPermissions
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(
        private ModelPermissionResolver $modelPermissions,
        private ApiResponseFactory $responses,
    ) {
    }

    /**
     * Resolve model class from request input.
     */
    protected function resolveModelClass(string $modelInput): ?string
    {
        $modelInput = trim($modelInput);

        if (str_contains($modelInput, '\\') && class_exists($modelInput)) {
            return $modelInput;
        }

        $modelName = Str::studly($modelInput);

        if (class_exists("App\\Models\\{$modelName}")) {
            return "App\\Models\\{$modelName}";
        }

        if (class_exists("Wncms\\Models\\{$modelName}")) {
            return "Wncms\\Models\\{$modelName}";
        }

        return null;
    }

    public function update(Request $request)
    {
        $modelClass = $this->authorizedModelClass($request, 'edit');
        if ($modelClass instanceof JsonResponse) {
            return $modelClass;
        }

        if (!$modelClass) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.model_not_found', [
                    'model_name' => $request->model,
                ]),
            ]);
        }

        $model = new $modelClass;
        $tableName = $model->getTable();
        $modelIds = $this->getModelIdsFromRequest($request);

        if (empty($modelIds)) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.model_ids_are_not_found'),
            ]);
        }

        try {
            DB::transaction(function () use ($modelClass, $tableName, $modelIds, $request) {
                $modelClass::query()
                    ->whereIn('id', $modelIds)
                    ->update([
                        $request->column => $request->value,
                    ]);

                wncms()->cache()->tags($tableName)->flush();
            });

            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_updated'),
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            info('bulk update fail');
            info($e);

            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage(),
                'reload' => true,
            ]);
        }
    }

    public function bulk_delete(Request $request)
    {
        $modelClass = $this->authorizedModelClass($request, 'bulk_delete');
        if ($modelClass instanceof JsonResponse) {
            return $modelClass;
        }

        if (!$modelClass) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.model_not_found', [
                    'model_name' => $request->model,
                ]),
            ]);
        }

        $model = new $modelClass;
        $tableName = $model->getTable();

        if (empty($request->model_ids) && empty($request->model_id)) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.model_ids_are_not_found'),
            ]);
        }

        $modelIds = $request->model_ids ?? (array) $request->model_id;

        try {
            $count = DB::transaction(function () use ($modelClass, $modelIds, $tableName) {
                $count = $modelClass::query()
                    ->whereIn('id', $modelIds)
                    ->delete();

                wncms()->cache()->tags($tableName)->flush();

                return $count;
            });

            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        } catch (\Exception $e) {
            info('bulk delete fail');
            info($e);

            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function bulk_force_delete(Request $request)
    {
        $modelClass = $this->authorizedModelClass($request, 'bulk_delete');
        if ($modelClass instanceof JsonResponse) {
            return $modelClass;
        }

        if (!$modelClass) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.model_not_found', [
                    'model_name' => $request->model,
                ]),
            ]);
        }

        $model = new $modelClass;
        $tableName = $model->getTable();

        try {
            DB::transaction(function () use ($modelClass, $tableName, $request) {
                $models = $modelClass::query()
                    ->whereIn('id', $request->model_ids)
                    ->get();

                foreach ($models as $model) {
                    $model->forceDelete();
                }

                wncms()->cache()->tags($tableName)->flush();
            });

            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted'),
            ]);
        } catch (\Exception $e) {
            info('bulk force delete fail');
            info($e);

            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function getModelIdsFromRequest($request)
    {
        return $request->model_ids
            ?? $request->modelIds
            ?? ($request->has('model_id') ? (array) $request->model_id : null)
            ?? ($request->has('modelId') ? (array) $request->modelId : null)
            ?? [];
    }

    /**
     * Resolve the exact authorized mutation class for API or legacy backend traffic.
     *
     * API traffic consumes only the server-side trusted resolution produced by
     * the target permission middleware. Legacy backend traffic preserves the
     * existing administrator-only class resolution behavior.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $permissionSuffix
     * @return string|\Illuminate\Http\JsonResponse|null
     */
    protected function authorizedModelClass(Request $request, string $permissionSuffix): string|JsonResponse|null
    {
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        if ($context instanceof AuthenticationContext) {
            $requirement = $request->attributes->get(RequireApiV2ModelPermission::TRUSTED_RESOLUTION_ATTRIBUTE);
            $user = $context->actor();

            if (
                is_array($requirement)
                && $this->trustedResolutionMatches($request->input('model'), $permissionSuffix, $requirement)
                && method_exists($user, 'checkPermissionTo')
                && $user->checkPermissionTo($requirement['permission'])
            ) {
                return $requirement['model_class'];
            }
        } else {
            $user = $request->user();

            if ($user && method_exists($user, 'hasRole') && $user->hasRole(['admin', 'superadmin'])) {
                return $this->resolveModelClass((string) $request->input('model', ''));
            }
        }

        return $this->responses->failure(
            'authorization.permission_denied',
            'Permission denied',
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Validate a server-side trusted resolution against the request and action.
     *
     * @param  mixed  $selector
     * @param  string  $permissionSuffix
     * @param  array<string, mixed>  $resolution
     * @return bool
     */
    protected function trustedResolutionMatches(mixed $selector, string $permissionSuffix, array $resolution): bool
    {
        if (! is_string($selector) || trim($selector) === '' || str_contains($selector, '\\')) {
            return false;
        }

        $modelKey = Str::snake(Str::singular(trim($selector)));
        $modelClass = $resolution['model_class'] ?? null;

        return is_string($modelClass)
            && ($resolution['model_key'] ?? null) === $modelKey
            && ($resolution['permission'] ?? null) === $modelKey.'_'.$permissionSuffix
            && $this->modelPermissions->validModelClass($modelKey, $modelClass);
    }
}
