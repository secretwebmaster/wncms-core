<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModelController extends Controller
{
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
        $modelClass = $this->resolveModelClass($request->model);

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
        $modelClass = $this->resolveModelClass($request->model);

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
        $modelClass = $this->resolveModelClass($request->model);

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
}
