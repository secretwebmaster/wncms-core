<?php

namespace Wncms\Http\Controllers\Frontend;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Wncms\Models\Post;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use CyrildeWit\EloquentViewable\Support\Period;

class PostController extends FrontendController
{
    /**
     * Show a single post by slug.
     */
    public function single(string $slug)
    {
        $post = wncms()->post()->get(['slug' => $slug, 'withs' => ['media', 'user', 'tags', 'comments']]);
        if (!$post) {
            return redirect()->route('frontend.pages.blog');
        }

        Event::dispatch('wncms.posts.single', $post);

        return $this->view("frontend.themes.{$this->theme}.posts.single", compact('post'));
    }

    /**
     * Show posts by tag.
     */
    public function tag(string $type, string $slug)
    {
        if (empty($slug)) {
            return redirect()->route('frontend.pages.home');
        }

        // get tag type meta
        $modelClass = wncms()->getModelClass('post');
        $tagMeta = collect(wncms()->tag()->getTagTypes(wncms()->getModelClass('post'), 'full'))->firstWhere('short', $type);
        $tagType = $tagMeta['key'] ?? '';
        if (empty($tagType)) {
            return redirect()->route('frontend.pages.home');;
        }

        // fetch tag by slug or name in current locale
        $tag = wncms()->tag()->get([
            'type'  => $tagType,
            'wheres' => [fn($q) => $q->where('slug', $slug)->orWhere('name', $slug)],
            'cache' => true,
        ]);

        if (!$tag) {
            return redirect()->route('frontend.posts.search.result', [
                'keyword' => $slug
            ]);
        }

        $posts = wncms()->post()->getList([
            'count' => gto('post_limit', 0),
            'page' => request('page', 0),
            'page_size' => gto('post_page_size', 10),
            'cache' => false,
            'tags' => [$tag->name],
            'tag_type' => $tagType,
        ]);

        return $this->view("frontend.themes.{$this->theme}.posts.archive", [
            'pageTitle' => __('wncms::word.latest_tag_models', [
                'tagName' => $slug,
                'modelName' => $modelClass::getModelName(),
            ]),
            'tagName' => $slug,
            'tagType' => $type,
            'posts' => $posts,
            'tag' => $tag,
        ]);
    }

    /**
     * Search request via POST.
     */
    public function search(Request $request)
    {
        if (empty($request->keyword)) {
            return back()->withErrors(['message' => __('wncms::word.keyword_is_empty')]);
        }

        return redirect()->route('frontend.posts.search.result', [
            'keyword' => $request->keyword,
        ]);
    }

    /**
     * Search results.
     */
    public function result(Request $request, string $keyword)
    {
        $posts = wncms()->post()->getList([
            'keywords' => $keyword,
            'search_fields' => gto('post_search_fields', ['title']),
            'page_size' => gto('archive_post_count', 10),
            'page' => $request->page,
        ]);

        return $this->view("frontend.themes.{$this->theme}.posts.search", [
            'pageTitle' => __('wncms::word.search_result_of', ['keyword' => $keyword]),
            'posts' => $posts,
            'keyword' => $keyword,
        ]);
    }

    /**
     * Ranking by period.
     */
    public function rank(Request $request, string $period = 'month')
    {
        $ranges = [
            'year' => Period::subYears(1),
            'month' => Period::subMonths(1),
            'week' => Period::subWeeks(1),
            'day' => Period::subDays(1),
        ];

        if (!array_key_exists($period, $ranges)) {
            return redirect()->route('frontend.posts.rank', ['period' => 'month']);
        }

        $posts = Post::orderByViews('desc', $ranges[$period])
            ->limit(gto('video_rank_page_size', 96))
            ->get();

        return $this->view("frontend.themes.{$this->theme}.posts.rank", [
            'pageTitle' => __('wncms::word.post_rank_of_period', [
                'period' => __('wncms::word.' . $period),
            ]),
            'pageName' => 'post_rank',
            'modelName' => 'post',
            'period' => $period,
            'posts' => $posts,
        ]);
    }

    /**
     * Predefined post lists (hot, like, fav, new).
     */
    public function post_list(Request $request, string $name, string $period = 'total')
    {
        $validPeriods = ['today', 'yesterday', 'week', 'month', 'total'];
        $period = in_array($period, $validPeriods) ? $period : 'total';

        $periodText = in_array($period, ['today', 'yesterday', 'week', 'month'])
            ? __('wncms::word.period_' . $period) . __('wncms::word.word_separator')
            : '';

        $options = [
            'count' => gto('post_limit', 0),
            'page' => request('page', 0),
            'page_size' => gto('post_page_size', 12),
        ];

        switch ($name) {
            case 'hot':
                $pageTitle = $periodText . __('wncms::word.hot_posts');
                $options['sort'] = 'view_month';
                break;
            case 'like':
                $pageTitle = $periodText . __('wncms::word.most_liked_posts');
                $options['sort'] = 'like';
                break;
            case 'fav':
                $pageTitle = $periodText . __('wncms::word.most_fav_posts');
                $options['sort'] = 'view_month';
                break;
            case 'new':
                $pageTitle = $periodText . __('wncms::word.latest_posts');
                $options['sort'] = 'created_at';
                break;
            default:
                return redirect()->route('frontend.pages.home');
        }

        $posts = wncms()->post()->getList($options);

        return $this->view("frontend.themes.{$this->theme}.posts.archive", [
            'page_title' => $pageTitle,
            'posts' => $posts,
            'show_post_filter' => true,
        ]);
    }

    public function create()
    {
        $tagOptions = ['tag_type' => 'post_category'];

        if (gto('user_allowed_post_category')) {
            $tagOptions['tag_ids'] = gto('user_allowed_post_category');
        }

        $categories = wncms()->tag()->getList($tagOptions);

        return $this->view("frontend.themes.{$this->theme}.posts.create", [
            'page_title' => __('wncms::word.post_management'),
            'statuses' => $this->modelClass::STATUSES,
            'visibilities' => $this->modelClass::VISIBILITIES,
            'categories' => $categories,
            'post' => new $this->modelClass,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->check()) {
            return back()->withInput()->withErrors(['message' => __('wncms::word.please_login_first')]);
        }

        // credit check
        if (!isAdmin()) {
            $requiredCredits = gto('credits_required_to_post', 0);
            $creditType = gto('credit_type_required_to_post', 'points');
            if ($requiredCredits > 0 && auth()->user()->getCredit($creditType) < $requiredCredits) {
                return back()->withInput()->withErrors([
                    'message' => __('wncms::word.insufficient_credit_to_post_with_requirement', [
                        'requirement_amount' => $requiredCredits,
                        'requirement_type' => __('wncms::word.' . $creditType),
                    ]),
                ]);
            }
        }

        $request->validate([
            'title' => 'required|max:255',
            'price' => 'sometimes|nullable|numeric'
        ]);

        // unique slug
        if ($request->slug && $this->modelClass::where('slug', $request->slug)->exists()) {
            return back()->withInput()->withErrors(['message' => __('wncms::word.duplicated_slug')]);
        }

        $post = $this->modelClass::create([
            'user_id' => auth()->id(),
            'status' => gto('default_post_status', 'draft'),
            'visibility' => gto('default_post_visibility', 'public'),
            'slug' => $request->slug ?? wncms()->getUniqueSlug('posts', 'slug', 8, 'lower'),
            'title' => $request->title,
            'label' => $request->label,
            'excerpt' => $request->excerpt,
            'content' => $request->input('content'),
            'remark' => $request->remark,
            'sort' => $request->sort,
            'password' => $request->password,
            'price' => $request->price,
            'published_at' => $request->published_at ? Carbon::parse($request->published_at) : Carbon::now(),
            'expired_at' => $request->expired_at ? Carbon::parse($request->expired_at) : null,
        ]);

        $post->localizeImages();
        $post->wrapTables();
        $post->websites()->sync([$this->website->id]);

        // thumbnail handling
        if ($request->post_thumbnail_remove) {
            $post->clearMediaCollection('post_thumbnail');
        }
        if ($request->post_thumbnail_clone_id) {
            if ($media = Media::find($request->post_thumbnail_clone_id)) {
                $media->copy($post, 'post_thumbnail');
            }
        }
        if ($request->hasFile('post_thumbnail')) {
            $post->addMediaFromRequest('post_thumbnail')->toMediaCollection('post_thumbnail');
        }

        // tags
        $allowedCats = $this->getUserAllowedTags('post_category');
        $categories = $allowedCats->whereIn('id', explode(",", $request->categories));
        $post->syncTagsWithType($categories, 'post_category');
        $post->syncTagsWithType(array_filter(explode(",", $request->tags)), 'post_tag');

        wncms()->cache()->tags('posts')->flush();

        return redirect()->route('frontend.posts.edit', ['post' => $post->id]);
    }

    public function edit($id)
    {
        $post = $this->modelClass::withTrashed()->find($id);

        if (!$post) {
            return back()->withErrors(['message' => __('wncms::word.post_not_found')]);
        }

        if (!isAdmin() && $post->user_id !== auth()->id()) {
            return back()->withErrors(['message' => __('wncms::word.invalid_request')]);
        }

        $categories = wncms()->tag()->getList([
            'tag_type' => 'post_category',
            'tag_ids' => gto('user_allowed_post_category') ?: null,
        ]);

        return $this->view("frontend.themes.{$this->theme}.posts.edit", [
            'page_title' => __('wncms::word.post_management'),
            'statuses' => $this->modelClass::STATUSES,
            'visibilities' => $this->modelClass::VISIBILITIES,
            'categories' => $categories,
            'post' => $post,
        ]);
    }

    public function update(Request $request, $id)
    {
        $post = $this->modelClass::withTrashed()->find($id);

        if (!$post) {
            return back()->withErrors(['message' => __('wncms::word.post_not_found')]);
        }

        if (!auth()->check()) {
            return back()->withErrors(['message' => __('wncms::word.please_login_first')]);
        }

        if (!isAdmin() && $post->user_id !== auth()->id()) {
            return back()->withErrors(['message' => __('wncms::word.invalid_request')]);
        }

        $request->validate([
            'title' => 'required|max:255',
            'price' => 'sometimes|nullable|numeric'
        ]);

        if ($request->slug && $this->modelClass::where('slug', $request->slug)->where('id', '<>', $post->id)->exists()) {
            return back()->withErrors(['message' => __('wncms::word.duplicated_slug')]);
        }

        $post->update([
            'status' => $request->status ?? $post->status,
            'visibility' => $request->visibility ?? $post->visibility,
            'slug' => $request->slug ?? $post->slug,
            'title' => $request->title,
            'label' => $request->label,
            'excerpt' => $request->excerpt,
            'content' => $request->input('content'),
            'remark' => $request->remark,
            'sort' => $request->sort,
            'password' => $request->password,
            'price' => $request->price,
            'published_at' => $request->published_at ? Carbon::parse($request->published_at) : $post->published_at,
            'expired_at' => $request->expired_at ? Carbon::parse($request->expired_at) : $post->expired_at,
        ]);

        $post->localizeImages();
        $post->wrapTables();

        if ($request->post_thumbnail_remove) {
            $post->clearMediaCollection('post_thumbnail');
        }
        if ($request->hasFile('post_thumbnail')) {
            $post->addMediaFromRequest('post_thumbnail')->toMediaCollection('post_thumbnail');
        }

        $allowedCats = $this->getUserAllowedTags('post_category');
        $categories = $allowedCats->whereIn('id', explode(",", $request->categories));
        $post->syncTagsWithType($categories, 'post_category');
        $post->syncTagsWithType(array_filter(explode(",", $request->tags)), 'post_tag');

        wncms()->cache()->tags('posts')->flush();

        return redirect()->route('frontend.posts.edit', ['post' => $post->id]);
    }

    /**
     * Helper: get user allowed tags.
     */
    public function getUserAllowedTags(string $type = 'post_category', bool $idOnly = false)
    {
        $categories = wncms()->tag()->getList([
            'tag_type' => $type,
            'tag_ids' => gto('user_allowed_' . $type) ?: null,
        ]);

        return $idOnly ? $categories->pluck('id')->toArray() : $categories;
    }
}
