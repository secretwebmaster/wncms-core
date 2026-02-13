<?php

namespace Wncms\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Wncms\Http\Controllers\Api\V1\ApiController;
use Wncms\Http\Resources\PostResource;

class PostController extends ApiController
{
    protected function buildPostPayload(Request $request, $userId = null)
    {
        return [
            'user_id' => $userId,
            'status' => $request->input('status', 'published'),
            'visibility' => $request->input('visibility', 'public'),
            'external_thumbnail' => $request->input('external_thumbnail'),
            'slug' => $request->input('slug') ?: wncms()->getUniqueSlug('posts'),
            'title' => $request->input('title'),
            'label' => $request->input('label'),
            'excerpt' => $request->input('excerpt'),
            'content' => $request->input('content'),
            'remark' => $request->input('remark'),
            'sort' => $request->input('sort'),
            'password' => $request->input('password'),
            'price' => $request->input('price'),
            'is_pinned' => $request->input('is_pinned', 0),
            'is_recommended' => $request->input('is_recommended', 0),
            'is_dmca' => $request->input('is_dmca', 0),
            'published_at' => $request->input('published_at') ? Carbon::parse($request->input('published_at')) : now(),
            'expired_at' => $request->input('expired_at') ? Carbon::parse($request->input('expired_at')) : null,
            'source' => $request->input('source'),
            'ref_id' => $request->input('ref_id'),
        ];
    }

    public function index(Request $request)
    {
        if ($err = $this->checkEnabled('wncms_api_post_index')) return $err;
        $auth = $this->checkAuthSetting('wncms_api_post_index', $request);
        if (isset($auth['error'])) return $auth['error'];

        try {
            $options = [
                'tags' => $request->input('tags'),
                'tag_type' => $request->input('tag_type', 'post_category'),
                'excluded_post_ids' => $request->input('excluded_post_ids'),
                'excluded_tag_ids' => $request->input('excluded_tag_ids'),
                'keywords' => $request->input('keyword') ?? $request->input('keywords'),
                'sort' => $request->input('sort', 'published_at'),
                'direction' => $request->input('direction', 'desc'),
                'select' => $request->input('select'),
                'page_size' => $request->input('page_size', 20),
                'page' => $request->input('page', 1),
                'is_random' => $request->boolean('is_random', false),
                'withs' => ['media', 'comments', 'tags', 'translations', 'user'],
                'count' => $request->count,
            ];

            $posts = wncms()->post()->getList($options);

            if ($posts instanceof LengthAwarePaginator) {
                return $this->success(
                    PostResource::collection($posts),
                    __('wncms::api.success'),
                    200,
                    [
                        'total' => $posts->total(),
                        'count' => $posts->count(),
                        'page_size' => $posts->perPage(),
                        'current_page' => $posts->currentPage(),
                        'last_page' => $posts->lastPage(),
                        'has_more' => $posts->hasMorePages(),
                        'next' => $posts->nextPageUrl(),
                        'previous' => $posts->previousPageUrl(),
                    ]
                );
            }

            return $this->success(
                PostResource::collection($posts),
                __('wncms::api.success'),
                200,
                ['count' => $posts->count()]
            );

        } catch (\Throwable $e) {

            logger()->error('API PostController@index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->fail(__('wncms::api.server_error'), 500);
        }
    }

    public function store(Request $request)
    {
        if ($err = $this->checkEnabled('wncms_api_post_store')) return $err;
        $auth = $this->checkAuthSetting('wncms_api_post_store', $request);
        if (isset($auth['error'])) return $auth['error'];
        $user = $auth['user'];

        $postModel = wncms()->getModelClass('post');
        $normalizedTranslatableInputs = $this->getNormalizedTranslatableInputs($request, $postModel);
        $this->mergeTranslatableBaseValuesIntoRequest($request, $normalizedTranslatableInputs);

        $data = $this->buildPostPayload($request, $user?->id);
        $post = $postModel::create($data);
        $this->applyModelTranslations($post, $normalizedTranslatableInputs);
        $websiteIds = $this->resolveModelWebsiteIds($postModel, $request->input('website_id'));
        if (empty($websiteIds)) {
            $fallbackWebsiteId = (int) (wncms()->website()->get()?->id ?? 0);
            if ($fallbackWebsiteId > 0) {
                $websiteIds = [$fallbackWebsiteId];
            }
        }
        if (!empty($websiteIds)) {
            $this->syncModelWebsites($post, $websiteIds);
        }

        if ($request->hasFile('thumbnail')) {
            $post->addMediaFromRequest('thumbnail')->toMediaCollection('post_thumbnail');
        }

        if (gss('localize_post_image')) $post->localizeImages();

        if ($request->filled('categories')) {
            $post->syncTagsWithType(explode(',', $request->categories), 'post_category');
        }

        if ($request->filled('tags')) {
            $post->syncTagsWithType(explode(',', $request->tags), 'post_tag');
        }

        wncms()->cache()->tags(['posts'])->flush();
        $post->refresh();

        return $this->success(
            $post,
            __('wncms::api.post_created', ['id' => $post->id]),
            200
        );
    }

    public function update(Request $request, $slug)
    {
        if ($err = $this->checkEnabled('wncms_api_post_update')) return $err;

        $auth = $this->checkAuthSetting('wncms_api_post_update', $request);
        if (isset($auth['error'])) return $auth['error'];

        $lookup = is_numeric($slug)
            ? ['id' => $slug, 'cache' => false]
            : ['slug' => $slug, 'cache' => false];

        $post = wncms()->post()->get($lookup);

        if (!$post) {
            return $this->fail(__('wncms::api.post_not_found'), 404);
        }

        $normalizedTranslatableInputs = $this->getNormalizedTranslatableInputs($request, $post::class);
        $this->mergeTranslatableBaseValuesIntoRequest($request, $normalizedTranslatableInputs);

        $data = $this->buildPostPayload($request, $post->user_id);
        foreach ($this->getModelTranslatableFields($post::class) as $field) {
            if (!$request->has($field)) {
                unset($data[$field]);
            }
        }

        $post->update($data);
        $this->applyModelTranslations($post, $normalizedTranslatableInputs);
        $websiteIds = $this->resolveModelWebsiteIds($post::class, $request->input('website_id'));
        if (empty($websiteIds)) {
            $fallbackWebsiteId = (int) (wncms()->website()->get()?->id ?? 0);
            if ($fallbackWebsiteId > 0) {
                $websiteIds = [$fallbackWebsiteId];
            }
        }
        if (!empty($websiteIds)) {
            $this->syncModelWebsites($post, $websiteIds);
        }

        if ($request->filled('categories')) {
            $post->syncTagsWithType(explode(',', $request->categories), 'post_category');
        }
        if ($request->filled('tags')) {
            $post->syncTagsWithType(explode(',', $request->tags), 'post_tag');
        }

        wncms()->cache()->tags(['posts'])->flush();
        $post->refresh();

        return $this->success(
            new PostResource($post),
            __('wncms::api.post_updated', ['id' => $post->id]),
            200
        );
    }

    public function show(Request $request, $slug)
    {
        if ($err = $this->checkEnabled('wncms_api_post_show')) return $err;

        $auth = $this->checkAuthSetting('wncms_api_post_show', $request);
        if (isset($auth['error'])) return $auth['error'];

        $post = wncms()->post()->get(['slug' => $slug, 'cache' => false]);

        if (!$post) {
            return $this->fail(__('wncms::api.post_not_found'), 404);
        }

        return $this->success(
            new PostResource($post),
            __('wncms::api.success'),
            200
        );
    }

    public function delete(Request $request, $slug)
    {
        if ($err = $this->checkEnabled('wncms_api_post_delete')) return $err;

        $auth = $this->checkAuthSetting('wncms_api_post_delete', $request);
        if (isset($auth['error'])) return $auth['error'];

        $lookup = is_numeric($slug)
            ? ['id' => $slug, 'cache' => false]
            : ['slug' => $slug, 'cache' => false];

        $post = wncms()->post()->get($lookup);

        if (!$post) {
            return $this->fail(__('wncms::api.post_not_found'), 404);
        }

        $post->delete();
        wncms()->cache()->tags(['posts'])->flush();

        return $this->success(
            null,
            __('wncms::api.post_deleted', ['id' => $post->id]),
            200
        );
    }
}
