<?php

namespace Wncms\Http\Controllers\Frontend;

use Illuminate\Http\Request;

class LinkController extends FrontendController
{
    public function index()
    {
        return $this->view("frontend.themes.{$this->theme}.links.index");
    }

    public function tag($type, $slug)
    {
        if (empty($slug)) {
            return redirect()->route('frontend.pages.home');
        }

        // get tag type meta
        $modelClass = wncms()->getModelClass('link');
        $tagMeta = collect(wncms()->tag()->getTagTypes(wncms()->getModelClass('link'), 'full'))->firstWhere('short', $type);
        $tagType = $tagMeta['key'] ?? '';
        if (empty($tagType)) {
            return redirect()->route('frontend.pages.home');;
        }

        // fetch tag by slug or name in current locale
        $tag = wncms()->tag()->get([
            'tag_type'  => $tagType,
            'wheres' => [fn($q) => $q->where('slug', $slug)->orWhere('name', $slug)],
            'salt' => md5("{$tagType}_{$slug}"),
            'cache' => true,
        ]);

        if (!$tag) {
            return redirect()->route('frontend.links.search.result', [
                'keyword' => $slug
            ]);
        }

        $links = wncms()->link()->getList([
            'count' => gto('link_limit', 0),
            'page' => request('page', 0),
            'page_size' => gto('link_page_size', 10),
            'tags' => [$tag->name],
            'tag_type' => $tagType,
            'cache' => true,
        ]);

        return $this->view(
            $this->theme . "::links.tag",
            [
                'pageTitle' => __('wncms::word.latest_tag_models',  [
                    'tagName' => $slug,
                    'modelName' => $modelClass::getModelName(),
                ]),
                'tagName' => $slug,
                'tagType' => $type,
                'links' => $links,
                'tag' => $tag,
            ],
            "frontend.themes.{$this->theme}.links.tag"
        );

        // if (str()->startsWith($type, 'link_')) {
        //     return redirect()->route('frontend.links.tag', ['type' => str_replace('link_', '', $type), 'slug' => $slug]);
        // }

        // $type = 'link_' . $type;

        // $tag = wncms()->getModel('tag')::where('type', $type)
        //     ->where(function ($query) use ($slug) {
        //         $query->where('slug', $slug)
        //             ->orWhere('name', $slug)
        //             ->orWhereHas('translations', function ($subq) use ($slug) {
        //                 $subq->where('name', $slug);
        //             });
        //     })
        //     ->first();

        // if (!$tag) {
        //     return redirect()->route('frontend.pages.home');
        // }

        // return $this->view("frontend.themes.{$this->theme}.links.tag", [
        //     'tag' => $tag,
        // ]);
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
