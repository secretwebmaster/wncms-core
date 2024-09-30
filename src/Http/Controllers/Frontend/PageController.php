<?php

namespace Wncms\Http\Controllers\Frontend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Page;

class PageController extends Controller
{
    /**
     * Home page
     */
    public function home()
    {
        $website = wncms()->website()->get();
        if (!$website) return redirect()->route('websites.create');

        //get theme option to get custom home page

        //send to home blade if no custom theme option is found
        return $this->getFrontendPageView(
            theme: $website->theme,
            pageNmae: 'home',
            params: [
                'page_title' => $website->site_name ?? __('word.homepage'),
                'pageId' => 'home',
            ],
        );
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Default core blog page
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.0.0
     * @param $posts Eloquent Collection of posts
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     * ----------------------------------------------------------------------------------------------------
     */
    public function blog($posts = null)
    {
        $website = wncms()->website()->get();
        if (!$website) return redirect()->route('websites.create');

        $posts ??= wncms()->post()->getList(
            count: 100,
            pageSize: gto('default_page_size', 10),
        );

        return $this->getFrontendPageView(
            theme: $website->theme,
            pageNmae: 'blog', 
            params: [
                'pageTitle' => gto('blog_title', __('word.latest_posts')),
                'page' => null,
                'posts' => $posts
            ],
            fallbackRoute: 'frontend.pages.home',
        );
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Theme single page
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.1.15

     * @param string|null $slug Page slug
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     * ----------------------------------------------------------------------------------------------------
     */
    public function single($slug = null)
    {
        if (!$slug) return redirect()->route('frontend.pages.home');

        $website = wncms()->website()->get();
        if (!$website) return redirect()->route('websites.create');

        //get page model
        $page = wncms()->page()->getBySlug(slug:$slug);

        //get template
        if($page){
            // TODO: render page builder

            // load theme template with specific name as same as the page slug
            if($page->type == 'template'){
                $pageTemplateView = "frontend.theme.{$page->website?->theme}.pages." . $page->blade_name;
                if (view()->exists($pageTemplateView)) {
                    return view($pageTemplateView, [
                        'page' => $page,
                        'pageTitle' => $page->title,
                    ]);
                }
            }

            // load theme single page template
            if($page->type == 'plain'){
                //get singe view if exist
                $singlePageView = "frontend.theme.{$website->theme}.pages.single";
                if (view()->exists($singlePageView)) {
                    return view($singlePageView, [
                        'page' => $page,
                        'pageTitle' => $page->title,
                    ]);
                }
            }
        }

        // page model does not exist. Load default static page
        if (view()->exists("frontend.theme.{$website?->theme}.pages." . $slug)) {
            return view("wncms::frontend.theme.{$website?->theme}.pages." . $slug, [
                'page' => $page ?? new Page,
                'pageTitle' => $page?->title,
            ]);
        }

        //TODO:: return to 404 if theme option is set

        //TODO:: return to 404 if global system setting is set
        
        //return to home if nothing matched
        return redirect()->route('frontend.pages.home');
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Redirect to static pages
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.0.0
     * @param string|null $pageNmae 
     *      The blade file name without .blade.php. 
     *      For example:
     *          Name of "/resource/view/frontend/theme/default/pages/blog.blade.php" is "blog"
     *          Name of "/resource/view/frontend/theme/default/pages/home.blade.php" is "home"
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     * ----------------------------------------------------------------------------------------------------
     */
    protected function getFrontendPageView($theme, $pageNmae, $params = [], $fallbackPage = "home", $fallbackRoute = null)
    {
        $website = wncms()->website()->get();
        if (!$website) return redirect()->route('websites.create');
        
        $theme ??= 'default';
        
        //check if page exists
        if (view()->exists("frontend.theme.{$theme}.pages.{$pageNmae}")) {
            return view("wncms::frontend.theme.{$theme}.pages.{$pageNmae}", $params);
        }

        if($fallbackRoute && wn()->getRoute($fallbackRoute)){
            return redirect()->route($fallbackRoute);
        }

        //redirect to fallback page if not exists
        if ($fallbackPage) {
            return $this->getFrontendPageView($theme, $fallbackPage, $params, null);
        }

        //throw 404 if both pages are not exists
        abort(404);
    }
}
