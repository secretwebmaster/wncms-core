<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Controller;
use Wncms\Models\Page;
use Wncms\Services\Builder\BuilderManager;

class PageBuilderController extends Controller
{
    protected BuilderManager $builder;

    public function __construct(BuilderManager $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Editor UI
     */
    public function editor(Page $page)
    {
        $builderType = $page->builder_type ?: 'default';

        return wncms()->view('backend.pages.builder.editor', [
            'page' => $page,
            'builder_type' => $builderType,
            'builder_engines' => $this->builder->getEngines(),
        ]);
    }


    /**
     * Load existing builder content (GET)
     */
    public function load(Page $page)
    {
        $builderType = $page->builder_type ?: 'default';

        $data = $this->builder->load($page, $builderType);

        // Ensure payload format is always complete for GrapeJS
        $data = array_merge([
            'html'       => '',
            'css'        => '',
            'components' => [],
            'styles'     => [],
            'assets'     => [],
        ], $data ?? []);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }


    /**
     * Save builder content (POST)
     */
    public function save(Request $request, Page $page)
    {
        $builderType = $page->builder_type ?: 'default';

        // The builder payload (GrapeJS format)
        $payload = $request->input('payload', []);

        // Save via BuilderManager (auto-versioning)
        $r = $this->builder->save($page, $payload, $builderType);
        info($r);

        return response()->json([
            'success' => true,
            'message' => 'Builder content saved successfully.',
        ]);
    }
}
