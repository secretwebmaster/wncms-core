<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Price;
use Illuminate\Http\Request;

class PriceController extends BackendController
{
    public function index(Request $request)
    {
        $q = Price::query();
        
        $Prices = $q->paginate($request->page_size ?? 100);

        return view('backend.prices.index', [
            'page_title' =>  wncms_model_word('price', 'management'),
            'Prices' => $Prices,
        ]);
    }

    public function create(Price $Price = null)
    {
        $Price ??= new Price;

        return view('backend.prices.create', [
            'page_title' =>  wncms_model_word('price', 'management'),
            'Price' => $Price,
        ]);
    }

    public function store(Request $request)
    {
        dd($request->all());

        $Price = Price::create([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->flush(['Prices']);

        return redirect()->route('prices.edit', [
            'Price' => $Price,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Price $Price)
    {
        return view('backend.prices.edit', [
            'page_title' =>  wncms_model_word('price', 'management'),
            'Price' => $Price,
        ]);
    }

    public function update(Request $request, Price $Price)
    {
        dd($request->all());

        $Price->update([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->flush(['Prices']);
        
        return redirect()->route('prices.edit', [
            'Price' => $Price,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Price $Price)
    {
        $Price->delete();
        return redirect()->route('prices.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        if(!is_array($request->model_ids)){
            $modelIds = explode(",", $request->model_ids);
        }else{
            $modelIds = $request->model_ids;
        }

        $count = Price::whereIn('id', $modelIds)->delete();

        if($request->ajax()){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('prices.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
