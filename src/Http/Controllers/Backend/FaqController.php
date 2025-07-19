<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\User;
use Wncms\Models\Website;
use Illuminate\Http\Request;

class FaqController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        if (in_array($request->status, $this->modelClass::STATUSES)) {
            $q->where('status', $request->status);
        }

        if ($request->keyword) {
            $q->where('slug', 'like', "%$request->keyword%")
                ->orWhereRaw("JSON_EXTRACT(question, '$.*') LIKE '%$request->keyword%'")
                ->orWhereRaw("JSON_EXTRACT(answer, '$.*') LIKE '%$request->keyword%'");
        }

        if ($request->tag) {
            $q->withAnyTags([$request->tag], 'faq_tag');
        }

        if (in_array($request->order, $this->modelClass::ORDERS)) {
            $q->orderBy($request->order, in_array($request->sort, ['asc', 'desc']) ? $request->sort : 'desc');
        }

        $q->with(['tags']);

        $q->orderBy('created_at', 'desc');
        $q->orderBy('id', 'desc');

        $faqs = $q->paginate($request->page_size ?? 50);

        $faq_tag_parants = wncms()->getModelClass('tag')::where('type', 'faq_tag')->whereNull('parent_id')->with('children')->get();


        return $this->view('backend.faqs.index', [
            'page_title' =>  wncms_model_word('faq', 'management'),
            'faqs' => $faqs,
            'faq_tag_parants' => $faq_tag_parants,
            'orders' => $this->modelClass::ORDERS,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $faq = $this->modelClass::find($id);
            if (!$faq) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $faq = new $this->modelClass;
        }

        return $this->view('backend.faqs.create', [
            'page_title' =>  wncms_model_word('faq', 'management'),
            'statuses' => $this->modelClass::STATUSES,
            'faq' => $faq,
            'faq_tags' =>  wncms()->tag()->getArray(tagType: "faq_tag", columnName: "name"),
        ]);
    }

    public function store(Request $request)
    {
        $faq = $this->modelClass::create([
            'status' => $request->status,
            'slug' => $request->slug ?? wncms()->getUniqueSlug('faqs'),
            'question' => $request->question,
            'answer' => $request->answer,
            'label' => $request->label,
            'remark' => $request->remark,
            'order' => $request->order,
            'is_pinned' => $request->is_pinned == 1 ? true : false,
        ]);

        $faq->syncTagsFromTagify($request->faq_tags, 'faq_tag');

        $this->flush();

        return redirect()->route('faqs.edit', [
            'faq' => $faq,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $faq = $this->modelClass::find($id);
        if (!$faq) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.faqs.edit', [
            'page_title' =>  wncms_model_word('faq', 'management'),
            'faq' => $faq,
            'statuses' => $this->modelClass::STATUSES,
            'faq_tags' => wncms()->tag()->getArray(tagType: "faq_tag", columnName: "name"),
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $faq = $this->modelClass::find($id);
        if (!$faq) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $faq->update([
            'status' => $request->status,
            'slug' => $request->slug ?? wncms()->getUniqueSlug('faqs'),
            'question' => $request->question,
            'answer' => $request->answer,
            'label' => $request->label,
            'remark' => $request->remark,
            'order' => $request->order,
            'is_pinned' => $request->is_pinned == 1 ? true : false,
        ]);

        $faq->syncTagsFromTagify($request->faq_tags, 'faq_tag');

        $this->flush();

        return redirect()->route('faqs.edit', [
            'faq' => $faq,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
