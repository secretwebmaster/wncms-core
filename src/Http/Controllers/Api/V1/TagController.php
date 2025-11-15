<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->input('locale', app()->getLocale());

        $tags = Tag::where('type', $request->type)
            ->whereNull('parent_id')
            ->with('children', 'children.children')
            ->orderBy('sort', 'desc')
            ->with('translations')
            ->get()
            ->map(function ($tag) use ($locale) {
                $tag->name = $tag->getTranslation('name', $locale);
                return $tag;
            });

        return $tags;
    }

    public function exist(Request $request)
    {
        $tagIds = Tag::whereIn('id', $request->tagIds ?? [])->pluck('id')->toArray();

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_fetched_data'),
            'ids' => $tagIds,
        ]);
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
     * - update_when_duplicated (bool, optional) → If true, update existing tag when duplicate slug+type is found
     */
    public function store(Request $request)
    {
        if (!gss('enable_api_tag_store', true)) {
            return response()->json([
                'status' => 403,
                'message' => 'API access is disabled',
            ], 403);
        }

        try {
            // Auth by api_token
            $user = wncms()->getModelClass('user')::where('api_token', $request->api_token)->first();
            if (!$user) {
                return response()->json(['status' => 'fail', 'message' => 'Invalid token']);
            }
            auth()->login($user);

            // Validate
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'nullable|string|max:50',
                'parent_id' => 'nullable|integer|exists:tags,id',
                'sort' => 'nullable|integer',
            ]);

            // Default type
            if (empty($data['type'])) {
                $data['type'] = 'post_category';
            }

            if (empty($data['slug'])) {
                $data['slug'] = str()->slug($data['name']) ?: $data['name'];
            }

            // Check duplicate
            $tagModel = wncms()->getModel('tag');
            $existing = $tagModel::where('name', $data['name'])->where('type', $data['type'])->first();

            if ($existing && !$request->update_when_duplicated) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Skipped. Duplicated tag found',
                    'data' => $existing,
                ]);
            }

            if ($existing && $request->update_when_duplicated) {
                $existing->update($data);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Existing tag updated',
                    'data' => $existing,
                ]);
            }

            // Create new
            $tag = $tagModel::create($data);

            // Bind websites
            if (gss('multi_website') && $request->filled('website_id')) {
                $websiteIds = is_array($request->website_id)
                    ? $request->website_id
                    : explode(',', $request->website_id);

                $tag->bindWebsites($websiteIds);
            }

            wncms()->cache()->tags(['tags'])->flush();

            return response()->json([
                'status' => 'success',
                'message' => "tag #{$tag->id} created",
                'data' => $tag,
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            logger()->error('API TagController@store error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Server Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
