<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;
use Wncms\Services\Builder\BuilderManager;

class PageBuilderController extends ApiV2Controller
{
    public function __construct(protected BuilderManager $builder)
    {
    }

    public function load(Request $request, int $id)
    {
        try {
            $modelClass = wncms()->getModelClass('page');
            $page = $modelClass::find($id);
            if (!$page) {
                return $this->error('model_not_found', 404, ['id' => $id]);
            }

            $builderType = $page->builder_type ?: 'default';
            $data = $this->builder->load($page, $builderType) ?? [];

            return $this->ok([
                'success' => true,
                'data' => array_merge([
                    'html' => '',
                    'css' => '',
                    'components' => [],
                    'styles' => [],
                    'assets' => [],
                ], $data),
            ]);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    public function save(Request $request, int $id)
    {
        try {
            $modelClass = wncms()->getModelClass('page');
            $page = $modelClass::find($id);
            if (!$page) {
                return $this->error('model_not_found', 404, ['id' => $id]);
            }

            $builderType = $page->builder_type ?: 'default';
            $payload = $request->input('payload', []);
            $this->builder->save($page, $payload, $builderType);

            return $this->ok([
                'success' => true,
                'message' => 'Builder content saved successfully.',
            ], 'successfully_updated');
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }
}
