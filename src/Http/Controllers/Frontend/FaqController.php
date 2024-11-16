<?php

namespace Wncms\Http\Controllers\Frontend;

use Illuminate\Http\Request;

class FaqController extends FrontendController
{
    /**
     * Display a single FAQ by its slug.
     */
    public function single($slug)
    {
        dd("Single method called with slug: $slug");

        // Logic to retrieve and display the FAQ by slug
        return view('faqs.single', compact('slug'));
    }

    /**
     * Search for FAQs by a keyword (GET request).
     */
    public function search_result($keyword)
    {
        dd("Search Result method called with keyword: $keyword");

        // Logic to perform a search by keyword
        return view('faqs.search_result', compact('keyword'));
    }

    /**
     * Perform a search for FAQs (POST request).
     */
    public function search(Request $request)
    {
        dd("Search method called with request data:", $request->all());

        $keyword = $request->input('keyword');
        // Logic to handle the search query
        return redirect()->route('faqs.search_result', ['keyword' => $keyword]);
    }

    /**
     * Display FAQs by a specific tag.
     */
    public function tag($tagName = null)
    {
        dd("Tag method called with tagName: $tagName");

        // Logic to display FAQs based on the tag
        return view('faqs.tag', compact('tagName'));
    }

    /**
     * Display FAQs by a tag type and tag name.
     */
    public function archive($tagType, $tagName = null)
    {
        dd("Archive method called with tagType: $tagType, tagName: $tagName");

        // Logic to display FAQs by tag type and name
        return view('faqs.archive', compact('tagType', 'tagName'));
    }
}
