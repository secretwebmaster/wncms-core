<?php

namespace Wncms\Http\Controllers\Frontend;

use Illuminate\Support\Facades\Event;
use Wncms\Models\Page;

class PageController extends FrontendController
{
    /**
     * Home page
     */
    public function home()
    {
        //get theme option to get custom home page

        // get website setting
        $homePageId = $this->website->homepage ?? null;
        if ($homePageId) {

            // load page by id
            $page = wncms()->page()->get(['id' => $homePageId]);

            if ($page) {
                return $this->show($page->slug);
            }
        }

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
        $posts ??= wncms()->post()->getList([
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
     *  Show a single frontend page.
     *  Resolves custom slug views, template pages, plain pages, fallback views,
     *  and performs homepage redirection logic.
     * 
     *  @param string|null $slug Page slug
     *  @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(?string $slug = null)
    {
        // Redirect when slug is empty
        if (!$slug) {
            return redirect()->route('frontend.pages.home');
        }

        // Load page
        $page = wncms()->page()->get(['slug' => $slug]);

        // Redirect if slug matches homepage ID
        if ($this->website->homepage && $page && $page->id == $this->website->homepage && request()->routeIs('frontend.pages.show')) {
            return redirect()->route('frontend.pages.home');
        }

        // if status is drafted , only allow $request->preview=true and route is not home
        if ($page && $page->status === 'drafted' && !request()->preview && !request()->routeIs('frontend.pages.home')) {
            return redirect()->route('frontend.pages.home');
        }

        // Fire event
        Event::dispatch('wncms.pages.show', $page);

        // Page exists
        if ($page) {

            // Template-based page
            if ($page->type === 'template') {

                $templateId   = $page->blade_name;
                $templateView = "{$this->theme}::pages.templates.{$templateId}";

                // Template exists
                if (view()->exists($templateView)) {

                    // Load template config
                    $templateConfig  = config("theme.{$this->theme}.templates.{$templateId}", []);
                    $templateOptions = $templateConfig['sections'] ?? [];

                    return view($templateView, [
                        'pageTitle' => $page->title,
                        'page'    => $page,
                        'templateOptions' => $templateOptions,
                    ]);
                }
            }

            // Plain page
            if ($page->type === 'plain') {

                $plainView = "{$this->theme}::pages.show";

                // Render plain page
                if (view()->exists($plainView)) {
                    return view($plainView, ['pageTitle' => $page->title, 'page' => $page]);
                }
            }

            // Theme show page
            $singlePageView = "{$this->theme}::pages.show";
            if (view()->exists($singlePageView)) {
                return view($singlePageView, ['pageTitle' => $page->title, 'page' => $page]);
            }
        }

        // Fallback: pages/{slug}.blade.php exists but DB page missing
        $customPageView = "{$this->theme}::pages.{$slug}";
        if (view()->exists($customPageView)) {
            return view($customPageView);
        }

        // fallback to home page
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
        if ($fallbackRoute && wncms()->getRoute($fallbackRoute)) {
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
