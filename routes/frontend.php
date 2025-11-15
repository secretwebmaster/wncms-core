<?php

use Wncms\Http\Controllers\Frontend\ClickController;
use Wncms\Http\Controllers\Frontend\CommentController;
use Wncms\Http\Controllers\Frontend\PageController;
use Wncms\Http\Controllers\Frontend\PostController;
use Wncms\Http\Controllers\Frontend\SitemapController;
use Wncms\Http\Controllers\Frontend\LinkController;
use Wncms\Http\Controllers\Frontend\UserController;

Route::name('frontend.')->middleware('is_installed', 'has_website', 'full_page_cache')->group(function () {

    //home
    Route::get('/', [PageController::class, 'home'])->name('pages.home');

    //build-in pages
    Route::get('blog', [PageController::class, 'blog'])->name('pages.blog');
    // Route::get('builder', [PageController::class, 'show_builder'])->name('pages.builder');
    // Route::get('contact', [PageController::class, 'show_contact'])->name('pages.contact');
    // Route::get('features', [PageController::class, 'show_features'])->name('pages.features');
    // Route::get('keywords', [PageController::class, 'show_keywords'])->name('pages.keywords');
    // Route::get('pricing', [PageController::class, 'show_pricing'])->name('pages.pricing');
    // Route::get('privacy-policy', [PageController::class, 'show_privacy'])->name('pages.privacy');
    // Route::get('scenario', [PageController::class, 'show_scenario'])->name('pages.scenario');
    // Route::get('templates', [PageController::class, 'show_templates'])->name('pages.templates');
    // Route::get('terms-and-conditions', [PageController::class, 'show_terms'])->name('pages.terms');
    // Route::get('usecase', [PageController::class, 'show_usecase'])->name('pages.usecase');

    Route::prefix('clicks')->controller(ClickController::class)->group(function () {
        Route::post('record', 'record')->name('clicks.record');
    });

    //link
    Route::prefix('link')->controller(LinkController::class)->group(function () {
        Route::get('/', 'index')->name('links.index');
        Route::get('{id}', 'single')->name('links.single');
        Route::get('{tagType}/{slug}', 'archive')->name('links.archive');
    });

    //post
    Route::prefix('post')->controller(PostController::class)->group(function () {
        Route::get('rank', 'rank')->name('posts.rank');
        Route::get('rank/{period}', 'rank')->name('posts.rank.period');
        Route::get('search/{keyword}', 'search_result')->name('posts.search_result');
        Route::post('search', 'search')->name('posts.search');
        // Route::get('category/{tagName?}', 'category')->name('posts.category');
        // Route::get('tag/{tagName?}', 'tag')->name('posts.tag');
        // Route::get('{tagType}/{tagName?}', 'archive')->name('posts.archive');
        Route::get('{type}/{slug}', [PostController::class, 'tag'])
            ->where('type', wncms()->tag()->getTagTypes(wncms()->getModelClass('post'), 'short', '|'))
            ->name('posts.tag');

        Route::get('list/{name?}/{period?}', 'post_list')->where('name', 'hot|new|like|fav')->where('period', 'today|yesterday|week|month')->name('posts.list');

        Route::middleware(['auth'])->group(function () {
            Route::get('create', 'create')->name('posts.create');
            Route::post('store', 'store')->name('posts.store');
            Route::get('edit/{post}', 'edit')->name('posts.edit');
            Route::post('update/{post}', 'update')->name('posts.update');
        });



        Route::get('{slug}', 'single')->name('posts.single');
    });

    //sitemap
    Route::get('sitemap/posts', [SitemapController::class, 'posts'])->name('sitemaps.posts');
    Route::get('sitemap/pages', [SitemapController::class, 'pages'])->name('sitemaps.pages');
    Route::get('sitemap/tags/{model}/{type}', [SitemapController::class, 'tags'])->name('sitemaps.tags');

    // user pages
    Route::prefix('user')->controller(UserController::class)->group(function () {
        Route::get('/login', 'show_login')->name('users.login');
        Route::post('/login/submit', 'login')->name('users.login.submit');
        Route::post('/login/ajax', 'login_ajax')->name('users.login.ajax');
        Route::get('/register', 'show_register')->name('users.register');
        Route::post('/register/submit', 'register')->name('users.register.submit');
        Route::get('/password/forgot', 'show_password_forgot')->name('users.password.forgot');
        Route::post('/password/forgot/submit', 'handle_password_forgot')->name('users.password.forgot.submit');
        Route::get('/password/forgot/sent', 'show_password_forgot_sent')->name('users.password.forgot.sent');
        Route::get('/password/reset', 'show_password_reset')->name('users.password.reset');
        Route::post('/password/reset/submit', 'handle_password_reset')->name('users.password.reset.submit');

        Route::get('/oauth/{provider?}', 'oauth')->name('users.oauth');
        Route::get('/oauth/callback/{provider?}/{code?}', 'oauth_callback')->name('users.oauth.callback');

        Route::get('/regcheck', 'validateRegistration')->name('users.regcheck');

        Route::middleware(['auth'])->group(function () {
            Route::get('/', 'dashboard')->name('users.dashboard');
            Route::get('/logout', 'logout')->name('users.logout');
            Route::get('/profile', 'show_profile')->name('users.profile');
            Route::get('/profile/edit', 'edit_profile')->name('users.profile.edit');
            Route::post('/profile/update', 'update_profile')->name('users.profile.update');
            // Route::get('/subscription', 'show_subscription')->name('users.subscription');

            Route::get('/bindmsg', 'sendBindMessage')->name('users.bindmsg');
            Route::post('/bind', 'bindAccount')->name('users.bind');
            Route::post('/unbind', 'unbindAccount')->name('users.unbind');

            // Route::prefix('card')->controller(CardController::class)->group(function () {
            //     Route::get('/', 'show')->name('users.card');
            //     Route::post('/use', 'use')->name('users.card.use');
            // });

            Route::fallback('page')->name('users.page');
        });
    });

    Route::prefix('comment')->controller(CommentController::class)->group(function () {
        Route::post('store', 'store')->name('comments.store');
    });

    //custom frontend route
    if (file_exists(base_path('routes/custom_frontend.php'))) {
        include base_path('routes/custom_frontend.php');
    }

    //page
    Route::get('page/{slug?}', [PageController::class, 'single'])->name('pages.single');
});
