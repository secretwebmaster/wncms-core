<?php

namespace Wncms\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Wncms\Http\Controllers\Api\V1\ApiController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Wncms\Http\Resources\PostResource;

class PostController extends ApiController
{
    public function index(Request $request)
    {
        if (!gss('enable_api_post_index')) {
            return response()->json([
                'status' => 403,
                'message' => 'API access is disabled',
            ], 403);
        }

        try {
            $options = [
                'tags' => $request->input('tags'),
                'tag_type' => $request->input('tag_type', 'post_category'),
                'excluded_post_ids' => $request->input('excluded_post_ids'),
                'excluded_tag_ids' => $request->input('excluded_tag_ids'),
                'keywords' => $request->input('keyword') ?? $request->input('keywords'),
                'order' => $request->input('order_by', 'published_at'),
                'sequence' => $request->input('sort', 'desc'),
                'select' => $request->input('select'),
                'page_size' => $request->input('page_size', 20),
                'page' => $request->input('page', 1),
                'is_random' => $request->boolean('is_random', false),
                'withs' => ['media', 'comments', 'tags', 'translations', 'websites', 'user'],
                'count' => $request->count,
            ];
    
            $posts = wncms()->post()->getList($options);
    
            return response()->json([
                'status' => 200,
                'message' => 'success',
                'data' => PostResource::collection($posts),
            ], 200, [], JSON_UNESCAPED_UNICODE);
    
        } catch (\Throwable $e) {
            logger()->error('API PostController@index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return response()->json([
                'status' => 500,
                'message' => 'Server Error: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        if (!gss('enable_api_post_store')) {
            return response()->json([
                'status' => 403,
                'message' => 'API access is disabled',
            ], 403);
        }

        // Validate and authenticate
        $user = wncms()->getModelClass('user')::where('api_token', $request->api_token)->first();
        if (!$user) {
            return response()->json(['status' => 'fail', 'message' => 'Invalid token']);
        }
        auth()->login($user);

        // Handle website IDs (comma or array)
        $websiteIds = is_array($request->website_id)
            ? $request->website_id
            : explode(',', $request->website_id);

        $websiteIds = isAdmin()
            ? wncms()->getModelClass('website')::query()->whereIn('id', $websiteIds)->orWhereIn('domain', $websiteIds)->pluck('id')->toArray()
            : auth()->user()->websites()->whereIn('id', $websiteIds)->orWhereIn('domain', $websiteIds)->pluck('id')->toArray();

        if (!$user) {
            return response()->json(['status' => 'fail', 'message' => 'User is not found']);
        }

        $data = [
            'user_id' => $user->id,
            'status' => $request->status ?? 'published',
            'visibility' => $request->visibility ?? 'public',
            'external_thumbnail' => $request->external_thumbnail,
            'slug' => $request->slug ?? wncms_get_unique_slug('posts', 'slug', 6),
            'title' => $request->title,
            'label' => $request->label,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'remark' => $request->remark,
            'order' => $request->order,
            'password' => $request->password,
            'price' => $request->price,
            'is_pinned' => $request->is_pinned ?? 0,
            'is_recommended' => $request->is_recommended ?? 0,
            'is_dmca' => $request->is_dmca ?? 0,
            'published_at' => $request->published_at ? Carbon::parse($request->published_at) : now(),
            'expired_at' => $request->expired_at ? Carbon::parse($request->expired_at) : null,
            'source' => $request->source,
            'ref_id' => $request->ref_id,
        ];

        $postModel = wncms()->getModel('post');

        // Check for duplicated title if enabled
        if ($request->check_title) {
            $existing = $postModel::query()
                ->where(function ($q) use ($request) {
                    foreach (LaravelLocalization::getSupportedLanguagesKeys() as $locale) {
                        $q->orWhere("title", $request->title);
                    }
                })->first();

            if ($existing && !$request->update_content_when_duplicated) {
                return response()->json(['status' => 'success', 'message' => 'Skipped. Duplicated post is found']);
            }

            if ($existing && $request->update_content_when_duplicated) {
                $existing->update($data);
                return response()->json(['status' => 'success', 'message' => 'Existing post updated']);
            }
        }

        $post = $postModel::create($data);

        // Sync website relations
        if (!empty($websiteIds)) {
            $post->websites()->sync($websiteIds);
        }

        // Handle thumbnail
        if ($request->hasFile('thumbnail')) {
            $post->addMediaFromRequest('thumbnail')->toMediaCollection('post_thumbnail');
        }

        // Optional: localize images
        if (gss('localize_post_image')) {
            $post->localizeImages();
        }

        // Tags
        if ($request->filled('categories')) {
            $post->syncTagsWithType(explode(',', $request->categories), 'post_category');
        }

        if ($request->filled('tags')) {
            $post->syncTagsWithType(explode(',', $request->tags), 'post_tag');
        }

        wncms()->cache()->tags(['posts'])->flush();

        return response()->json([
            'status' => 'success',
            'message' => "post #{$post->id} created",
            'data' => $post,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function show(Request $request, $id)
    {
        if (!gss('enable_api_post_show')) {
            return response()->json([
                'status' => 403,
                'message' => 'API access is disabled',
            ], 403);
        }

        return wncms()->post()->get($id);
    }
}
