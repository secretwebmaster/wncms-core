<?php

namespace Wncms\Http\Controllers\Backend;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Faker\Factory as Faker;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class PostController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();
        $this->applyBackendListWebsiteScope($q);

        if (!isAdmin()) {
            $q->whereRelation('user', 'id', auth()->id());
        }

        if (in_array($request->status, $this->modelClass::STATUSES)) {
            $q->where('status', $request->status);
        }

        if ($request->keyword) {
            $q->where('slug', 'like', "%$request->keyword%")
                ->orWhere('id', $request->keyword)
                ->orWhere('slug', $request->keyword)
                ->orWhere("title", 'like', "%$request->keyword%");
        }

        if ($request->category) {
            $q->withAnyTags([$request->category], 'post_category');
        }

        if ($request->show_trashed) {
            $q->withTrashed();
        }

        if (in_array($request->sort, $this->modelClass::SORTS)) {
            if (in_array($request->sort, ['traffics', 'clicks'])) {
                $q->orderBy($request->sort . '_count', in_array($request->direction, ['asc', 'desc']) ? $request->direction : 'desc');
            } else {
                $q->orderBy($request->sort, in_array($request->direction, ['asc', 'desc']) ? $request->direction : 'desc');
            }
        }

        $q->with(['media', 'tags']);

        $q->orderBy('created_at', 'desc');
        $q->orderBy('id', 'desc');
        
        $posts = $q->paginate($request->page_size ?? 20);

        $post_category_parants = wncms()->getModel('tag')::where('type', 'post_category')->whereNull('parent_id')->with('children')->get();

        return $this->view('backend.posts.index', [
            'page_title' => wncms_model_word('post', 'management'),
            'posts' => $posts,
            'post_category_parants' => $post_category_parants,
            'sorts' => $this->modelClass::SORTS,
            'statuses' => $this->modelClass::STATUSES,
            'visibilities' => $this->modelClass::VISIBILITIES,
        ]);
    }

    public function create($id = null)
    {
        $post = $this->modelClass::find($id);
        if ($id && !$post) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        if (isAdmin()) {
            $users = wncms()->getModel('user')::all();
            $websites = wncms()->getModel('website')::all();
        } else {
            $users = wncms()->getModel('user')::where('id', auth()->id())->get();
            $websites = auth()->user()->websites;
        }

        return $this->view('backend.posts.create', [
            'page_title' => wncms_model_word('post', 'management'),
            'statuses' => $this->modelClass::STATUSES,
            'visibilities' => $this->modelClass::VISIBILITIES,
            'post_categories' => wncms()->tag()->getArray(tagType: "post_category", columnName: "name"),
            'post_tags' => wncms()->tag()->getArray(tagType: "post_tag", columnName: "name"),
            'users' => $users,
            'websites' => $websites,
            'post' => $post ?? new $this->modelClass,
        ]);
    }

    public function store(Request $request)
    {
        if (isAdmin()) {
            $user = wncms()->getModel('user')::find($request->user_id) ?? auth()->user();
            $website_ids = wncms()->getModel('website')::whereIn("websites.id", $request->website_ids ?? [])->pluck('id')->toArray();
        } else {
            $user = auth()->user();
            $website_ids = auth()->user()->websites()->whereIn("websites.id", $request->website_id ?? [])->pluck('websites.id')->toArray();;
        }

        if (!$user) return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.user_not_found')]);

        $request->validate(
            [
                'title' => 'required|max:255',
                'status' => 'required',
                'visibility' => 'required',
                'price' => 'sometimes|nullable|numeric|max:999999.999',

            ],
            [
                'title.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.title')]),
                'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
                'visibility.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.visibility')]),
                'price.numeric' => __('wncms::word.field_should_be_numeric', ['field_name' => __('wncms::word.price')]),
                'price.max' => __('wncms::word.field_should_not_exceed', ['field_name' => __('wncms::word.price'), 'value' => '999999.999']),
            ]
        );

        $post = $user->posts()->create([
            'status' => $request->input('status'),
            'visibility' => $request->input('visibility'),
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
            'is_pinned' => $request->input('is_pinned') ?? false,
            'is_recommended' => $request->input('is_recommended') ?? false,
            'is_dmca' => $request->input('is_dmca') ?? false,
            'published_at' => $request->input('published_at') ? Carbon::parse($request->input('published_at')) : Carbon::now(),
            'expired_at' => $request->input('expired_at') ? Carbon::parse($request->input('expired_at')) : null,
        ]);

        //handle content
        $post->localizeImages();
        $post->wrapTables();

        //attach to website models
        // $post->websites()->sync($website_ids);

        //thumbnail
        if (!empty($request->post_thumbnail_remove)) {
            $post->clearMediaCollection('post_thumbnail');
        }

        if (!empty($request->post_thumbnail_clone_id)) {
            $mediaToClone = Media::find($request->post_thumbnail_clone_id);
            if ($mediaToClone) {
                $mediaToClone->copy($post, 'post_thumbnail');
            }
        }

        if (!empty($request->post_thumbnail)) {
            $post->addMediaFromRequest('post_thumbnail')->toMediaCollection('post_thumbnail');
        }

        //tags
        $post->syncTagsFromRequest($request->post_categories, 'post_category', $request->auto_generate_category, [$request->title, $request->input('content')]);
        $post->syncTagsFromRequest($request->post_tags, 'post_tag', $request->auto_generate_tag, [$request->title, $request->input('content')]);

        //clear cache
        $this->flush();
        return redirect()->route('posts.edit', [
            'id' => $post->id,
        ]);
    }

    public function show($slug)
    {
        dd('Show is disabled in backend. Preview in frontend instead.');
    }

    public function edit($id)
    {
        $post = $this->modelClass::withTrashed()->find($id);
        if (!$post) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        if (isAdmin()) {
            $users = wncms()->getModel('user')::all();
            $websites = wncms()->getModel('website')::all();
        } else {
            $users = wncms()->getModel('user')::where('id', auth()->id())->get();
            $websites = auth()->user()->websites;
        }
        return $this->view('backend.posts.edit', [
            'page_title' => wncms_model_word('post', 'management'),
            'statuses' => $this->modelClass::STATUSES,
            'visibilities' => $this->modelClass::VISIBILITIES,
            'post_categories' => wncms()->tag()->getArray(tagType: "post_category", columnName: "name"),
            'post_tags' => wncms()->tag()->getArray(tagType: "post_tag", columnName: "name"),
            'users' => $users,
            'websites' => $websites,
            'post' => $post,
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $post = $this->modelClass::withTrashed()->find($id);
        if (!$post) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        if (isAdmin()) {
            $user = wncms()->getModel('user')::find($request->user_id) ?? auth()->user();
            $website_ids = $request->website_ids;
        } else {
            $user = auth()->user();
            $website_ids = auth()->user()->websites()->whereIn('id', $request->website_id)->pluck('id')->toArray();

            //只能修改自己的文章
            if ($post->user?->id != auth()->id()) {
                return back()->withInput()->withErrors(['message' => __('wncms::word.invalid_request')]);
            }
        }

        //TODO 改為用 FormRequest
        // if(empty($website_ids)) return back()->withInput()->withErrors(['message' => __('wncms::word.website_ids_is_required')]);
        if (!$user) return back()->withInput()->withErrors(['message' => __('wncms::word.user_not_found')]);

        $request->validate(
            [
                'title' => 'required|max:255',
                'status' => 'required',
                'visibility' => 'required',
                'price' => 'sometimes|nullable|numeric|max:999999.999',

            ],
            [
                'title.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.title')]),
                'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
                'visibility.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.visibility')]),
                'price.numeric' => __('wncms::word.field_should_be_numeric', ['field_name' => __('wncms::word.price')]),
                'price.max' => __('wncms::word.field_should_not_exceed', ['field_name' => __('wncms::word.price'), 'value' => '999999.999']),
            ]
        );

        $duplicate_slug = $this->modelClass::where('slug', $request->slug)->where('id', '<>', $post->id)->first();

        if ($duplicate_slug) {
            return back()->withInput()->withErros(['message' => __('wncms::word.duplicated_slug')]);
        }

        $post->update([
            'user_id' => $user->id,
            'status' => $request->input('status'),
            'visibility' => $request->input('visibility'),
            'external_thumbnail' => $request->input('external_thumbnail'),
            'slug' => $request->input('slug'),
            'title' => $request->input('title'),
            'label' => $request->input('label'),
            'excerpt' => $request->input('excerpt'),
            'content' => $request->input('content'),
            'remark' => $request->input('remark'),
            'sort' => $request->input('sort'),
            'password' => $request->input('password'),
            'price' => $request->input('price'),
            'is_pinned' => $request->input('is_pinned') ?? false,
            'is_recommended' => $request->input('is_recommended') ?? false,
            'is_dmca' => $request->input('is_dmca') ?? false,
            'published_at' => $request->input('published_at') ? Carbon::parse($request->input('published_at')) : Carbon::now(),
            'expired_at' => $request->input('expired_at') ? Carbon::parse($request->input('expired_at')) : null,
        ]);

        //handle content
        $post->localizeImages();
        $post->wrapTables();

        // $post->websites()->sync($website_ids);


        //remove image
        if (!empty($request->post_thumbnail_remove)) {
            $post->clearMediaCollection('post_thumbnail');
        }

        //thumbnail
        if (!empty($request->post_thumbnail)) {
            $post->addMediaFromRequest('post_thumbnail')->toMediaCollection('post_thumbnail');
        }

        //post_category
        $new_cateogories = [];
        if (!empty($request->post_categories)) {
            $categories = json_decode($request->post_categories);
            foreach ($categories as $category) {
                $new_cateogories[] = $category->value;
            }
        }
        $post->syncTagsWithType($new_cateogories, 'post_category');

        //post_tag
        $new_tags = [];
        if (!empty($request->post_tags)) {
            $tags = json_decode($request->post_tags);
            $new_tags = [];
            foreach ($tags as $tag) {
                $new_tags[] = $tag->value;
            }
        }
        $post->syncTagsWithType($new_tags, 'post_tag');


        //clear cache
        $this->flush();
        return redirect()->route('posts.edit', [
            'id' => $post->id,
        ]);
    }

    public function destroy($id)
    {
        $post = $this->modelClass::withTrashed()->find($id);
        if (!$post) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $post->update(['status' => 'trashed']);

        $post->delete();

        return redirect()->route('posts.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function restore($id)
    {
        $post = $this->modelClass::withTrashed()->find($id);
        if (!$post) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $post->update(['status' => gss('restore_trashed_content_to_published') ? 'published' : 'drafted']);

        $post->restore();

        return redirect()->route('posts.index')->withMessage(__('wncms::word.successfully_restored'));
    }

    public function bulk_clone(Request $request)
    {
        // info($request->all());
        parse_str($request->formData, $formData);
        $status = $formData['clone_status'] ?? 'drafted';

        if (isAdmin()) {
            $posts = $this->modelClass::whereIn('id', $request->model_ids)->get();
        } else {
            $user = auth()->user();
            $posts = $user->posts()->whereIn('id', $request->model_ids)->get();
        }

        $count = 0;
        foreach ($posts as $post) {
            $newPost = $post->replicate();
            $newPost->status = $status;
            $newPost->slug = wncms()->getUniqueSlug('posts');
            $newPost->push();

            //copy websites
            // $websiteIds = $post->websites()->pluck('websites.id')->toArray();
            // $newPost->websites()->sync($websiteIds);

            //copy thumbnail
            $thumbnail = $post->getMedia('post_thumbnail')->first();
            if ($thumbnail) {
                $mediaToClone = Media::find($thumbnail->id);
                if ($mediaToClone) {
                    $mediaToClone->copy($newPost, 'post_thumbnail');
                }
            }

            //copy category
            $categoryNams = $post->tagsWithType('post_category')->pluck('name')->toArray();
            $newPost->syncTagsWithType($categoryNams, 'post_category');

            //copy tags
            $tagNames = $post->tagsWithType('post_category')->pluck('name')->toArray();
            $newPost->syncTagsWithType($tagNames, 'post_tag');

            //copy content images
            $content = $post->content;
            preg_match_all('/<img[^>]+src="([^"]+)"/i', $content, $matches);

            foreach ($matches[1] as $imageUrl) {
                // $tempImageUrl = str_replace("../../..", $post->websites()->first()?->domain, $imageUrl);
                $tempImageUrl = str_replace("../../..", "/", $imageUrl);

                $tempImageUrl = wncms_add_https($tempImageUrl);

                if (!$post->imageUrlExists($tempImageUrl)) {
                    continue;
                }

                $imageContents = file_get_contents($tempImageUrl);
                $mediaContents = $imageContents;
                $sourcePath = parse_url($tempImageUrl, PHP_URL_PATH) ?: $tempImageUrl;
                $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);

                if (gss('convert_thumbnail_to_webp')) {
                    $mediaContents = $post->convertToWebp($imageContents);
                    $extension = 'webp';
                }

                if (empty($extension)) {
                    $extension = 'jpg';
                }

                $fileName = str()->random(16);

                $media = $newPost->addMediaFromString($mediaContents)
                    ->usingFileName("{$fileName}.{$extension}")
                    ->toMediaCollection('post_content'); // You can define your own collection name

                $mediaUrl = $newPost->removeDomainFromUrl($media->getUrl());
                $content = str_replace($imageUrl, $mediaUrl, $content);
            }

            // Update the post's content with localized image URLs
            $newPost->update(['content' => $content]);

            if ($newPost->wasRecentlyCreated) {
                $count++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_created_count', ['count' => $count]),
            'reload' => true,
        ]);
    }

    public function bulk_sync_tags(Request $request)
    {
        try {
            // info($request->all());
            parse_str($request->formData, $formDataArray);
            // info($formDataArray);

            if (empty($request->model_ids)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => __('wncms::word.model_ids_are_not_found'),
                    'restoreBtn' => true,
                ]);
            }

            //receive checked ids
            $posts = $this->modelClass::whereIn('id', $request->model_ids)->get();
            if ($posts->isEmpty()) {
                return response()->json([
                    'status' => 'fail',
                    'message' => __('wncms::word.post_is_not_fount'),
                    'restoreBtn' => true,
                ]);
            }

            //get action
            if (empty($formDataArray['action']) || !in_array($formDataArray['action'], ['sync', 'attach', 'detach'])) {
                return response()->json([
                    'status' => 'fail',
                    'message' => __('wncms::word.action_is_not_found'),
                    'restoreBtn' => true,
                ]);
            }

            $post_categories = collect(json_decode($formDataArray['post_categories'], true))->pluck('name')->toArray();
            // info($post_categories);

            $post_tags = collect(json_decode($formDataArray['post_tags'], true))->pluck('name')->toArray();
            // info($post_tags);

            foreach ($posts as $post) {
                if (($formDataArray['action'] == 'sync')) {
                    if (!empty($post_categories)) {
                        $post->syncTagsWithType($post_categories, 'post_category');
                    }
                    if (!empty($post_tags)) {
                        $post->syncTagsWithType($post_tags, 'post_tag');
                    }
                }

                if (($formDataArray['action'] == 'attach')) {
                    if (!empty($post_categories)) {
                        $post->attachTags($post_categories, 'post_category');
                    }
                    if (!empty($post_tags)) {
                        $post->attachTags($post_tags, 'post_tag');
                    }
                }

                if (($formDataArray['action'] == 'detach')) {
                    if (!empty($post_categories)) {
                        $post->detachTags($post_categories, 'post_category');
                    }
                    if (!empty($post_tags)) {
                        $post->detachTags($post_tags, 'post_tag');
                    }
                }
            }

            $this->flush();
            $this->flush(['tags']);

            return response()->json([
                'status' => 'success',
                'title' => __('wncms::word.success'),
                'message' => __('wncms::word.successfully_updated_all'),
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json([
                'status' => 'fail',
                'title' => __('wncms::word.failed'),
                'message' => __('wncms::word.error') . ": " . $e->getMessage(),
                'restoreBtn' => true,
            ]);
        }
    }

    public function generate_demo_posts(Request $request)
    {
        $website = wncms()->website()->get();

        $count = $request->count ?? 10;

        // Get tags from request
        // $tags = explode(",", $request->tag);

        // Get category from request
        // $category = $request->input('category');

        // Get images from placeholder directory
        $imageDirectory = public_path('wncms/images/placeholders');
        $imageFilenames = preg_grep('/^placeholder_16_9_/', scandir($imageDirectory));

        // Create Post model
        // $faker = Faker::create(config('app.locale', 'zh_TW'));

        // Get all supported locales
        $fakers = [];
        $locales = LaravelLocalization::getSupportedLocales();
        foreach ($locales as $localeCode => $localeData) {
            $fakers[$localeCode] = Faker::create($localeCode);
        }

        $categories = wncms()->getModel('tag')::query()->where('type', 'post_category')->inRandomOrder()->limit(3)->get();
        $tags = wncms()->getModel('tag')::query()->where('type', 'post_tag')->inRandomOrder()->limit(3)->get();

        for ($i = 0; $i < $count; $i++) {
            // Choose a random image filename
            $randomImageFilename = $fakers[config('app.locale')]->randomElement($imageFilenames);
            $imagePath = '/wncms/images/placeholders/' . $randomImageFilename;

            // Add Sub title to paragraphs
            $content = "";
            $paragraph_count = rand(2, 5);
            for ($j = 0; $j < $paragraph_count; $j++) {
                $paragraphTitle = $fakers[config('app.locale')]->realText(20, 5);
                $content .= "<h2>{$paragraphTitle}</h2>";
                $content .= "<p>" .  $fakers[config('app.locale')]->realText(500, 5) . "</p>";
            }

            // Create a new post
            $post = $this->modelClass::create([
                'user_id' => auth()->id(),
                'title' => $fakers[config('app.locale')]->realText(30, 5),
                'slug' => wncms()->getUniqueSlug('posts'),
                // 'content' => $fakers[config('app.locale')]->realText(500, 5),
                'content' => $content,
                'published_at' => now(),
                'external_thumbnail' => $imagePath,
            ]);

            // Set translations for the post title in all supported locales
            foreach ($locales as $localeCode => $localeData) {

                if (config('app.locale') != $localeCode) {
                    try {
                        // Create a faker instance for each locale
                        $translatedTitle = $fakers[$localeCode]->realText(30, 5);

                        $content = "";
                        $paragraph_count = rand(2, 5);
                        for ($j = 0; $j < $paragraph_count; $j++) {
                            $paragraphTitle = $fakers[$localeCode]->realText(20, 5);
                            $content .= "<h2>{$paragraphTitle}</h2>";
                            $content .= "<p>" .  $fakers[$localeCode]->realText(500, 5) . "</p>";
                        }
                    } catch (\Exception $e) {
                        // Fallback in case the locale doesn't support realText
                        logger()->error($e);
                        // $translatedTitle = $fakers[$localeCode]->realText(30, 5); // Default locale fallback
                    }

                    // Set the translation for the title
                    $post->setTranslation('title', $localeCode, $translatedTitle);
                    $post->setTranslation('content', $localeCode, $content);
                }
            }

            // add random existing categories
            // TODO: Add random existing categories
            if ($categories->count()) {
                $category_names = $categories->pluck('name');
                $post->syncTagsWithType($category_names, 'post_category');
            }

            // add random existing tags
            // TODO: Add random existing tags
            if ($tags->count()) {
                $tag_names = $tags->pluck('name');
                $post->syncTagsWithType($tag_names, 'post_tag');
            }

            // $post->websites()->sync($website->id);
        }

        $this->flush();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_created'),
            ]);
        }

        return back()->withMessage(__('wncms::word.successfully_created_count', ['count' => $count]));
    }

    // public function bulk_set_websites(Request $request)
    // {
    //     // info($request->all());

    //     parse_str($request->formData, $formData);
    //     $website = wncms()->getModel('website')::find($formData['website_id']);

    //     if (!$website) {
    //         return response()->json([
    //             'status' => 'fail',
    //             'message' => __('wncms::word.website_is_not_found'),
    //         ]);
    //     }

    //     if (isAdmin()) {
    //         $posts = $this->modelClass::whereIn('id', $request->model_ids)->get();
    //     } else {
    //         $posts = auth()->users()->posts()->whereIn('id', $request->model_ids)->get();
    //     }

    //     foreach ($posts as $post) {
    //         $post->websites()->syncWithoutDetaching($website->id);
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => __('wncms::word.successfully_updated'),
    //         'reload' => true,
    //     ]);
    // }
}
