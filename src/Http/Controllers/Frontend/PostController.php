<?php

namespace Wncms\Http\Controllers\Frontend;

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
}
