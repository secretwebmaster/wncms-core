<?php

namespace Wncms\Http\Controllers\Frontend;

use Illuminate\Http\Request;

class LinkController extends FrontendController
{
    public function category($slug)
    {
        $tag = Tag::where('slug', $slug)->where('type', 'link_category')->first();
        if(!$tag){
            return redirect()->route('frontend.pages.home');
        }

        return view("frontend.theme.{$this->theme}.links.category", [
            'tag' => $tag,
        ]);
    }
}