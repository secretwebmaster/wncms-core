<?php

namespace Wncms\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

trait BulkDeleteTraits
{
    public static function bootBulkDeleteTraits()
    {
        static::deleting(function (Model $model) {
            if ($model->usesSoftDeletes() && !$model->isForceDeleting()) {
                // Handle soft delete logic
                $model->delete();
            }
        });
    }

    public function bulk_delete(Request $request)
    {
        info($request->all());
        $modelClass = $request->input('model');
        $modelIds = $request->input('model_ids', []);
        $count = 0;

        if (empty($modelIds)) {
            return response()->json([
                'message' => __('word.ids_is_empty')
            ], 400);
        }

        // Check if the model class exists in the Wncms\Models namespace
        $modelClass = 'Wncms\Models\\' . $modelClass;
        if (!class_exists($modelClass)) {
            return response()->json([
                'message' => __('word.model_class_is_not_found')
            ], 404);
        }

        // Check if the model uses soft deletes by checking for the method's existence
        $model = new $modelClass;
        if (method_exists($model, 'usesSoftDeletes') && $model->usesSoftDeletes()) {
            $forceDelete = $request->input('force', false);
            $query = $model->whereIn($model->getKeyName(), $modelIds);

            if ($forceDelete) {
                $count = $query->forceDelete();
            } else {
                $count = $query->delete();
            }
        } else {
            // If the model does not use soft deletes, just perform a regular delete
            $count = $model->whereIn($model->getKeyName(), $modelIds)->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('word.successfully_bulk_deleted_count_models', ['count' => $count]),
        ]);
    }
}
