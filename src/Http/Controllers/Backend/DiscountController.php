<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $q = Discount::query();
        
        $discounts = $q->paginate($request->page_size ?? 100);

        return view('wncms::backend.discounts.index', [
            'page_title' =>  wncms_model_word('discount', 'management'),
            'discounts' => $discounts,
        ]);
    }

    public function create(Discount $discount = null)
    {
        $discount ??= new Discount;

        return view('wncms::backend.discounts.create', [
            'page_title' =>  wncms_model_word('discount', 'management'),
            'discount' => $discount,
        ]);
    }

    public function store(Request $request)
    {
        dd($request->all());

        $discount = Discount::create([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->flush(['discounts']);

        return redirect()->route('discounts.edit', [
            'discount' => $discount,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Discount $discount)
    {
        return view('wncms::backend.discounts.edit', [
            'page_title' =>  wncms_model_word('discount', 'management'),
            'discount' => $discount,
        ]);
    }

    public function update(Request $request, Discount $discount)
    {
        dd($request->all());

        $discount->update([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->flush(['discounts']);
        
        return redirect()->route('discounts.edit', [
            'discount' => $discount,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();
        return redirect()->route('discounts.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        if(!is_array($request->model_ids)){
            $modelIds = explode(",", $request->model_ids);
        }else{
            $modelIds = $request->model_ids;
        }

        $count = Discount::whereIn('id', $modelIds)->delete();

        if($request->ajax()){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('discounts.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
