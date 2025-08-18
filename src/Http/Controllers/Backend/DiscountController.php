<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class DiscountController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        $discounts = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.discounts.index', [
            'page_title' =>  wncms_model_word('discount', 'management'),
            'discounts' => $discounts,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $discount = $this->modelClass::find($id);
            if (!$discount) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $discount = new $this->modelClass;
        }

        return $this->view('backend.discounts.create', [
            'page_title' =>  wncms_model_word('discount', 'management'),
            'discount' => $discount,
        ]);
    }

    public function store(Request $request)
    {
        dd($request->all());

        $discount = $this->modelClass::where('name', $request->name)->first();
        if ($discount) {
            return back()->withMessage(__('wncms::word.model_exists', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $discount = $this->modelClass::create([
            'xxxx' => $request->xxxx,
        ]);

        $this->flush();

        return redirect()->route('discounts.edit', [
            'id' => $discount,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $discount = $this->modelClass::find($id);
        if (!$discount) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.discounts.edit', [
            'page_title' =>  wncms_model_word('discount', 'management'),
            'discount' => $discount,
        ]);
    }

    public function update(Request $request, $id)
    {
        dd($request->all());

        $discount = $this->modelClass::find($id);
        if (!$discount) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $discount->update([
            'xxxx' => $request->xxxx,
        ]);

        $this->flush();

        return redirect()->route('discounts.edit', [
            'id' => $discount,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
