<?php

namespace Wncms\Http\Controllers\Frontend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    protected $website;
    protected $theme;

    public function __construct()
    {
        $this->website = wncms()->website()->get();
        if (!$this->website) redirect()->route('websites.create')->send();
        $this->theme = $this->website->theme ?? 'default';
    }

    public function single($slug)
    {
        $post = wncms()->post()->getBySlug($slug);
        if (!$post) return redirect()->route('frontend.pages.blog');

        // TODO: record view can now be toggle in theme option and call dynamically in template
        // RecordViews::dispatch($post->id);

        // post event
        event('frontend.model.single', $post);
        event('frontend.post.single', $post);

        return wncms_view('frontend.theme.' . $this->theme . '.posts.single', [
            'post' => $post,
        ]);
    }

    public function category($tagName = null)
    {
        if(empty($tagName)){
            return route('frontend.pages.home');
        }
        return $this->archive('post_category', $tagName);
    }

    public function tag($tagName = null)
    {
        if(empty($tagName)){
            return route('frontend.pages.home');
        }
        return $this->archive('post_tag', $tagName);
    }

    public function archive($tagType, $tagName = null)
    {
        // dd($tagName,$tagType);
        if(empty($tagName)){
            return route('frontend.pages.home');
        }

        $tag = wncms()->tag()->getByName(
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
            'pageTitle' => __('word.latest_tag_models', ['tagName' => $tagName, 'modelName' => __('word.' . $modelName)]),
            'tagName' => $tagName,
            'tagType' => $tagType,
            'posts' => $posts,
            'tag' => $tag,
        ]);
    }

    public function search(Request $request)
    {
        if(empty($request->keyword)){
            return back()->withErrors(['message' => __('word.keyword_is_empty')]);
        }
        // TODO: search limit

        // TODO: filter keywords

        //search keyword
        return redirect()->route('frontend.posts.search_result', [
            'keyword' => $request->keyword,
        ]);
    }

    public function search_result(Request $request, $keyword)
    {
        // TODO: add to gss or gto
        $pageSize = gto('archive_post_count', 10);
        $posts = wncms()->post()->search(
            keyword: $keyword,
            pageSize: $pageSize,
            page:$request->page,
        );

        return wncms_view('frontend.theme.' . $this->theme . '.posts.search', [
            'pageTitle' => __('word.search_result_of', ['keyword' => $keyword]),
            'posts' => $posts,
            'keyword' => $keyword,
        ]);
    }

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
            'pageTitle' => __('word.post_rank_of_period', ['period' => __('word.' . $period)]),
            'pageName' => 'post_rank',
            'modelName' => 'post',
            'period' => $period,
            'posts' => $posts,
        ]);
    }

    // TODO: 製作list
    public function post_list(Request $request, $name, $period = 'total')
    {

        $website = wncms()->website()->get();
        if (!$website) return redirect()->route('websites.create');
        
        $theme = $website->theme ?? 'default';

        $column = [
            'today' => 'traffic_today',
            'yesterday' => 'traffic_yesterday',
            'week' => 'traffic_week',
            'month' => 'traffic_month',
        ];

        $period = in_array($period, ['today', 'yesterday', 'week','month', 'total']) ? $period : 'total';

        $period_text = in_array($period, ['today', 'yesterday', 'week', 'month']) ? __('word.period_' . $period) . __('word.word_separator'): '';

        $count = gto('post_limit', 0);
        $page = request()->page ?? 0;
        $pageSize = gto('post_page_size', 12);

        if($name == 'hot'){
            $page_title = $period_text . __('word.hot_posts');
            $posts = wncms()->post()->getList(order:'view_month',count:$count,page:$page,pageSize:$pageSize);
        }

        if($name == 'like'){
            $page_title = $period_text . __('word.most_liked_posts');
            $posts = wncms()->post()->getList(order:'like',count:$count,page:$page,pageSize:$pageSize);
        }
        
        if($name == 'fav'){
            $page_title = $period_text . __('word.most_fav_posts');
            $posts = wncms()->post()->getList(order:'view_month',count:$count,page:$page,pageSize:$pageSize);
        }
        
        if($name == 'new'){
            $page_title = $period_text . __('word.latest_posts');
            $posts = wncms()->post()->getList(order:'created_at',count:$count,page:$page,pageSize:$pageSize);
        }

        return view("frontend.theme.$theme.posts.archive", [
            'page_title' => $page_title ?? '',
            'posts' => $posts,
            'show_post_filter' => true,
        ]);
    }
 
}
