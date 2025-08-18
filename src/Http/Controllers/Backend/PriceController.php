<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Price;
use Illuminate\Http\Request;

class PriceController extends BackendController
{
    public function index(Request $request)
    {
        $q = Price::query();
        
        $prices = $q->paginate($request->page_size ?? 100);

        return view('backend.prices.index', [
            'page_title' =>  wncms_model_word('price', 'management'),
            'Prices' => $prices,
        ]);
    }

    public function create($id = null)
    {
        $price ??= new Price;

        return view('backend.prices.create', [
            'page_title' =>  wncms_model_word('price', 'management'),
            'price' => $price,
        ]);
    }

    public function store(Request $request)
    {
        dd($request->all());

        $price = Price::create([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->flush(['Prices']);

        return redirect()->route('prices.edit', [
            'id' => $price,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        return view('backend.prices.edit', [
            'page_title' =>  wncms_model_word('price', 'management'),
            'price' => $price,
        ]);
    }

    public function update(Request $request, $id)
    {
        dd($request->all());

        $price->update([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->flush(['Prices']);
        
        return redirect()->route('prices.edit', [
            'id' => $price,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy($id)
    {
        $price = Price::find($id);
        if (!$price) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $price->delete();
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
