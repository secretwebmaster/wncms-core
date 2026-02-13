<?php

namespace Wncms\Http\Controllers\Api\V1;

use Illuminate\Http\Request;

class TagController extends ApiController
{
    public function index(Request $request)
    {
        if ($err = $this->checkEnabled('wncms_api_tag_index')) return $err;
        $auth = $this->checkAuthSetting('wncms_api_tag_index', $request);
        if (isset($auth['error'])) return $auth['error'];

        $request->validate([
            'type' => 'required|string|max:50',
            'locale' => 'nullable|string|max:10',
        ]);

        $locale = $request->input('locale', app()->getLocale());
        $tagModel = wncms()->getModelClass('tag');

        $tags = $tagModel::where('type', $request->input('type'))
            ->whereNull('parent_id')
            ->with('children', 'children.children')
            ->orderBy('sort', 'desc')
            ->with('translations')
            ->get()
            ->map(function ($tag) use ($locale) {
                $tag->name = $tag->getTranslation('name', $locale);
                return $tag;
            });

        return $this->success(
            $tags,
            __('wncms::word.successfully_fetched_data')
        );
    }

    public function exist(Request $request)
    {
        if ($err = $this->checkEnabled('wncms_api_tag_exist')) return $err;
        $auth = $this->checkAuthSetting('wncms_api_tag_exist', $request);
        if (isset($auth['error'])) return $auth['error'];

        $request->validate([
            'tagIds' => 'required|array',
            'tagIds.*' => 'integer',
        ]);

        $tagModel = wncms()->getModelClass('tag');
        $tagIds = $tagModel::whereIn('id', $request->input('tagIds', []))
            ->pluck('id')
            ->toArray();

        return $this->success(
            ['ids' => $tagIds],
            __('wncms::word.successfully_fetched_data')
        );
    }

    /**
     * Store a new tag (or update an existing one if duplicated and allowed).
     *
     * Acceptable request inputs:
     * - api_token      (string, required)   → User API token for authentication
     * - name           (string, required)   → Tag display name
     * - slug           (string, optional)   → Custom slug; defaults to slugified `name`
     * - type           (string, optional)   → Tag type; defaults to `post_category`
     * - parent_id      (int, optional)      → Parent tag ID (nullable, must exist in `tags`)
     * - description    (string, optional)   → Tag description
     * - icon           (string, optional)   → Tag icon reference
     * - sort           (int, optional)      → Sorting order
     * - website_id     (int|array|string)   → Website IDs (array or comma-separated string)
     * - update_when_duplicated (bool, optional) → If true, update existing tag when duplicate name+type is found
     */
    public function store(Request $request)
    {
        if (!$this->isTagStoreEnabled()) {
            return $this->fail("API feature 'wncms_api_tag_store' is disabled", 403);
        }
        $auth = $this->checkAuthSetting('wncms_api_tag_store', $request);
        if (isset($auth['error'])) return $auth['error'];

        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255',
                'type' => 'nullable|string|max:50',
                'parent_id' => 'nullable|integer|exists:tags,id',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:255',
                'sort' => 'nullable|integer',
                'group' => 'nullable|string|max:255',
                'website_id' => 'nullable',
                'update_when_duplicated' => 'nullable|boolean',
            ]);
            $websiteInput = $data['website_id'] ?? null;
            $updateWhenDuplicated = $request->boolean('update_when_duplicated');
            unset($data['website_id'], $data['update_when_duplicated']);

            if (empty($data['type'])) {
                $data['type'] = 'post_category';
            }

            if (empty($data['slug'])) {
                $data['slug'] = str()->slug($data['name']) ?: $data['name'];
            }

            $tagModel = wncms()->getModelClass('tag');
            $existing = $tagModel::where('name', $data['name'])->where('type', $data['type'])->first();

            if ($existing && !$updateWhenDuplicated) {
                return $this->success($existing, 'Skipped. Duplicated tag found');
            }

            if ($existing && $updateWhenDuplicated) {
                $existing->update($data);
                return $this->success($existing->fresh(), 'Existing tag updated');
            }

            $tag = $tagModel::create($data);

            if (!empty($websiteInput)) {
                $websiteIds = $this->resolveModelWebsiteIds($tagModel, $websiteInput);
                if (!empty($websiteIds)) {
                    $this->syncModelWebsites($tag, $websiteIds);
                }
            }

            wncms()->cache()->tags(['tags'])->flush();

            return $this->success($tag->fresh(), "tag #{$tag->id} created");

        } catch (\Throwable $e) {
            logger()->error('API TagController@store error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->fail('Server Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if the tag store API feature is enabled. Legacy support for 'enable_api_tag_store' is included.
     */
    protected function isTagStoreEnabled(): bool
    {
        return (bool) gss('wncms_api_tag_store') || (bool) gss('enable_api_tag_store');
    }
}
