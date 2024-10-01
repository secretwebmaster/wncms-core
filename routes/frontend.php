<?php

use Wncms\Http\Controllers\Frontend\PageController;
use Wncms\Http\Controllers\Frontend\PostController;
use Wncms\Http\Controllers\Frontend\SitemapController;

Route::name('frontend.')->middleware('full_page_cache')->group(function () {
        
    //home
    Route::get('/', [PageController::class, 'home'])->name('pages.home');


    //installation
    Route::get('installed', [PageController::class, 'installed'])->name('pages.installed');


    //contact_form_submission
    Route::post('contact_form_submissions/submit_ajax', [ContactFormSubmissionController::class, 'submit_ajax'])->name('contact_form_submissions.submit_ajax');


    //build-in pages
    Route::get('blog', [PageController::class, 'blog'])->name('pages.blog');
    Route::get('builder', [PageController::class, 'show_builder'])->name('pages.builder');
    Route::get('contact', [PageController::class, 'show_contact'])->name('pages.contact');
    Route::get('features', [PageController::class, 'show_features'])->name('pages.features');
    Route::get('keywords', [PageController::class, 'show_keywords'])->name('pages.keywords');
    Route::get('pricing', [PageController::class, 'show_pricing'])->name('pages.pricing');
    Route::get('privacy-policy', [PageController::class, 'show_privacy'])->name('pages.privacy');
    Route::get('scenario', [PageController::class, 'show_scenario'])->name('pages.scenario');
    Route::get('templates', [PageController::class, 'show_templates'])->name('pages.templates');
    Route::get('terms-and-conditions', [PageController::class, 'show_terms'])->name('pages.terms');
    Route::get('usecase', [PageController::class, 'show_usecase'])->name('pages.usecase');
    

    //post
    Route::get('post/rank', [PostController::class, 'rank'])->name('posts.rank');
    Route::get('post/rank/{period}', [PostController::class, 'rank'])->name('posts.rank.period');
    Route::get('post/{slug}', [PostController::class, 'single'])->name('posts.single');
    Route::get('post/search/{keyword}', [PostController::class, 'search_result'])->name('posts.search_result');
    Route::post('post/search', [PostController::class, 'search'])->name('posts.search');
    Route::get('post/category/{tagName?}', [PostController::class, 'category'])->name('posts.category');
    Route::get('post/tag/{tagName?}', [PostController::class, 'tag'])->name('posts.tag');
    Route::get('post/list/{name?}/{period?}', [PostController::class, 'post_list'])->where('name', 'hot|new|like|fav')->where('period', 'today|yesterday|week|month')->name('posts.list');
    Route::get('post/{tagType}/{tagName?}', [PostController::class, 'archive'])->name('posts.archive');

    
    //faq
    Route::get('faq/{slug}', [FaqController::class, 'single'])->name('faqs.single');
    Route::get('faq/search/{keyword}', [FaqController::class, 'search_result'])->name('faqs.search_result');
    Route::post('faq/search', [FaqController::class, 'search'])->name('faqs.search');
    Route::get('faq/tag/{tagName?}', [FaqController::class, 'tag'])->name('faqs.tag');
    Route::get('faq/{tagType}/{tagName?}', [FaqController::class, 'archive'])->name('faqs.archive');


    //sitemap
    Route::get('sitemap/posts', [SitemapController::class, 'posts'])->name('sitemaps.posts');
    Route::get('sitemap/pages', [SitemapController::class, 'pages'])->name('sitemaps.pages');
    Route::get('sitemap/tags/{model}/{type}', [SitemapController::class, 'tags'])->name('sitemaps.tags');


    //custom frontend route
    if (file_exists(base_path('routes/custom_frontend.php'))) {
        include base_path('routes/custom_frontend.php');
    }
    
    
    //page
    Route::get('page/{slug?}', [PageController::class, 'single'])->name('pages.single');
});