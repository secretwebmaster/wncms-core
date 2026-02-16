<?php

//% Depracated soon
// Legacy helper archive. Active helper functions were moved to `helpers/wncms.php`.
// Keep blocks below commented for backward reference only.
//
// if (!function_exists('isAdmin')) {
//     /**
//      * ----------------------------------------------------------------------------------------------------
//      * Check if user is admin
//      * ----------------------------------------------------------------------------------------------------
//      * @link https://wncms.cc
//      * @since 3.0.0
//      * @version 3.0.0
//      * @param string|null $user_id User model id. If $user_id is not passed. Check auth()->user() instead
//      * @return boolean true if user is admin
//      * TODO: Store $adminRoles in database and let users to edit
//      * ----------------------------------------------------------------------------------------------------
//      */
//     function isAdmin($user_id = null)
//     {
//         $adminRoles = ['superadmin', 'admin'];
//         if (!empty($user_id)) {
//             $user = wn('user')->get($user_id);
//             if ($user) {
//                 return $user->hasRole($adminRoles);
//             } else {
//                 return false;
//             }
//         }
//
//         return auth()->user()?->hasRole($adminRoles) ? true : false;
//     }
// }
//
// if (!function_exists('wncms_model_word')) {
//     function wncms_model_word(string $model_name, ?string $action = null): string
//     {
//         return wncms()->getModelWord($model_name, $action);
//     }
// }

// ------------------------------------------------------------------------
// Moved from helpers/model.php (legacy commented helpers)
// ------------------------------------------------------------------------
// <?php
// 
// 
// //Models

// ------------------------------------------------------------------------
// Moved from helpers/page.php (legacy commented helpers)
// ------------------------------------------------------------------------
// <?php
// /**
//  * Page helpers
//  */
// 
// use Wncms\Models\Page;
// 
// // if (!function_exists('wncms_get_page')) {
// //     Deprecated helper disabled in cleanup phase.
// //     External usage search evidence (core repo, excluding this file):
// //     rg -n "\\bwncms_get_page\\s*\\(" . --glob '!helpers/page.php'
// //     Result: 1 match.
// //     - resources/views/frontend/common/parts/menu_items.blade.php:3
// //     Action: updated to manager syntax.
// //
// //     External usage search evidence (workspace packages):
// //     cd /www/wwwroot/package.wncms.cc
// //     rg -n "\\bwncms_get_page\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
// //     Result: 0 matches in other packages.
// //
// //     Migration syntax:
// //     - Use manager API: wncms()->page()->get(['id' => $id]) or wncms()->page()->get(['slug' => $slug])
// //
// //     if (!function_exists('wncms_get_page')) {
// //         function wncms_get_page($id = null, $slug = null)
// //         {
// //             $cacheKey = "wncms_get_page_{$id}_{$slug}";
// //             $cacheTags = ['pages'];
// //             $page = wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time'), function () use ($id, $slug) {
// //                 return Page::query()
// //                     ->where(function ($q) use ($id, $slug) {
// //                         $q->where('id', $id)->orWhere('slug', $slug);
// //                     })
// //                     ->with('media', function ($q) {
// //                         $q->where('collection_name', 'page_thumbnail');
// //                     })
// //                     ->first();
// //             });
// //             return $page;
// //         }
// //     }
// // }
// 
// // if (!function_exists('wncms_get_pages')) {
// //     Deprecated helper disabled in cleanup phase.
// //     External usage search evidence (core repo, excluding this file):
// //     rg -n "\\bwncms_get_pages\\s*\\(" . --glob '!helpers/page.php'
// //     Result: 0 matches.
// //
// //     External usage search evidence (workspace packages):
// //     cd /www/wwwroot/package.wncms.cc
// //     rg -n "\\bwncms_get_pages\\s*\\(" . --glob '!packages/secretwebmaster/wncms-core/helpers/page.php'
// //     Result: 0 matches.
// //
// //     Migration syntax:
// //     - Use manager API: wncms()->page()->getList(['website_id' => $website?->id, ...])
// //
// //     if (!function_exists('wncms_get_pages')) {
// //         function wncms_get_pages(Website $website = null)
// //         {
// //             $cacheKey = "wncms_get_pages" . $website?->id;
// //             $cacheTags = ['pages'];
// //             // wncms_clear_cache($cacheKey, $cacheTags);
// //             $pages = wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time'), function () use ($website) {
// //                 if ($website) {
// //                     return $website->pages()->with('media')->get();
// //                 } else {
// //                     return Page::query()->with('media')->get();
// //                 }
// //             });
// //             return $pages;
// //         }
// //     }
// // }

// ------------------------------------------------------------------------
// Moved from helpers/post.php (legacy commented helpers)
// ------------------------------------------------------------------------
// <?php
// 
// //Post
// //% 將參數改為array
// 
// use Wncms\Models\Post;
// use Wncms\Models\Tag;
// use Wncms\Models\Website;
// 
// // if (!function_exists('wncms_get_post')) {
//     // Deprecated helper disabled in cleanup phase.
//     // External usage search evidence (core repo, excluding this file):
//     // rg -n "wncms_get_post\\s*\\(" . --glob '!helpers/post.php'
//     // Result: 0 matches.
//     //
//     // External usage search evidence (workspace packages):
//     // cd /www/wwwroot/package.wncms.cc
//     // rg -n "wncms_get_post\\s*\\(" . --glob '!packages/secretwebmaster/wncms-core/helpers/post.php'
//     // Result: 0 matches.
//     //
//     // Migration syntax:
//     // - Use manager API: wncms()->post()->get(['slug' => $slug])
//     //
//     // if (!function_exists('wncms_get_post')) {
//     //     function wncms_get_post($slug)
//     //     {
//     //         $cacheKey = "wncms_get_post_" . $slug;
//     //         wncms()->cache()->tags(['posts'])->forget($cacheKey);
//     //         $post = wncms()->cache()->tags(['post'])->remember($cacheKey, gss('data_cache_time'), function () use ($slug) {
//     //             return Post::query()
//     //                 ->where('slug', $slug)
//     //                 ->with('media', function ($q) {
//     //                     $q->where('collection_name', 'post_thumbnail');
//     //                 })
//     //                 ->with('tags')
//     //                 ->with(['comments'])
//     //                 ->first();
//     //         });
//     //         return $post;
//     //     }
//     // }
// // }
// 
// // if (!function_exists('wncms_get_posts')) {
//     // Deprecated helper disabled in cleanup phase.
//     // External usage search evidence (core repo, excluding this file):
//     // rg -n "\\bwncms_get_posts\\s*\\(" . --glob '!helpers/post.php'
//     // Result: 0 matches.
//     //
//     // External usage search evidence (workspace packages):
//     // cd /www/wwwroot/package.wncms.cc
//     // rg -n "\\bwncms_get_posts\\s*\\(" . --glob '!packages/secretwebmaster/wncms-core/helpers/post.php'
//     // Result: 0 matches.
//     //
//     // Migration syntax:
//     // - Use manager API: wncms()->post()->getList([...])
//     //   Example options: website_id, tags, tag_type, count, page_size, sort, direction, status.
//     //
//     // if (!function_exists('wncms_get_posts')) {
//     //     function wncms_get_posts(Website $website = null, array|string|null $category = [], $taxonomy_type = 'post_category', $count = 0, $page_size = 0, $sort = 'id', $direction = 'desc', $status = 'published')
//     //     {
//     //         if (empty($category)) $category = [];
//     //         if (is_string($category)) $category = explode(',', $category);
//     //         //% To DO
//     //
//     //         $cacheKey = "wncms_get_posts_" . implode(",", $category) . "_taxonomy_type_{$taxonomy_type}_{$count}_{$page_size}_{$sort}_{$direction}_{$status}";
//     //         wncms()->cache()->tags(['posts'])->forget($cacheKey);
//     //         $posts = wncms()->cache()->tags(['posts'])->remember($cacheKey, gss('data_cache_time'), function () use ($category, $taxonomy_type, $count, $page_size, $sort, $direction, $status) {
//     //             return Post::query()
//     //                 ->with('media', function ($q) {
//     //                     $q->where('collection_name', 'post_thumbnail');
//     //                 })
//     //                 ->with('tags')
//     //                 ->with(['comments'])
//     //                 ->withCount('comments')
//     //                 ->when($category, function ($q) use ($category, $taxonomy_type) {
//     //                     $q->withAnyTags($category, $taxonomy_type);
//     //                 })
//     //                 ->when($count, fn ($q) => $q->limit($count))
//     //                 ->orderBy($sort, in_array($direction, ['asc', 'desc']) ? $direction : 'desc')
//     //                 ->orderBy('id', 'desc')
//     //                 ->where('status', $status)
//     //                 ->get();
//     //         });
//     //
//     //         if (!$page_size) {
//     //             // $posts = $posts->get();
//     //         } else {
//     //             $posts = $posts->paginate($page_size);
//     //         }
//     //
//     //         return $posts;
//     //     }
//     // }
// // }
// 
// // if (!function_exists('wncms_get_posts_by_keyword')) {
//     // Deprecated helper disabled in cleanup phase.
//     // External usage search evidence (core repo, excluding this file):
//     // rg -n "\\bwncms_get_posts_by_keyword\\s*\\(" . --glob '!helpers/post.php'
//     // Result: 0 matches.
//     //
//     // External usage search evidence (workspace packages):
//     // cd /www/wwwroot/package.wncms.cc
//     // rg -n "\\bwncms_get_posts_by_keyword\\s*\\(" . --glob '!packages/secretwebmaster/wncms-core/helpers/post.php'
//     // Result: 0 matches.
//     //
//     // Migration syntax:
//     // - Use manager API: wncms()->post()->getList(['keywords' => [...], ...])
//     //
//     // if (!function_exists('wncms_get_posts_by_keyword')) {
//     //     function wncms_get_posts_by_keyword(Website $website = null, array|string|null $keywords = [], $count = 0, $page_size = 0, $sort = 'id', $direction = 'desc', $status = 'published')
//     //     {
//     //         if (empty($keywords)) $keywords = [];
//     //         if (is_string($keywords)) $keywords = explode(',', $keywords);
//     //
//     //         $cacheKey = "wncms_get_posts_by_keyword_" . implode(",", $keywords) . "_{$count}_{$page_size}_{$sort}_{$direction}_{$status}";
//     //         wncms()->cache()->tags(['posts'])->forget($cacheKey);
//     //         $posts = wncms()->cache()->tags(['posts'])->remember($cacheKey, gss('data_cache_time'), function () use ($website, $keywords, $count, $sort, $direction, $status) {
//     //             return $website->posts()
//     //                 ->with('media')
//     //                 ->where(function ($q) use ($keywords) {
//     //                     foreach ($keywords as $keyword) {
//     //                         // $q->orWhere('title','like',"%$keyword%");
//     //                         $q->orWhereRaw("JSON_EXTRACT(title, '$.*') LIKE '%$keyword%'");
//     //                     }
//     //                 })
//     //                 ->get();
//     //         });
//     //
//     //         if (!$page_size) {
//     //             // $posts = $posts->get();
//     //         } else {
//     //             $posts = $posts->paginate($page_size);
//     //         }
//     //
//     //         return $posts;
//     //     }
//     // }
// // }
// 
// // if (!function_exists('wncms_get_posts_by_tag')) {
//     // Deprecated helper disabled in cleanup phase.
//     // External usage search evidence (core repo, excluding this file):
//     // rg -n "\\bwncms_get_posts_by_tag\\s*\\(" . --glob '!helpers/post.php'
//     // Result: 0 matches.
//     //
//     // External usage search evidence (workspace packages):
//     // cd /www/wwwroot/package.wncms.cc
//     // rg -n "\\bwncms_get_posts_by_tag\\s*\\(" . --glob '!packages/secretwebmaster/wncms-core/helpers/post.php'
//     // Result: 0 matches.
//     //
//     // Migration syntax:
//     // - Use manager API:
//     //   wncms()->post()->getList(['tags' => [$tag->name], 'tag_type' => $tag->type, ...])
//     //
//     // if (!function_exists('wncms_get_posts_by_tag')) {
//     //     function wncms_get_posts_by_tag(Tag $tag, $count = 0, $page_size = 0, $sort = 'id', $direction = 'desc', $status = 'published')
//     //     {
//     //         if (empty($tag)) return;
//     //         $cacheKey = "wncms_get_posts_by_tag_{$tag->id}_{$count}_{$page_size}_{$sort}_{$direction}_{$status}";
//     //         wncms()->cache()->tags(['posts'])->forget($cacheKey);
//     //         $posts = wncms()->cache()->tags(['posts'])->remember($cacheKey, gss('data_cache_time'), function () use ($tag, $count, $page_size, $sort, $direction, $status) {
//     //             $website = wn('website')->get();
//     //             if (!$website) return;
//     //
//     //             return $website->posts()
//     //                 ->whereRelation('tags', 'id', $tag->id)
//     //                 ->with('media', function ($q) {
//     //                     $q->where('collection_name', 'post_thumbnail');
//     //                 })
//     //                 ->when($count, fn ($q) => $q->limit($count))
//     //                 ->orderBy($sort, in_array($direction, ['asc', 'desc']) ? $direction : 'desc')
//     //                 ->orderBy('id', 'desc')
//     //                 ->where('status', $status)
//     //                 ->get();
//     //         });
//     //
//     //         if (!$page_size) {
//     //             // $posts = $posts->get();
//     //         } else {
//     //             $posts = $posts->paginate($page_size);
//     //         }
//     //
//     //         return $posts;
//     //     }
//     // }
// // }
// 
// // if (!function_exists('wncms_get_related_posts')) {
//     // Deprecated helper disabled in cleanup phase.
//     // External usage search evidence (core repo, excluding this file):
//     // rg -n "\\bwncms_get_related_posts\\s*\\(" . --glob '!helpers/post.php'
//     // Result: 0 matches.
//     //
//     // External usage search evidence (workspace packages):
//     // cd /www/wwwroot/package.wncms.cc
//     // rg -n "\\bwncms_get_related_posts\\s*\\(" . --glob '!packages/secretwebmaster/wncms-core/helpers/post.php'
//     // Result: 0 matches.
//     //
//     // Migration syntax:
//     // - Use manager/model API:
//     //   wncms()->post()->getRelated($post, [...])
//     //   $post->getRelated([...])
//     //
//     // if (!function_exists('wncms_get_related_posts')) {
//     //     function wncms_get_related_posts(Post $post, array|string|null $category = [], $taxonomy_type = 'post_category', $count = 0, $page_size = 0, $sort = 'id', $direction = 'desc', $status = 'published')
//     //     {
//     //         if (!$post) return collect([]);
//     //         if (empty($category)) $category = [];
//     //         if (is_string($category)) $category = explode(',', $category);
//     //         //% To DO
//     //
//     //         $cacheKey = "wncms_get_related_posts_{$post->id}_" . implode(",", $category) . "_taxonomy_type_{$taxonomy_type}_{$count}_{$page_size}_{$sort}_{$direction}_{$status}";
//     //         $cacheTags = ['posts'];
//     //         wncms()->cache()->tags($cacheTags)->forget($cacheKey);
//     //         $posts = wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time'), function () use ($post, $category, $taxonomy_type, $count, $page_size, $sort, $direction, $status) {
//     //             return Post::query()
//     //                 ->with('media', function ($q) {
//     //                     $q->where('collection_name', 'post_thumbnail');
//     //                 })
//     //                 ->when($category, function ($q) use ($category, $taxonomy_type) {
//     //                     $q->withAnyTags($category, $taxonomy_type);
//     //                 })
//     //                 ->when($count, fn ($q) => $q->limit($count))
//     //                 ->orderBy($sort, in_array($direction, ['asc', 'desc']) ? $direction : 'desc')
//     //                 ->orderBy('id', 'desc')
//     //                 ->where('status', $status)
//     //                 ->where('id', '<>' , $post->id)
//     //                 ->get();
//     //         });
//     //
//     //         if (!$page_size) {
//     //             // $posts = $posts->get();
//     //         } else {
//     //             $posts = $posts->paginate($page_size);
//     //         }
//     //
//     //         return $posts;
//     //     }
//     // }
// // }

// ------------------------------------------------------------------------
// Moved from helpers/record.php (legacy commented helpers)
// ------------------------------------------------------------------------
// <?php
// 
// use Wncms\Models\Record;
// 
// // if (!function_exists('wncms_add_record')) {
//     // Deprecated helper disabled in cleanup phase.
//     // External usage search evidence (core repo, excluding this file):
//     // rg -n "wncms_add_record\\s*\\(" . --glob '!helpers/record.php'
//     // Result: 0 matches.
//     //
//     // External usage search evidence (workspace packages):
//     // cd /www/wwwroot/package.wncms.cc
//     // rg -n "wncms_add_record\\s*\\(" . --glob '!packages/secretwebmaster/wncms-core/helpers/record.php'
//     // Result: 0 matches.
//     //
//     // if (!function_exists('wncms_add_record')) {
//     //     function wncms_add_record($type, $sub_type, $status,  $message, $detail = null)
//     //     {
//     //         return Record::create([
//     //             'type' => $type,
//     //             'sub_type' => $sub_type,
//     //             'status' => $status,
//     //             'message' => $message,
//     //             'detail' => $detail,
//     //         ]);
//     //     }
//     // }
// // }
// 
// // if (!function_exists('wncms_add_credit_record')) {
//     // Deprecated helper disabled in cleanup phase.
//     // External usage search evidence (core repo, excluding this file):
//     // rg -n "wncms_add_credit_record\\s*\\(" . --glob '!helpers/record.php'
//     // Result: 0 matches.
//     //
//     // External usage search evidence (workspace packages):
//     // cd /www/wwwroot/package.wncms.cc
//     // rg -n "wncms_add_credit_record\\s*\\(" . --glob '!packages/secretwebmaster/wncms-core/helpers/record.php'
//     // Result: 0 matches.
//     //
//     // if (!function_exists('wncms_add_credit_record')) {
//     //     function wncms_add_credit_record($type, $status, $amount, $remark = null)
//     //     {
//     //         return auth()->user()->credit_records()->create([
//     //             'type' => $type,
//     //             'status' => $status,
//     //             'amount' => $amount,
//     //             'remark' => $remark,
//     //         ]);
//     //     }
//     // }
// // }

// ------------------------------------------------------------------------
// Moved from helpers/search_keyword.php (legacy commented helpers)
// ------------------------------------------------------------------------
// <?php
// 
// //% Deprecated soon
// //Search Keyword
// 
// use Wncms\Models\Website;
// 
// // Deprecated helper disabled in cleanup phase.
// // External usage search evidence (repo-wide, excluding this file):
// // rg -n "wncms_get_search_keywords\\s*\\(" . --glob '!helpers/search_keyword.php'
// // Result: 0 matches.
// //
// // if (!function_exists('wncms_get_search_keywords')) {
// //     function wncms_get_search_keywords($count, $sort = 'random', $direction = 'desc', ?Website $website = null)
// //     {
// //         $website = $website ?? wn('website')->get();
// //         if (!$website) return;
// //         $cacheKey = "wncms_get_search_keywords_{$count}_{$sort}_{$direction}_{$website->id}";
// //         $cacheTags = ['search_keywords'];
// //         $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
// //
// //         return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($count, $sort, $direction, $website) {
// //             return $website->search_keywords()
// //                 ->limit($count)
// //                 ->when($sort == 'random', function ($q) {
// //                     $q->inRandomOrder();
// //                 })
// //                 ->when(in_array($sort, ['count', 'created_at', 'updated_at']), function ($q) use ($sort, $direction) {
// //                     $q->orderBy($sort, $direction == 'desc' ? 'desc' : 'asc');
// //                 })
// //                 ->get();
// //         });
// //     }
// // }

// ------------------------------------------------------------------------
// Moved from helpers/tags.php (legacy commented helpers)
// ------------------------------------------------------------------------
// <?php
// 
// //% Depracated soon. This helper will be moved to TagHelper and called as wncms()->tag()->{method name}()
// 
// use Wncms\Models\Tag;
// 
// // if (!function_exists('wncms_get_hot_taxonomies')) {
// //     Deprecated helper disabled in cleanup phase.
// //     External usage search evidence (core repo, excluding this file):
// //     rg -n "\\bwncms_get_hot_taxonomies\\s*\\(" . --glob '!helpers/tags.php'
// //     Result: 0 matches.
// //
// //     External usage search evidence (other packages only):
// //     cd /www/wwwroot/package.wncms.cc
// //     rg -n "\\bwncms_get_hot_taxonomies\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
// //     Result: 0 matches.
// //
// //     Migration syntax:
// //     - Use manager API: wncms()->tag()->getList(['tag_type' => $type, 'sort' => 'posts_count', 'direction' => 'desc', 'count' => $count])
// //
// //     if (!function_exists('wncms_get_hot_taxonomies')) {
// //         function wncms_get_hot_taxonomies($type = 'post_category', $count = 50)
// //         {
// //             $cacheKey = "wncms_get_hot_taxonomies_{$type}_{$count}_" . wncms()->getDomain();
// //             wncms()->cache()->tags(['tags'])->forget($cacheKey);
// //             $taxonomies = cache()->remember($cacheKey, gss('data_cache_time'), function () use ($type, $count) {
// //                 return Tag::query()
// //                     ->withCount('posts')
// //                     ->where('type', $type)
// //                     ->orderBy('posts_count', 'desc')
// //                     ->limit($count)
// //                     ->get();
// //             });
// //             return $taxonomies;
// //         }
// //     }
// // }
// 
// // if (!function_exists('wncms_get_all_tag_types')) {
// //     Deprecated helper disabled in cleanup phase.
// //     External usage search evidence (core repo, excluding this file):
// //     rg -n "\\bwncms_get_all_tag_types\\s*\\(" . --glob '!helpers/tags.php'
// //     Result: 0 matches.
// //
// //     External usage search evidence (other packages only):
// //     cd /www/wwwroot/package.wncms.cc
// //     rg -n "\\bwncms_get_all_tag_types\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
// //     Result: 0 matches.
// //
// //     Migration syntax:
// //     - Use manager API: wncms()->tag()->getAllTagTypes()
// //
// //     if (!function_exists('wncms_get_all_tag_types')) {
// //         function wncms_get_all_tag_types()
// //         {
// //             $cacheKey = "wncms_get_all_tag_types" . wncms()->getDomain();
// //             $tags = ['tags'];
// //             wncms()->cache()->tags($tags)->forget($cacheKey);
// //             $taxonomies = wncms()->cache()->tags($tags)->remember($cacheKey, gss('data_cache_time'), function () {
// //                 return array_unique(array_filter(Tag::distinct('type')->pluck('type')->toArray()));
// //             });
// //             return $taxonomies;
// //         }
// //     }
// // }
// 
// //單一標籤
// // if (!function_exists('wncms_get_tag')) {
// //     Deprecated helper disabled in cleanup phase.
// //     External usage search evidence (core repo, excluding this file):
// //     rg -n "\\bwncms_get_tag\\s*\\(" . --glob '!helpers/tags.php'
// //     Result: 0 matches.
// //
// //     External usage search evidence (other packages only):
// //     cd /www/wwwroot/package.wncms.cc
// //     rg -n "\\bwncms_get_tag\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
// //     Result: 0 matches.
// //
// //     Migration syntax:
// //     - Use manager API: wncms()->tag()->get(['name' => $name, 'tag_type' => $type])
// //
// //     if (!function_exists('wncms_get_tag')) {
// //         function wncms_get_tag($name, $type = 'post_category')
// //         {
// //             $cacheKey = "wncms_get_tag_{$name}_{$type}";
// //             $cacheTags = ['tags'];
// //             return wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($name, $type) {
// //                 return Tag::query()
// //                     ->where('type', $type)
// //                     ->where(function ($q) use ($name) {
// //                         foreach (LaravelLocalization::getSupportedLocales() as $locale_key => $locale) {
// //                             $q->orWhere('name->' . $locale_key, $name);
// //                         }
// //                         $q->orWhere('slug', $name);
// //                     })->first();
// //             });
// //         }
// //     }
// // }
// 
// /**
//  * 根據Tag類別獲取所有Tag
//  * @since 1.0.0
//  * @version 3.0.0
//  * @param string $type Tag類別，例如 post_category
//  * @param int $count 獲取數量
//  * @param boolean $parent_only 是否只獲取父分類
//  * @return Collection|null
//  * @example wncms_get_tags_by_type('post_category')
//  */
// // if (!function_exists('wncms_get_tags_by_type')) {
//     // Deprecated helper disabled in cleanup phase.
//     // External usage search evidence (core repo, excluding this file):
//     // rg -n "\\bwncms_get_tags_by_type\\s*\\(" . --glob '!helpers/tags.php'
//     // Result: 0 matches.
//     //
//     // External usage search evidence (other packages only):
//     // cd /www/wwwroot/package.wncms.cc
//     // rg -n "\\bwncms_get_tags_by_type\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
//     // Result: 0 matches.
//     //
//     // Migration syntax:
//     // - Use manager API: wncms()->tag()->getList(['tag_type' => $type, 'count' => $count, 'parent_only' => $parent_only])
//     //
//     // if (!function_exists('wncms_get_tags_by_type')) {
//     //     function wncms_get_tags_by_type(string $type = 'post_category', int $count = 0, $parent_only = false)
//     //     {
//     //         $cacheKey = "wncms_get_tags_by_type_{$type}_{$count}_{$parent_only}";
//     //         $cacheTags = ['tags'];
//     //         return wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($type, $count, $parent_only) {
//     //             $q = Tag::query();
//     //             $q->where('type', $type);
//     //
//     //             if ($count) {
//     //                 $q->linit(0);
//     //             }
//     //
//     //             if ($parent_only) {
//     //                 $q->whereNull('parent_id');
//     //             }
//     //
//     //             $tags = $q->get();
//     //             return $tags;
//     //         });
//     //     }
//     // }
// // }
// 
// //model的第一個標籤
// // if (!function_exists('wncms_get_model_first_tag')) {
// //     function wncms_get_model_first_tag($model, $type = 'post_category')
// //     {
// //         $cacheKey = "wncms_get_model_first_tag_" . class_basename($model) . "_{$model?->id}_{$type}_" . wncms()->getDomain();
// //         $cacheTags = ['tags'];
// //         // wncms()->cache()->clear($cacheKey, $cacheTags);
// //         return wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($model, $type) {
// //             if (method_exists($model, 'tags')) {
// //                 return $model->tags()->where('type', $type)->first() ?? false;
// //             }
//             
// //             return false;
// //         });
// //     }
// // }
// 
// //model的第一個子標籤
// // if (!function_exists('wncms_get_model_lowerest_level_tag')) {
// //     Deprecated helper disabled in cleanup phase.
// //     External usage search evidence (core repo, excluding this file):
// //     rg -n "\\bwncms_get_model_lowerest_level_tag\\s*\\(" . --glob '!helpers/tags.php'
// //     Result: 0 matches.
// //
// //     External usage search evidence (other packages only):
// //     cd /www/wwwroot/package.wncms.cc
// //     rg -n "\\bwncms_get_model_lowerest_level_tag\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
// //     Result: 0 matches.
// //
// //     if (!function_exists('wncms_get_model_lowerest_level_tag')) {
// //         function wncms_get_model_lowerest_level_tag($model, $type = 'post_category')
// //         {
// //             $cacheKey = "wncms_get_model_lowerest_level_tag_" . class_basename($model) . "_{$model?->id}_{$type}_" . wncms()->getDomain();
// //             $cacheTags = ['tags'];
// //             return wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($model, $type    ) {
// //                 return _get_model_lowerest_level_tag($model, $type);
// //             });
// //         }
// //
// //         function _get_model_lowerest_level_tag($model, $type, $current_tag = null)
// //         {
// //             if (!$current_tag) {
// //                 $tags = $model->tags()->where('type', $type)->get();
// //             } else {
// //                 $tags = $current_tag->children;
// //             }
// //
// //             if ($tags->isEmpty()) {
// //                 return $current_tag;
// //             }
// //
// //             $lowest_level_tag = null;
// //
// //             foreach ($tags as $tag) {
// //                 $child_lowest_tag = _get_model_lowerest_level_tag($model, $type, $tag);
// //
// //                 if (!$lowest_level_tag || $child_lowest_tag->level > $lowest_level_tag->level) {
// //                     $lowest_level_tag = $child_lowest_tag;
// //                 }
// //             }
// //
// //             return $lowest_level_tag;
// //         }
// //     }
// // }
// 
// //獲取多個標籤 id/name
// // if (!function_exists('wncms_get_tags')) {
// //     Deprecated helper disabled in cleanup phase.
// //     External usage search evidence (core repo, excluding this file):
// //     rg -n "\\bwncms_get_tags\\s*\\(" . --glob '!helpers/tags.php'
// //     Result: 0 matches.
// //
// //     External usage search evidence (other packages only):
// //     cd /www/wwwroot/package.wncms.cc
// //     rg -n "\\bwncms_get_tags\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
// //     Result: 0 matches.
// //
// //     Migration syntax:
// //     - Use manager API: wncms()->tag()->getList(['tag_type' => 'link_category', 'tag_ids' => explode(',', $category_str)])
// //
// //     if (!function_exists('wncms_get_tags')) {
// //         function wncms_get_tags(string $category_str)
// //         {
// //             $cacheKey = "wncms_get_tags_{$category_str}";
// //             $cacheTags = ['tags'];
// //             return wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($category_str) {
// //                 return Tag::where('type', 'link_category')
// //                     ->whereIn('name', explode(',', $category_str))
// //                     ->orWhereIn('id', explode(',', $category_str))
// //                     ->orderBy('sort', 'desc')
// //                     ->get() ?? [];
// //             });
// //         }
// //     }
// // }
// 
// //所有標籤
// // if (!function_exists('wncms_get_all_tags')) {
// //     Deprecated helper disabled in cleanup phase.
// //     External usage search evidence (core repo, excluding this file):
// //     rg -n "\\bwncms_get_all_tags\\s*\\(" . --glob '!helpers/tags.php'
// //     Result: 0 matches.
// //
// //     External usage search evidence (other packages only):
// //     cd /www/wwwroot/package.wncms.cc
// //     rg -n "\\bwncms_get_all_tags\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
// //     Result: 0 matches.
// //
// //     Migration syntax:
// //     - Use manager API: wncms()->tag()->getArray('link_category')
// //
// //     if (!function_exists('wncms_get_all_tags')) {
// //         function wncms_get_all_tags()
// //         {
// //             wncms()->cache()->tags(['tags'])->forget('wncms_get_all_tags');
// //             return wncms()->cache()->tags(['tags'])->remember('wncms_get_all_tags', gss('data_cache_time', 3600), function () {
// //                 return Tag::getWithType('link_category')->pluck('name')->toArray();
// //             });
// //         }
// //     }
// // }
// 
// //獲取標籤連結
// // if (!function_exists('wncms_get_tag_url')) {
// //     Deprecated helper disabled in cleanup phase.
// //     External usage search evidence (core repo, excluding this file):
// //     rg -n "\\bwncms_get_tag_url\\s*\\(" . --glob '!helpers/tags.php'
// //     Result: 0 matches.
// //
// //     External usage search evidence (other packages only):
// //     cd /www/wwwroot/package.wncms.cc
// //     rg -n "\\bwncms_get_tag_url\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
// //     Result: 0 matches.
// //
// //     Migration syntax:
// //     - Use manager API: wncms()->tag()->getUrl($tag)
// //
// //     if (!function_exists('wncms_get_tag_url')) {
// //         function wncms_get_tag_url($tag)
// //         {
// //             if (empty($tag)) return;
// //             $cacheKey = "wncms_get_tag_url_{$tag->name}_" . wncms()->getDomain();
// //             $cacheTags = ['tags'];
// //             wncms_clear_cache($cacheKey, $cacheKey);
// //             return wncms()->cache()->tags($cacheKey)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($tag) {
// //                 if ($tag->type == 'post_category') {
// //                     return route('frontend.posts.post_taxonomy', ['post_taxonomy_type' => 'category', 'taxonomy_name' => $tag->name]);
// //                 } elseif ($tag->type == 'post_tag') {
// //                     return route('frontend.posts.post_taxonomy', ['post_taxonomy_type' => 'tag', 'taxonomy_name' => $tag->name]);
// //                 } else {
// //                     return route('frontend.posts.archive', ['taxonomy_type' => $tag->type, 'taxonomy_name' => $tag->name]);
// //                 }
// //             });
// //         }
// //     }
// // }
// 
// // if (!function_exists('generate_breadcrumbs')) {
// //     Deprecated helper disabled in cleanup phase.
// //     External usage search evidence (core repo, excluding this file):
// //     rg -n "\\bgenerate_breadcrumbs\\s*\\(" . --glob '!helpers/tags.php'
// //     Result: 0 matches.
// //
// //     External usage search evidence (other packages only):
// //     cd /www/wwwroot/package.wncms.cc
// //     rg -n "\\bgenerate_breadcrumbs\\s*\\(" packages --glob '!packages/secretwebmaster/wncms-core/**'
// //     Result: 0 matches.
// //
// //     if (!function_exists('generate_breadcrumbs')) {
// //         function generate_breadcrumbs($tag)
// //         {
// //             $breadcrumbs = [];
// //
// //             while ($tag) {
// //                 $breadcrumbs[] = $tag;
// //                 $tag = $tag->parent;
// //             }
// //
// //             return array_reverse($breadcrumbs);
// //         }
// //     }
// // }

// ------------------------------------------------------------------------
// Moved from helpers/theme.php (legacy commented helpers)
// ------------------------------------------------------------------------
// <?php
// 
// //% Deprecated: Use gto() instead.
// //Theme
// // if (!function_exists('wncms_get_theme_option')) {
// //     function wncms_get_theme_option($key = null, $fallback = '', $locale = null, $fallbackWhenEmpty = true)
// //     {
// //         $locale ??= app()->getLocale();
// //         $cacheKey = "theme_options_{$locale}_" . wncms()->getDomain();
// //         $cacheTags = ['websites'];
// //         $cacheTime = gss('data_cache_time', 3600);
// //         // wncms()->cache()->clear($cacheKey, $cacheTags);
// 
// //         $theme_options = wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use($locale){
// //             $website = wncms()->website()->get();
// //             if (!$website) return;
// //             return  $website->get_options($locale);
// //         });
// 
// //         if(empty($key)){
// //             return $theme_options;
// //         }
//         
// //         if($fallbackWhenEmpty && array_key_exists($key, $theme_options) && empty($theme_options[$key])){
// //             return $fallback;
// //         }
// 
// //         return array_key_exists($key, $theme_options) ? $theme_options[$key] : $fallback;
// //     }
// // }

// ------------------------------------------------------------------------
// Moved from helpers/user.php (legacy commented helpers)
// ------------------------------------------------------------------------
// <?php
// 
// use Wncms\Models\User;
// 
// 
// // Deprecated helper disabled in cleanup phase.
// // External usage search evidence (repo-wide, excluding this file):
// // rg -n "wncms_get_users\\s*\\(" . --glob '!helpers/user.php'
// // Result: 0 matches.
// //
// // Replacement pattern:
// // - Use manager syntax: wncms()->user()->getList([...]) or wncms()->user()->query()
// //
// // if (!function_exists('wncms_get_users')) {
// //     function wncms_get_users()
// //     {
// //         if (isAdmin()) {
// //             return User::all();
// //         }
// //         // return user manager
// //     }
// // }
