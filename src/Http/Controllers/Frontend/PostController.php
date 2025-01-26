<?php

namespace Wncms\Http\Controllers\Frontend;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Wncms\Facades\Wncms;
use Wncms\Models\Post;


class PostController extends FrontendController
{
    /**
     * Show the single post
     * 
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function single($slug)
    {
        $post = Wncms::post()->getBySlug($slug);
        if (!$post) return redirect()->route('frontend.pages.blog');

        Event::dispatch('wncms.posts.single', $post);

        return wncms_view('frontend.theme.' . $this->theme . '.posts.single', [
            'post' => $post,
        ]);
    }

    /**
     * Show the post list by category
     * @param string $tagName
     * 
     * TODO: merge with tag
     */
    public function category($tagName = null)
    {
        if(empty($tagName)){
            return route('frontend.pages.home');
        }
        return $this->archive('post_category', $tagName);
    }

    /**
     * Show the post list by tag
     * @param string $tagName
     * @return \Illuminate\View\View
     */
    public function tag($tagName = null)
    {
        if(empty($tagName)){
            return route('frontend.pages.home');
        }
        return $this->archive('post_tag', $tagName);
    }

    /**
     * Show the post list by tag type and tag name
     * @param string $tagType
     * @param string $tagName
     * @return \Illuminate\View\View
     */
    public function archive($tagType, $tagName = null)
    {
        if(empty($tagName)){
            return route('frontend.pages.home');
        }

        $tag = Wncms::tag()->getByName(
            tagName:$tagName,
            tagType:$tagType,
            withs: ['posts', 'posts.media', 'children'],
        );
        
        if(!$tag) return redirect()->route('frontend.posts.search_result', [
            'keyword' => $tagName,
        ]);


        $modelName = explode("_", $tagType)[0] ?? '';

        $count = gto('post_limit', 0);
        $page = request()->page ?? 0;
        $pageSize = gto('post_page_size', 10);
        $posts = $tag->getPostList(count:$count,page:$page,pageSize:$pageSize);

        return wncms_view('frontend.theme.' . $this->theme . '.posts.archive', [
            'pageTitle' => __('wncms::word.latest_tag_models', ['tagName' => $tagName, 'modelName' => __('wncms::word.' . $modelName)]),
            'tagName' => $tagName,
            'tagType' => $tagType,
            'posts' => $posts,
            'tag' => $tag,
        ]);
    }

    /**
     * Process search request via post method
     * @param Request $request
     */
    public function search(Request $request)
    {
        if(empty($request->keyword)){
            return back()->withErrors(['message' => __('wncms::word.keyword_is_empty')]);
        }
        // TODO: search limit

        // TODO: filter keywords

        //search keyword
        return redirect()->route('frontend.posts.search_result', [
            'keyword' => $request->keyword,
        ]);
    }

    /**
     * Show the search result
     * @param Request $request
     * @param string $keyword
     * @return \Illuminate\View\View
     */
    public function search_result(Request $request, $keyword)
    {
        // TODO: add to gss or gto
        $pageSize = gto('archive_post_count', 10);
        $posts = Wncms::post()->search(
            keyword: $keyword,
            pageSize: $pageSize,
            page:$request->page,
        );

        return wncms_view('frontend.theme.' . $this->theme . '.posts.search', [
            'pageTitle' => __('wncms::word.search_result_of', ['keyword' => $keyword]),
            'posts' => $posts,
            'keyword' => $keyword,
        ]);
    }

    /**
     * Show the post rank
     * @param Request $request
     * @param string $period
     * @return \Illuminate\View\View
     */
    public function rank(Request $request, $period = 'month')
    {
        $pageSize = gto('video_rank_page_size', 96);

        $ranges = [
            'year' => Period::subYears(1),
            'month' => Period::subMonths(1),
            'week' => Period::subWeeks(1),
            'day' => Period::subDays(1),
        ];

        if(!array_key_exists($period, $ranges)){
            return redirect()->route('frontend.posts.rank.period', [
                'period' => 'month',
            ]);
        }

        $posts = Post::orderByViews('desc', $ranges[$period])->limit($pageSize)->get();

        return wncms_view('frontend.theme.' . $this->theme . '.posts.rank', [
            'pageTitle' => __('wncms::word.post_rank_of_period', ['period' => __('wncms::word.' . $period)]),
            'pageName' => 'post_rank',
            'modelName' => 'post',
            'period' => $period,
            'posts' => $posts,
        ]);
    }

    /**
     * Show a collection of specific posts
     * @param Request $request
     * @param string $name
     * @param string $period
     * @return \Illuminate\View\View
     * 
     * TODO: 製作list
     */
    public function post_list(Request $request, $name, $period = 'total')
    {

        $website = Wncms::website()->get();
        if (!$website) return redirect()->route('websites.create');
        
        $theme = $website->theme ?? 'default';

        $column = [
            'today' => 'traffic_today',
            'yesterday' => 'traffic_yesterday',
            'week' => 'traffic_week',
            'month' => 'traffic_month',
        ];

        $period = in_array($period, ['today', 'yesterday', 'week','month', 'total']) ? $period : 'total';

        $period_text = in_array($period, ['today', 'yesterday', 'week', 'month']) ? __('wncms::word.period_' . $period) . __('wncms::word.word_separator'): '';

        $count = gto('post_limit', 0);
        $page = request()->page ?? 0;
        $pageSize = gto('post_page_size', 12);

        if($name == 'hot'){
            $page_title = $period_text . __('wncms::word.hot_posts');
            $posts = Wncms::post()->getList(order:'view_month',count:$count,page:$page,pageSize:$pageSize);
        }

        if($name == 'like'){
            $page_title = $period_text . __('wncms::word.most_liked_posts');
            $posts = Wncms::post()->getList(order:'like',count:$count,page:$page,pageSize:$pageSize);
        }
        
        if($name == 'fav'){
            $page_title = $period_text . __('wncms::word.most_fav_posts');
            $posts = Wncms::post()->getList(order:'view_month',count:$count,page:$page,pageSize:$pageSize);
        }
        
        if($name == 'new'){
            $page_title = $period_text . __('wncms::word.latest_posts');
            $posts = Wncms::post()->getList(order:'created_at',count:$count,page:$page,pageSize:$pageSize);
        }

        return view("wncms::frontend.theme.$theme.posts.archive", [
            'page_title' => $page_title ?? '',
            'posts' => $posts,
            'show_post_filter' => true,
        ]);
    }


    //! CURD
    /**
     * Get the model class that this controller works with.
     * Uses a setting from config/wncms.php and falls back to Post model if not set.
     */
    protected function getModelClass()
    {
        // Fetch the model class from the config file, or fall back to Post model
        return config('wncms.default_post_model', \Wncms\Models\Post::class);
    }

    public function create()
    {
        //TODO check permission here
        $modelClass = $this->getModelClass();

        if(gto('user_allowed_post_category')){
            $categories = wncms()->tag()->getList(tagType: "post_category", tagIds: gto('user_allowed_post_category'));
        }else{
            $categories = wncms()->tag()->getList(tagType: "post_category");
        }

        return wncms()->view(
            "frontend.theme.{$this->theme}.posts.create",
            [
                'page_title' => __('wncms::word.post_management'),
                'statuses' => $modelClass::STATUSES,
                'visibilities' => $modelClass::VISIBILITIES,
                'categories' => $categories,
                'post' => new $modelClass,
            ]
        );
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $modelClass = $this->getModelClass();

        if(!auth()->check()){
            return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.please_login_first')]);
        }

        if (!isAdmin()) {
            $credistRequitedToPost = gto('credits_required_to_post', 0);
            $creditType = gto('credit_type_required_to_post', 'points');
            if ($credistRequitedToPost > 0) {
                if (auth()->user()->getCredit($creditType) < $credistRequitedToPost) {
                    return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.insufficient_credit_to_post_with_requirement', ['requirement_amount' => $credistRequitedToPost, 'requirement_type' => __('wncms::word.' . $creditType)])]);
                }
            }
        }

        $request->validate(
            [
                'title' => 'required|max:255',
                'price' => 'sometimes|nullable|numeric'
            ],
            [
                'title.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.title')]),
                'price.numeric' => __('wncms::word.field_should_be_numeric', ['field_name' => __('wncms::word.price')]),
            ]
        );

        // check slug
        if(!empty($request->slug)){
            $duplicate_slug = $modelClass::where('slug', $request->slug)->first();
            if ($duplicate_slug) {
                return back()->withInput()->withErros(['message' => __('wncms::word.duplicated_slug')]);
            }
        }

        $status = gto('default_post_status', 'draft');
        $visibility = gto('default_post_visibility', 'public');
        $slug = $request->slug ?? wncms()->getUniqueSlug('posts', 'slug', 8, 'lower');
        $title = $request->title; // TODO: check for forbidden words
        $label = $request->label; // TODO: check for forbidden words
        $excerpt = $request->excerpt; // TODO: check for forbidden words
        $content = $request->content; // TODO: check for forbidden words
        
        $remark = $request->remark; // TODO: check for forbidden words
        $order = $request->order;
        $password = $request->password;
        $price = $request->price;

        $is_pinned = false; // TODO: check for permission to set this
        $is_recommended = false; // TODO: check for permission to set this
        $is_dmca = false; // TODO: check for permission to set this
        $published_at = $request->published_at ? Carbon::parse($request->published_at) : Carbon::now(); // TODO: check for permission to set this
        $expired_at =$request->expired_at ? Carbon::parse($request->expired_at) : null; // TODO: check for permission to set this

        // categories
        // check if category is in allowed list
        $userAllowedPostCategories = $this->getUserAllowedTags('post_category');
        $categories =  $userAllowedPostCategories->whereIn('id', explode("," , $request->categories));

        $post = $modelClass::create([
            'user_id' => auth()->id(),
            'status' => $status,
            'visibility' => $visibility,
            'external_thumbnail' => $request->external_thumbnail,
            'slug' => $slug,
            'title' => $title,
            'label' => $label,
            'excerpt' => $excerpt,
            'content' => $content,
            'remark' => $remark,
            'order' => $order,
            'password' => $password,
            'price' => $price,
            'is_pinned' => $is_pinned,
            'is_recommended' => $is_recommended,
            'is_dmca' => $is_dmca,
            'published_at' => $published_at,
            'expired_at' => $expired_at,
        ]);

        //handle content
        $post->localizeImages();
        $post->wrapTables();

        //attach to website models
        $post->websites()->sync([$this->website->id]);

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

        // update categories
        $post->syncTagsWithType($categories, 'post_category');

        // update tags
        $tags = array_filter(explode(",", $request->tags));
        $post->syncTagsWithType($tags, 'post_tag');

        //clear cache
        wncms()->cache()->tags('posts')->flush();
        return redirect()->route('frontend.posts.edit', [
            'post' => $post->id,
        ]);
    }

    public function edit($post)
    {
        $modelClass = $this->getModelClass();
        $post = $modelClass::withTrashed()->find($post);
        if (!$post) return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.post_not_found')]);

        if (isAdmin()) {
            // allow edit others
        } else {
            // check if user is allowed to edit this post
            if ($post->user_id != auth()->id()) {
                return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.invalid_request')]);
            }
        }

        if(gto('user_allowed_post_category')){
            $categories = wncms()->tag()->getList(tagType: "post_category", tagIds: gto('user_allowed_post_category'));
        }else{
            $categories = wncms()->tag()->getList(tagType: "post_category");
        }
        
        return wncms()->view(
            "frontend.theme.{$this->theme}.posts.edit",
            [
                'page_title' => __('wncms::word.post_management'),
                'statuses' => $modelClass::STATUSES,
                'visibilities' => $modelClass::VISIBILITIES,
                'categories' => $categories,
                'post' => $post,
            ]
        );
    }

    public function update(Request $request, $post)
    {
        // dd($request->all());
        $modelClass = $this->getModelClass();
        $post = $modelClass::withTrashed()->find($post);
        if (!$post) return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.post_not_found')]);

        if(!auth()->check()){
            return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.please_login_first')]);
        }

        if (isAdmin()) {
            // allow edit others
        } else {
            // check if user is allowed to edit this post
            if ($post->user_id != auth()->id()) {
                return redirect()->back()->withInput()->withErrors(['message' => __('wncms::word.invalid_request')]);
            }
        }

        $request->validate(
            [
                'title' => 'required|max:255',
                'price' => 'sometimes|nullable|numeric'
            ],
            [
                'title.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.title')]),
                'price.numeric' => __('wncms::word.field_should_be_numeric', ['field_name' => __('wncms::word.price')]),
            ]
        );

        // check slug
        if(!empty($request->slug)){
            $duplicate_slug = $modelClass::where('slug', $request->slug)->where('id', '<>', $post->id)->first();
            if ($duplicate_slug) {
                return back()->withInput()->withErros(['message' => __('wncms::word.duplicated_slug')]);
            }
        }

        $status = $request->status ?? $post->status;
        $visibility = $request->visibility ?? $post->visibility;
        $slug =  $request->slug ?? wncms()->getUniqueSlug('posts', 'slug', 8, 'lower');
        $title = $request->title; // TODO: check for forbidden words
        $label = $request->label; // TODO: check for forbidden words
        $excerpt = $request->excerpt; // TODO: check for forbidden words
        $content = $request->content; // TODO: check for forbidden words
        
        $remark = $request->remark; // TODO: check for forbidden words
        $order = $request->order;
        $password = $request->password;
        $price = $request->price;

        $is_pinned = false; // TODO: check for permission to set this
        $is_recommended = false; // TODO: check for permission to set this
        $is_dmca = false; // TODO: check for permission to set this
        $published_at = $request->published_at ? Carbon::parse($request->published_at) : $post->published_at; // TODO: check for permission to set this
        $expired_at =$request->expired_at ? Carbon::parse($request->expired_at) : $post->expired_at; // TODO: check for permission to set this

        // validate categories
        // check if category is in allowed list
        $userAllowedPostCategories = $this->getUserAllowedTags('post_category');
        $categories =  $userAllowedPostCategories->whereIn('id', explode("," , $request->categories));



        // update post
        $post->update([
            'status' => $status,
            'visibility' => $visibility,
            'external_thumbnail' => $request->external_thumbnail,
            'slug' => $slug,
            'title' => $title,
            'label' => $label,
            'excerpt' => $excerpt,
            'content' => $content,
            'remark' => $remark,
            'order' => $order,
            'password' => $password,
            'price' => $price,
            'is_pinned' => $is_pinned,
            'is_recommended' => $is_recommended,
            'is_dmca' => $is_dmca,
            'published_at' => $published_at,
            'expired_at' => $expired_at,
        ]);

        //handle content
        $post->localizeImages();
        $post->wrapTables();

        // remove thumbnail
        if (!empty($request->post_thumbnail_remove)) {
            $post->clearMediaCollection('post_thumbnail');
        }

        // update thumbnail
        if (!empty($request->post_thumbnail)) {
            $post->addMediaFromRequest('post_thumbnail')->toMediaCollection('post_thumbnail');
        }

        // update categories
        $post->syncTagsWithType($categories, 'post_category');

        // update tags
        $tags = array_filter(explode(",", $request->tags));
        $post->syncTagsWithType($tags, 'post_tag');

        //clear cache
        wncms()->cache()->tags('posts')->flush();
        return redirect()->route('frontend.posts.edit', [
            'post' => $post->id,
        ]);
    }


    public function getUserAllowedTags($type = 'post_category', $idOnly = false)
    {
        if(gto('user_allowed_' . $type)){
            $categories = wncms()->tag()->getList(tagType: $type, tagIds: gto('user_allowed_' . $type));
        }else{
            $categories = wncms()->tag()->getList(tagType: $type);
        }

        if($idOnly){
            return $categories->pluck('id')->toArray();
        }

        return $categories;
    }
}
