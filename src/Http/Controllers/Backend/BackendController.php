<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Controller;

abstract class BackendController extends Controller
{
    protected string $modelClass;
    protected array $cacheTags;
    protected string $singular;
    protected string $plural;

    public function __construct()
    {
        $this->modelClass = $this->getModelClass();
        $this->cacheTags = $this->getModelCacheTags();
        $this->singular = $this->getModelSingular();
        $this->plural = $this->getModelPlural();
    }

    public function getModelClass(): string
    {
        $className = class_basename(static::class);
        $modelKey = str()->before($className, 'Controller');

        return wncms()->getModelClass(str()->snake($modelKey));
    }

    protected function getModelTable()
    {
        $modelClass = $this->getModelClass();
        return (new $modelClass)->getTable();
    }

    protected function getModelCacheTags(): array
    {
        return $this->cacheTags ?? [$this->getModelTable()];
    }

    protected function getModelSingular(): string
    {
        return $this->singular ?? str()->singular($this->getModelTable());
    }

    protected function getModelPlural(): string
    {
        return $this->plural ?? str()->plural($this->getModelSingular());
    }

    /**
     * Flush the cache.
     * 
     * @param string|array|null $tag
     * @return bool
     */
    public function flush(string|array|null $tags = null)
    {
        $tags ??= $this->cacheTags;
        if (is_string($tags)) {
            $tags = [$tags];
        }

        $isCleared = false;

        foreach ($tags as $tag) {
            $result = wncms()->cache()->tags($tag)->flush();
            if ($result) {
                $isCleared = true;
            }
        }

        return $isCleared;
    }

    /**
     * ============================================
     * CRUD Operations
     * ============================================
     */
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        $q->orderBy('id', 'desc');

        $models = $q->get();

        return $this->view('backend.' . $this->plural . '.index', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.' . $this->singular)]),
            'models' => $models,
        ]);
    }

    public function create(int|string|null $id = null)
    {
        if ($id) {
            $model = $this->modelClass::find($id);
            if (!$model) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $model = new $this->modelClass;
        }

        return $this->view('backend.' . $this->plural . '.create', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.' . $this->singular)]),
            'model' => $model,
        ]);
    }

    public function store(Request $request)
    {
        $model = $this->modelClass::create($request->all());

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_created'),
                'redirect' => route($this->plural . '.edit', ['id' => $model->id]),
            ]);
        }

        return redirect()->route($this->plural . '.edit', ['id' => $model->id])
            ->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(int|string $id)
    {
        $model = $this->modelClass::find($id);
        if (!$model) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.' . $this->plural . '.edit', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.' . $this->singular)]),
            'model' => $model,
        ]);
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::find($id);
        if (!$model) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $model->update($request->all());

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_updated'),
                'redirect' => route($this->plural . '.edit', ['id' => $model->id]),
            ]);
        }

        return redirect()->route($this->plural . '.edit', ['id' => $model->id])
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy($id)
    {
        $model = $this->modelClass::find($id);

        if (!$model) {
            return back()->withMessage(__('wncms::word.model_not_found', [
                'model_name' => __('wncms::word.' . $this->singular),
            ]));
        }

        $model->delete();

        $this->flush();

        return redirect()->route($this->plural . '.index')
            ->withMessage(__('wncms::word.successfully_deleted'));
    }

    /**
     * Bulk delete models.
     */
    public function bulk_delete(Request $request)
    {
        if (!is_array($request->model_ids)) {
            $modelIds = explode(",", $request->model_ids);
        } else {
            $modelIds = $request->model_ids;
        }

        $count = $this->modelClass::whereIn('id', $modelIds)->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return back()->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
