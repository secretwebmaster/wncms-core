<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ModelPermissionResolver;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Http\Middleware\ApiV2TokenAuth;

class ModelController extends Controller
{
    /**
     * Create the generic model controller.
     *
     * @param  \Wncms\Api\V2\ModelPermissionResolver  $modelPermissions
     */
    public function __construct(private ModelPermissionResolver $modelPermissions)
    {
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
        if ($response = $this->authorizeAdminModelMutation($request, 'edit')) {
            return $response;
        }

        $modelKey = $request->attributes->get('wncms_api_v2_model_key', $request->model);
        $modelClass = $this->resolveModelClass($modelKey);

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
        if ($response = $this->authorizeAdminModelMutation($request, 'bulk_delete')) {
            return $response;
        }

        $modelKey = $request->attributes->get('wncms_api_v2_model_key', $request->model);
        $modelClass = $this->resolveModelClass($modelKey);

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
        if ($response = $this->authorizeAdminModelMutation($request, 'bulk_delete')) {
            return $response;
        }

        $modelKey = $request->attributes->get('wncms_api_v2_model_key', $request->model);
        $modelClass = $this->resolveModelClass($modelKey);

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

    protected function authorizeAdminModelMutation(Request $request, string $permissionSuffix): ?JsonResponse
    {
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        if ($context instanceof AuthenticationContext) {
            $requirement = $this->modelPermissions->resolve($request->input('model'), $permissionSuffix);
            $user = $context->actor();

            if (
                $requirement !== null
                && method_exists($user, 'checkPermissionTo')
                && $user->checkPermissionTo($requirement['permission'])
            ) {
                $request->attributes->set('wncms_api_v2_model_key', $requirement['model_key']);

                return null;
            }
        } else {
            $user = $request->user();

            if ($user && method_exists($user, 'hasRole') && $user->hasRole(['admin', 'superadmin'])) {
                return null;
            }
        }

        return response()->json([
            'code' => Response::HTTP_FORBIDDEN,
            'status' => 'fail',
            'message' => __('wncms::word.permission_denied'),
            'data' => null,
            'meta' => [],
            'errors' => [],
        ], Response::HTTP_FORBIDDEN);
    }
}
