<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModelController extends Controller
{
    public function update(Request $request)
    {
        // info($request->all());
        $modelName = Str::studly($request->model);
        $model = new ("Wncms\Models\\$modelName");
        $tableName = $model->getTable();
        $modelIds = $this->getModelIdsFromRequest($request);

        if(empty($modelIds)){
            return response()->json([
                'status' => 'fail',
                'message' => __('word.model_ids_are_not_found'),
            ]); 
        }
        
        try{
            DB::transaction(function() use($modelName, $tableName, $modelIds, $request){
                $test = "Wncms\Models\\$modelName"::query()
                    ->whereIn('id',$modelIds)
                    ->update([
                        $request->column => $request->value
                    ]);
                wncms()->cache()->tags($tableName)->flush();
            });

            return response()->json([
                'status' => 'success',
                'message' => __('word.successfully_updated'),
                'reload' => true,
            ]);

        }catch(\Exception $e){

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
        info($request->all());
        $modelName = Str::studly($request->model);
        $model = new ("Wncms\Models\\$modelName");
        $tableName = $model->getTable();

        if(empty($request->model_ids) && empty($request->model_id)){
            return response()->json([
                'status' => 'fail',
                'message' => __('word.model_ids_are_not_found'),
            ]); 
        }
        
        $modelIds = $request->model_ids ?? (array) $request->moded_id; 

        try{
            $count = DB::transaction(function() use($modelName, $modelIds, $tableName){
                $model = "Wncms\Models\\{$modelName}";
                $count = $model::query()
                    ->whereIn('id',$modelIds)
                    ->delete();
                wncms()->cache()->tags($tableName)->flush();
                return $count;
            });

            return response()->json([
                'status' => 'success',
                'message' => __('word.successfully_deleted_count', ['count' => $count]),
            ]);

        }catch(\Exception $e){
            info('bulk update fail');
            info($e);
            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage()
            ]);

        }
    }

    public function bulk_force_delete(Request $request)
    {
        // info($request->all());
        $modelName = $request->model;
        $model = new ("Wncms\Models\\$modelName");
        $tableName = $model->getTable();
        
        try{
            DB::transaction(function() use($modelName, $tableName, $request){

                $models = "Wncms\Models\\$modelName"::query()->whereIn('id',$request->model_ids)->get();
                foreach($models as $model){
                    $model->forceDelete();
                }

                wncms()->cache()->tags($tableName)->flush();
            });
            return response()->json(['status' => 'success']);

        }catch(\Exception $e){
            info('bulk update fail');
            info($e);
            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage()
            ]);

        }

        return response()->json([
            'status' => 'success',
            'message' => __('word.successfully_deleted'),
        ]);
    }

    public function getModelIdsFromRequest($request)
    {
        return $request->model_ids
        ?? $request->modelIds
        ?? ($request->has('model_id') ? (array)$request->model_id : null)
        ?? ($request->has('modelId') ? (array)$request->modelId : null)
        ?? [];
    }
}
