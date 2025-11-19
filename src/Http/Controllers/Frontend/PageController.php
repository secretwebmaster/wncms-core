<?php

namespace Wncms\Http\Controllers\Frontend;

use Illuminate\Support\Facades\Event;
use Wncms\Facades\Wncms;
use Wncms\Models\Page;

class PageController extends FrontendController
{
    /**
     * Home page
     */
    public function home()
    {
        //get theme option to get custom home page

        //send to home blade if no custom theme option is found
        return $this->getFrontendPageView(
            pageName: gto('home_page', 'home'),
            params: [
                'page_title' => $this->website->site_name ?? __('wncms::word.homepage'),
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
        $posts ??= Wncms::post()->getList([
            'count' => 100,
            'page_size' => gto('default_page_size', 10),
        ]);

        return $this->getFrontendPageView(
            pageName: 'blog', 
            params: [
                'pageTitle' => gto('blog_title', __('wncms::word.latest_posts')),
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
     * @param string|null $slug Page slug
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     * ----------------------------------------------------------------------------------------------------
     */
    public function single($slug = null)
    {
        if (!$slug) return redirect()->route('frontend.pages.home');

        //get page model
        $page = Wncms::page()->get(['slug' => $slug]);

        //get template
        if($page){
            // TODO: render page builder

            // load theme template with specific name as same as the page slug
            if($page->type == 'template'){
                $pageTemplateView = "frontend.themes.{$this->theme}.pages." . $page->blade_name;
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
                $singlePageView = "frontend.themes.{$this->theme}.pages.single";
                if (view()->exists($singlePageView)) {
                    return view($singlePageView, [
                        'page' => $page,
                        'pageTitle' => $page->title,
                    ]);
                }
            }
        }

        Event::dispatch('wncms.pages.single', $page);

        // page model does not exist. Load default static page
        if (view()->exists("wncms::frontend.themes.{$this->theme}.pages." . $slug)) {
            return view("wncms::frontend.themes.{$this->theme}.pages." . $slug, [
                'page' => $page ?? new Page,
                'pageTitle' => $page?->title,
            ]);
        }

        // load custom static page
        if (view()->exists("frontend.themes.{$this->theme}.pages." . $slug)) {
            return view("frontend.themes.{$this->theme}.pages." . $slug, [
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
     * @param string|null $pageName 
     *      The blade file name without .blade.php. 
     *      For example:
     *          Name of "/resource/view/frontend/theme/default/pages/blog.blade.php" is "blog"
     *          Name of "/resource/view/frontend/theme/default/pages/home.blade.php" is "home"
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     * ----------------------------------------------------------------------------------------------------
     */
    protected function getFrontendPageView($pageName, $params = [], $fallbackPage = "home", $fallbackRoute = null)
    {
        // check theme path);
        if (view()->exists("{$this->theme}::pages.{$pageName}")) {
            return view("{$this->theme}::pages.{$pageName}", $params);
        }


        //check if page exists
        if (view()->exists("frontend.themes.{$this->theme}.pages.{$pageName}")) {
            return view("frontend.themes.{$this->theme}.pages.{$pageName}", $params);
        }

        // check if wncms view exists
        if (view()->exists("wncms::frontend.themes.{$this->theme}.pages.{$pageName}")) {
            return view("wncms::frontend.themes.{$this->theme}.pages.{$pageName}", $params);
        }

        // fallback to route if set
        if($fallbackRoute && Wncms::getRoute($fallbackRoute)){
            return redirect()->route($fallbackRoute);
        }

        // redirect to fallback page if not exists
        if ($fallbackPage) {
            return $this->getFrontendPageView($fallbackPage, $params, null);
        }

        // throw 404 if both pages are not exists
        abort(404);
    }

    /**
     * Fallback method for handling unmatched routes
     */
    public function fallback()
    {
        $segments = request()->segments();

        $view = 'frontend.themes.' . $this->theme . '.' . implode('.', $segments);
        if (view()->exists($view)) {
            return view($view);
        }

        $notFoundView = 'frontend.themes.' . $this->theme . '.pages.404';
        if (view()->exists($notFoundView)) {
            return view($notFoundView);
        }

        $defaultNotFoundView = 'wncms::errors.404';
        if (view()->exists($defaultNotFoundView)) {
            return view($defaultNotFoundView);
        }

        $customNotFoundView = 'errors.404';
        if (view()->exists($customNotFoundView)) {
            return view($customNotFoundView);
        }

        abort(404);
    }
}
