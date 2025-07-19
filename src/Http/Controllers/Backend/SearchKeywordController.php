<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\SearchKeyword;
use Illuminate\Http\Request;

class SearchKeywordController extends BackendController
{
    public function index(Request $request)
    {
        $search_keywords = $this->modelClass::query()->get();
        return $this->view('backend.search_keywords.index', [
            'page_title' => wncms_model_word('search_keyword', 'management'),
            'search_keywords' => $search_keywords,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $search_keyword = $this->modelClass::find($id);
            if (!$search_keyword) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $search_keyword = new $this->modelClass;
        }

        return $this->view('backend.search_keywords.create', [
            'page_title' => wncms_model_word('search_keyword', 'management'),
            'search_keyword' => $search_keyword,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
            'locale' => 'nullable|string',
            'count' => 'nullable|integer|min:0',
        ],[
            'keyword.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.keyword')]),
            'keyword.max' => __('wncms::word.data_length_should_be_less_than', ['count' => 255]),
            'count.integer' => __('wncms::word.field_should_be_integer', ['field_name' => __('wncms::word.count')]),
        ]);

        $existing = $this->modelClass::where('keyword', $request->keyword)->first();
        if ($existing) {
            return back()->withErrors(['message' => __('wncms::word.search_keyword_already_exist', ['keyword' => $request->keyword])]);
        }

        $search_keyword = $this->modelClass::create([
            'keyword' => $request->keyword,
            'locale' => $request->locale,
            'count' => $request->count ?? 0,
        ]);

        $this->flush();

        return redirect()->route('search_keywords.edit', [
            'search_keyword' => $search_keyword,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $search_keyword = $this->modelClass::find($id);
        if (!$search_keyword) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.search_keywords.edit', [
            'page_title' => wncms_model_word('search_keyword', 'management'),
            'search_keyword' => $search_keyword,
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $search_keyword = $this->modelClass::find($id);
        if (!$search_keyword) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }
        $request->validate([
            'keyword' => 'required|string|max:255',
            'locale' => 'nullable|string|max:10',
            'count' => 'nullable|integer|min:0',
        ]);

        if ($request->keyword !== $search_keyword->keyword) {
            $existing = $this->modelClass::where('keyword', $request->keyword)->first();
            if ($existing) {
                return back()->withErrors(['message' => __('wncms::word.search_keyword_already_exist', ['keyword' => $request->keyword])]);
            }
        }

        $search_keyword->update([
            'keyword' => $request->keyword,
            'locale' => $request->locale,
            'count' => $request->count ?? 0,
        ]);

        $this->flush();

        return redirect()->route('search_keywords.edit', [
            'search_keyword' => $search_keyword,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
