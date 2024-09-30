<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Post;
use Wncms\Models\User;
use Wncms\Models\Website;
use Carbon\Carbon;
use Illuminate\Http\Request;
use LaravelLocalization;

class PostController extends Controller
{
    public function index(Request $request)
    {
        // TODO: Check auth and website config
        // $posts = Post::limit(5)->get();
        $posts = collect([]);
        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => $posts,
        ])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public function store(Request $request)
    {
        // info($request->all());

        //find user
        $user = User::whereNotNull('api_token')->where('api_token',$request->api_token)->first();

        if($user){
            auth()->login($user);
        }else{
            return response()->json([
                'status' => 'fail',
                'message' => 'invalid token',
            ]);
        }

        // info("user #{$user->id} is found");

        if(!is_array($request->website_id)){
            $requestWebsiteIds = explode(",", $request->website_id);
        }

        if(isAdmin()){
            // info('is admin');
            $user = User::find($request->user_id) ?? auth()->user();
            $websiteIds = Website::query()
                ->whereIn("id", $requestWebsiteIds)
                ->orWhereIn('domain', $requestWebsiteIds)
                ->pluck('id')
                ->toArray();
        }else{
            // info('is not admin');
            $user = auth()->user();
            $websiteIds = auth()->user()->websites()
                ->whereIn("id", $requestWebsiteIds)
                ->orWhereIn('domain', $requestWebsiteIds)
                ->pluck('id')
                ->toArray();
        }

        if(!$user){
            return response()->json([
                'status' => 'fail',
                'message' => 'user is not found',
            ]);
        }

        // TODO: allow import with no website
        // if(!$website){
        //     return response()->json([
        //         'status' => 'fail',
        //         'message' => 'website is not found',
        //     ]);
        // } 

        // TODO: check model table update for wncms 3.0.0+
        $data = [
            'user_id' => $user->id,
            'status' => $request->status ?? 'published',
            'visibility' => $request->visibility ?? 'public',
            'external_thumbnail' => $request->external_thumbnail,
            'slug'=> $request->slug ?? wncms_get_unique_slug('posts','slug',6),
            'title' => $request->title,
            'label' => $request->label,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'remark' => $request->remark,
            'order' => $request->order,
            'password' => $request->password,
            'price' => $request->price,
            'is_pinned' => $request->is_pinned ?? 0,
            'is_recommended' => $request->is_recommended ?? 0,
            'is_dmca' => $request->is_dmca ?? 0,
            'published_at' => $request->published_at ? Carbon::parse($request->published_at) : Carbon::now(),
            'expired_at' => $request->expired_at ? Carbon::parse($request->expired_at) : null,
            'source' => $request->source,
            'ref_id' => $request->ref_id,
        ];

        //check_title
        if(!empty($request->check_title)){

            $existing_post = Post::where(function($q) use($request){
                foreach(LaravelLocalization::getSupportedLanguagesKeys() as $lang_key){
                    $q->orWhere("title->{$lang_key}", $request->title);
                }
            })->first();

            //update_content_when_duplicated
            if($existing_post && empty($request->update_content_when_duplicated)){

                return response()->json([
                    'status' => 'success',
                    'message' => 'duplicated post is found',
                ]);

            }elseif($existing_post){

                $existing_post->update($data);
                return response()->json([
                    'status' => 'success',
                    'message' => 'updated existing post',
                ]);

            }

        }

        $post = $user->posts()->create($data);

        if(!empty($websiteIds)){
            $post->websites()->sync($websiteIds);
        }

        //Thumbnail
        // TODO: Read website config and check if need to convert to webp
        if(!empty($request->thumbnail)){
            $post->addMediaFromRequest('thumbnail')->toMediaCollection('post_thumbnail');
        }

        //localize content images
        info("localize_post_image = " . gss('localize_post_image'));
        if(gss('localize_post_image')){
            $post->localizeImages();
            info('localize image completed');
        }

        //post_category
        if (!empty($request->categories)) {

            $categories = explode(",", $request->categories);
            $new_cateogories = [];
            foreach ($categories as $category) {
                $new_cateogories[] = $category;
            }
            $post->syncTagsWithType($new_cateogories, 'post_category');

        }

        //post_tag
        if (!empty($request->tags)) {
            $tags = explode(",", $request->tags);
            $new_tags = [];
            foreach ($tags as $tag) {
                $new_tags[] = $tag;
            }
            $post->syncTagsWithType($new_tags, 'post_tag');
        }

        wncms()->cache()->tags(['posts'])->flush();

        return response()->json([
            'status' => "success",
            'message' => "post #{$post->id} is created",
            'data' => $post,
        ])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public function show(Request $request, $id)
    {
        $post = wncms()->post()->get($id);
        return $post;
    }
}
