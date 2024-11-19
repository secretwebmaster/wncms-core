<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PlanPrice;
use Illuminate\Http\Request;

class PlanPriceController extends Controller
{
    public function index(Request $request)
    {
        $q = PlanPrice::query();
        
        $planPrices = $q->paginate($request->page_size ?? 100);

        return view('backend.plan_prices.index', [
            'page_title' =>  wncms_model_word('plan_price', 'management'),
            'planPrices' => $planPrices,
        ]);
    }

    public function create(PlanPrice $planPrice = null)
    {
        $planPrice ??= new PlanPrice;

        return view('backend.plan_prices.create', [
            'page_title' =>  wncms_model_word('plan_price', 'management'),
            'planPrice' => $planPrice,
        ]);
    }

    public function store(Request $request)
    {
        dd($request->all());

        $planPrice = PlanPrice::create([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->flush(['planPrices']);

        return redirect()->route('plan_prices.edit', [
            'planPrice' => $planPrice,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(PlanPrice $planPrice)
    {
        return view('backend.plan_prices.edit', [
            'page_title' =>  wncms_model_word('plan_price', 'management'),
            'planPrice' => $planPrice,
        ]);
    }

    public function update(Request $request, PlanPrice $planPrice)
    {
        dd($request->all());

        $planPrice->update([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->flush(['planPrices']);
        
        return redirect()->route('plan_prices.edit', [
            'planPrice' => $planPrice,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(PlanPrice $planPrice)
    {
        $planPrice->delete();
        return redirect()->route('plan_prices.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        if(!is_array($request->model_ids)){
            $modelIds = explode(",", $request->model_ids);
        }else{
            $modelIds = $request->model_ids;
        }

        $count = PlanPrice::whereIn('id', $modelIds)->delete();

        if($request->ajax()){
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('plan_prices.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
