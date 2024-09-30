<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\SearchKeyword;
use Illuminate\Http\Request;

class SearchKeywordController extends Controller
{
    public function index()
    {
        $search_keywords = SearchKeyword::query()->get();
        return view('wncms::backend.search_keywords.index', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.search_keyword')]),
            'search_keywords' => $search_keywords,
        ]);
    }

    public function create()
    {
        return view('wncms::backend.search_keywords.create', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.search_keyword')]),
        ]);
    }

    public function store(Request $request)
    {
        dd($request->all());
        $search_keyword = SearchKeyword::create([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->tags(['search_keyword'])->flush;

        return redirect()->route('search_keywords.edit', [
            'search_keyword' => $search_keyword,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(SearchKeyword $search_keyword)
    {
        return view('wncms::backend.search_keywords.edit', [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.search_keyword')]),
            'search_keyword' => $search_keyword,
        ]);
    }

    public function update(Request $request, SearchKeyword $search_keyword)
    {
        dd($request->all());
        $search_keyword->update([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->tags(['search_keyword'])->flush;
        
        return redirect()->route('search_keywords.edit', [
            'search_keyword' => $search_keyword,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(SearchKeyword $search_keyword)
    {
        $search_keyword->delete();
        return redirect()->route('search_keywords.index')->withMessage(__('wncms::word.successfully_deleted'));
    }
}
