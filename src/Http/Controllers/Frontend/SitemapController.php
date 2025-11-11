<?php

namespace Wncms\Http\Controllers\Frontend;

use Wncms\Models\Tag;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController
{
    public function posts()
    {
        $sitemap = Sitemap::create();
        foreach($this->website->posts()->orderBy('id', 'desc')->get() as $post){
            $sitemap
                ->add(Url::create(route('frontend.posts.single', ['slug' => $post->slug]))
                ->setLastModificationDate($post->updated_at));
        }

        return $sitemap;
    }

    public function pages()
    {
        $sitemap = Sitemap::create();
        $sitemap->add(Url::create(route('frontend.pages.home')));
        foreach($this->website->pages()->orderBy('id', 'desc')->get() as $page){

            $sitemap
                ->add(Url::create(route('frontend.pages', ['slug' => $page->slug]))
                ->setLastModificationDate($page->updated_at));
        }

        return $sitemap;
    }

    public function tags($model, $type)
    {
        $sitemap = Sitemap::create();
        
        foreach(Tag::where('type', $type)->get() as $tag){

            if($model == 'posts'){
                if($type == 'post_category'){
                    $sitemap->add(Url::create(route("frontend.posts.category", ['tagName' => $tag->name]))->setLastModificationDate($tag->updated_at));

                }elseif($type == 'post_tag'){
                    $sitemap->add(Url::create(route("frontend.posts.tag", ['tagName' => $tag->name]))->setLastModificationDate($tag->updated_at));

                }else{
                    $sitemap->add(Url::create(route("frontend.posts.archive", ['tagType' => $tag->type, 'tagName' => $tag->name]))->setLastModificationDate($tag->updated_at));
                }
            }
        }

        return $sitemap;
    }

    /**
     * TODO: add dynamic models
     */
    public function model($model)
    {
        dd($model, 'get instance of class');
        $sitemap = Sitemap::create();
        foreach($this->website->post()->orderBy('id', 'desc')->get() as $post){

            $sitemap
                ->add(Url::create(route('frontend.posts.single', ['slug' => $post->slug]))
                ->setLastModificationDate($post->updated_at));
        }

        return $sitemap;
    }

}

