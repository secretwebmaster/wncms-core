<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\User;
use Wncms\Models\Website;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Wncms\Models\Tag;
use Faker\Factory as Faker;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class PostController extends Controller
{
    protected $website;
    protected $theme;

    protected $post_categories;
    protected $post_tags;

    protected $model;

    public function __construct()
    {
        // TODO:: Add multi website support. Move to BaseModel abastract class
        $this->website = wncms()->website()->get();
        if (!$this->website) redirect()->route('websites.create')->send();
        $this->theme = $this->website->theme ?? 'default';

        $this->post_categories = wncms()->tag()->getArray(tagType: "post_category", columnName: "name");
        $this->post_tags = wncms()->tag()->getArray(tagType: "post_tag", columnName: "name");
    }

    /**
     * Get the model class that this controller works with.
     * Uses a setting from config/wncms.php and falls back to Post model if not set.
     */
    protected function getModelClass()
    {
        // Fetch the model class from the config file, or fall back to Post model
        return config('wncms.models.post', \Wncms\Models\Post::class);
    }

    protected function getModelTable()
    {
        $modelClass = $this->getModelClass();
        return (new $modelClass)->getTable();
    }

    public function index(Request $request)
    {
        $modelClass = $this->getModelClass();
        $q = $modelClass::query();

        if (!isAdmin()) {
            $q->whereRelation('user', 'id', auth()->id());
        }

        $selectedWebsiteId = $request->website ?? session('selected_website_id');
        if ($selectedWebsiteId) {
            $q->whereHas('websites', function ($subq) use ($selectedWebsiteId) {
                $subq->where('websites.id', $selectedWebsiteId);
            });
        } elseif (!$request->has('website') && config('wncms.multi_website', false)) {
            $websiteId = wncms()->website()->get()?->id;
            $q->whereHas('websites', function ($subq) use ($websiteId) {
                $subq->where('websites.id', $websiteId);
            });
        }

        if (in_array($request->status, $modelClass::STATUSES)) {
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

        if (in_array($request->order, $modelClass::ORDERS)) {
            if (in_array($request->order, ['traffics', 'clicks'])) {
                $q->orderBy($request->order . '_count', in_array($request->sort, ['asc', 'desc']) ? $request->sort : 'desc');
            } else {
                $q->orderBy($request->order, in_array($request->sort, ['asc', 'desc']) ? $request->sort : 'desc');
            }
        }

        $q->with(['media', 'tags', 'websites']);

        $q->orderBy('created_at', 'desc');
        $q->orderBy('id', 'desc');
        $posts = $q->paginate($request->page_size ?? 20);

        $post_category_parants = Tag::where('type', 'post_category')->whereNull('parent_id')->with('children')->get();

        return view('wncms::backend.posts.index', [
            'page_title' => __('wncms::word.post_management'),
            'posts' => $posts,
            'post_category_parants' => $post_category_parants,
            'orders' => $modelClass::ORDERS,
            'statuses' => $modelClass::STATUSES,
            'visibilities' => $modelClass::VISIBILITIES,
            'websites' => wncms()->website()->getList(),
        ]);
    }

    public function create(Request $request, $post = null)
    {
        $modelClass = $this->getModelClass();
        $post = $modelClass::find($post);

        if (isAdmin()) {
            $users = User::all();
            $websites = Website::all();
        } else {
            $users = User::where('id', auth()->id())->get();
            $websites = auth()->user()->websites;
        }

        return view('wncms::backend.posts.create', [
            'page_title' => __('wncms::word.post_management'),
            'statuses' => $modelClass::STATUSES,
            'visibilities' => $modelClass::VISIBILITIES,
            'post_categories' => $this->post_categories,
            'post_tags' => $this->post_tags,
            'users' => $users,
            'websites' => $websites,
            'post' => $post ?? new $modelClass,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        // $modelClass = $this->getModelClass();

        if (isAdmin()) {
            $user = User::find($request->user_id) ?? auth()->user();
            $website_ids = Website::whereIn("websites.id", $request->website_ids ?? [])->pluck('id')->toArray();
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
                'price' => 'sometimes|nullable|numeric'

            ],
            [
                'title.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.title')]),
                'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
                'visibility.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.visibility')]),
                'price.numeric' => __('wncms::word.field_should_be_numeric', ['field_name' => __('wncms::word.price')]),
            ]
        );

        $post = $user->posts()->create([
            'status' => $request->status,
            'visibility' => $request->visibility,
            'external_thumbnail' => $request->external_thumbnail,
            'slug' => wncms_get_unique_slug('posts', 'slug', 8, 'lower'),
            'title' => $request->title,
            'label' => $request->label,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'remark' => $request->remark,
            'order' => $request->order,
            'password' => $request->password,
            'price' => $request->price,
            'is_pinned' => $request->is_pinned ?? false,
            'is_recommended' => $request->is_recommended ?? false,
            'is_dmca' => $request->is_dmca ?? false,
            'published_at' => $request->published_at ? Carbon::parse($request->published_at) : Carbon::now(),
            'expired_at' => $request->expired_at ? Carbon::parse($request->expired_at) : null,
        ]);

        //handle content
        $post->localizeImages();
        $post->wrapTables();

        //attach to website models
        $post->websites()->sync($website_ids);

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
        $post->syncTagsFromRequest($request->post_categories, 'post_category', $request->auto_generate_category, [$request->title, $request->content]);
        $post->syncTagsFromRequest($request->post_tags, 'post_tag', $request->auto_generate_tag, [$request->title, $request->content]);

        //clear cache
        wncms()->cache()->tags('posts')->flush();
        return redirect()->route('posts.edit', $post->id);
    }

    public function show($slug)
    {
        $post = $modelClass::where('slug', $slug)->first();
        if (!$post) return redirect()->route('frontend.pages.home');
        return view('wncms::frontend.theme.default.posts.single', [
            'post' => $post,
        ]);
    }

    public function edit($postId)
    {
        $modelClass = $this->getModelClass();
        $post = $modelClass::withTrashed()->find($postId);
        if (isAdmin()) {
            $users = User::all();
            $websites = Website::all();
        } else {
            $users = User::where('id', auth()->id())->get();
            $websites = auth()->user()->websites;
        }
        return view('wncms::backend.posts.edit', [
            'page_title' => __('wncms::word.post_management'),
            'statuses' => $modelClass::STATUSES,
            'visibilities' => $modelClass::VISIBILITIES,
            'post_categories' => $this->post_categories,
            'post_tags' => $this->post_tags,
            'users' => $users,
            'websites' => $websites,
            'post' => $post,
        ]);
    }

    public function update(Request $request, $post)
    {
        // dd($request->all());
        $modelClass = $this->getModelClass();
        $post = $modelClass::withTrashed()->find($post);
        if (!$post) return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.post_not_found')]);

        if (isAdmin()) {
            $user = User::find($request->user_id) ?? auth()->user();
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
                'price' => 'sometimes|nullable|numeric'

            ],
            [
                'title.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.title')]),
                'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
                'visibility.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.visibility')]),
                'price.numeric' => __('wncms::word.field_should_be_numeric', ['field_name' => __('wncms::word.price')]),
            ]
        );

        $duplicate_slug = $modelClass::where('slug', $request->slug)->where('id', '<>', $post->id)->first();

        if ($duplicate_slug) {
            return back()->withInput()->withErros(['message' => __('wncms::word.duplicated_slug')]);
        }

        $post->update([
            'user_id' => $user->id,
            'status' => $request->status,
            'visibility' => $request->visibility,
            'external_thumbnail' => $request->external_thumbnail,
            'slug' => $request->slug,
            'title' => $request->title,
            'label' => $request->label,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'remark' => $request->remark,
            'order' => $request->order,
            'password' => $request->password,
            'price' => $request->price,
            'is_pinned' => $request->is_pinned ?? false,
            'is_recommended' => $request->is_recommended ?? false,
            'is_dmca' => $request->is_dmca ?? false,
            'published_at' => $request->published_at ? Carbon::parse($request->published_at) : Carbon::now(),
            'expired_at' => $request->expired_at ? Carbon::parse($request->expired_at) : null,
        ]);

        //handle content
        $post->localizeImages();
        $post->wrapTables();

        $post->websites()->sync($website_ids);


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
        wncms()->cache()->tags('posts')->flush();
        return redirect()->route('posts.edit', $post->id);
    }

    public function destroy($id)
    {
        $modelClass = $this->getModelClass();
        $post = $modelClass::withTrashed()->find($id);
        if ($post) {
            $post->update(['status' => 'trashed']);
            $post->delete();
        }
        return redirect()->route('posts.index')->withMessage(__('wncms::word.successfully_deleted'));;
    }

    public function restore($id)
    {
        $modelClass = $this->getModelClass();
        $post = $modelClass::withTrashed()->find($id);

        if ($post) {
            $post->update(['status' => gss('restore_trashed_content_to_published') ? 'published' : 'drafted']);
            $post->restore();
            return redirect()->route('posts.index')->withMessage(__('wncms::word.successfully_restored'));
        } else {
            return back()->withErrors(['message' => __('wncms::word.successfully_restored')]);
        }
    }

    public function bulk_clone(Request $request)
    {
        // info($request->all());
        $modelClass = $this->getModelClass();
        parse_str($request->formData, $formData);
        $status = $formData['clone_status'] ?? 'drafted';

        if (isAdmin()) {
            $posts = $modelClass::whereIn('id', $request->model_ids)->get();
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
            $websiteIds = $post->websites()->pluck('websites.id')->toArray();
            $newPost->websites()->sync($websiteIds);

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
                $tempImageUrl = str_replace("../../..", $post->websites()->first()?->domain, $imageUrl);
                $tempImageUrl = wncms_add_https($tempImageUrl);

                if (!$post->imageUrlExists($tempImageUrl)) {
                    continue;
                }

                $imageContents = file_get_contents($tempImageUrl);
                $webpImageContents = $post->convertToWebp($imageContents);

                $fileName = str()->random(16);
                $extension = 'webp';

                $media = $newPost->addMediaFromString($webpImageContents)
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
            info($request->all());
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
            $modelClass = $this->getModelClass();
            $posts = $modelClass::whereIn('id', $request->model_ids)->get();
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

            wncms()->cache()->flush(['posts', 'tags']);

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
        $modelClass = $this->getModelClass();

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

        $categories = Tag::query()->where('type', 'post_category')->inRandomOrder()->limit(3)->get();
        $tags = Tag::query()->where('type', 'post_tag')->inRandomOrder()->limit(3)->get();

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
            $post = $modelClass::create([
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

            $post->websites()->sync($website->id);
        }

        wncms()->cache()->flush(['posts']);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_created'),
            ]);
        }

        return back()->withMessage(__('wncms::word.successfully_created_count', ['count' => $count]));
    }

    public function bulk_set_websites(Request $request)
    {
        // info($request->all());

        parse_str($request->formData, $formData);
        $website = Website::find($formData['website_id']);

        if (!$website) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::word.website_is_not_found'),
            ]);
        }

        $modelClass = $this->getModelClass();

        if (isAdmin()) {
            $posts = $modelClass::whereIn('id', $request->model_ids)->get();
        } else {
            $posts = auth()->users()->posts()->whereIn('id', $request->model_ids)->get();
        }

        foreach ($posts as $post) {
            $post->websites()->syncWithoutDetaching($website->id);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_updated'),
            'reload' => true,
        ]);
    }
}
