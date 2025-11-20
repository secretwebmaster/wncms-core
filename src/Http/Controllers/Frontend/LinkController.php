<?php

namespace Wncms\Http\Controllers\Frontend;

use Illuminate\Http\Request;

class LinkController extends FrontendController
{
    public function index()
    {
        return $this->view("frontend.themes.{$this->theme}.links.index");
    }

    public function archive($tagType, $slug)
    {
        if(str()->startsWith($tagType, 'link_')) {
            return redirect()->route('frontend.links.archive', ['tagType' => str_replace('link_', '', $tagType), 'slug' => $slug]);
        }

        $tagType = 'link_' . $tagType;

        $tag = wncms()->getModel('tag')::where('type', $tagType)
        ->where(function ($query) use ($slug) {
            $query->where('slug', $slug)
                ->orWhere('name', $slug)
                ->orWhereHas('translations', function ($subq) use ($slug) {
                    $subq->where('name', $slug);
                });
        })
        ->first();

        if (!$tag) {
            return redirect()->route('frontend.pages.home');
        }

        return $this->view("frontend.themes.{$this->theme}.links.archive", [
            'tag' => $tag,
        ]);
    }

    public function show($id)
    {
        $link = wncms()->getModelClass('link')::find($id);
        if (!$link) {
            return redirect()->route('frontend.pages.home');
        }

        return $this->view("frontend.themes.{$this->theme}.links.show", [
            'link' => $link,
        ]);
    }


}
