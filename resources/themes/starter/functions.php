<?php

if (!defined('WNCMS_THEME_START')) {
    exit('No direct script access allowed');
}

/**
 * Demo Theme Functions
 * This file is automatically included by WNCMS ThemeServiceProvider
 *
 * Available helpers:
 * - wncms()                   Core access
 * - theme_asset()             Asset path helper
 * - theme_view($path)         Resolve theme blade file
 * - gto($key)                 Get theme option
 */


/**
 * Register frontend hooks
 * Example: modify post output before rendering
 */
// wncms()->listen('post.render', function ($post) {

//     // Example: append subtitle if exists
//     $subtitle = gto('site_subtitle');
//     if (!empty($subtitle)) {
//         $post->title = $post->title . ' - ' . $subtitle;
//     }

//     return $post;
// });


/**
 * Register theme widgets for dashboard (optional)
 */
// wncms()->registerDashboardWidget(
//     theme_view('components.post-item'),
//     [
//         'title' => 'Demo Theme Widget',
//     ]
// );


/**
 * You may also register custom blade directives (optional)
 */