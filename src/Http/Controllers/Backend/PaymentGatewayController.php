<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\PaymentGateway;
use Illuminate\Http\Request;

class PaymentGatewayController extends BackendController
{
    public function getModelClass(): string
    {
        return config('wncms.models.payment_gateway', \Wncms\Models\PaymentGateway::class);
    }

    public function index(Request $request)
    {
        $q = PaymentGateway::query();

        $paymentGateways = $q->paginate($request->page_size ?? 100);

        return view('wncms::backend.payment_gateways.index', [
            'page_title' =>  wncms_model_word('payment_gateway', 'management'),
            'payment_gateways' => $paymentGateways,
            'statuses' => PaymentGateway::STATUSES,
        ]);
    }

    public function create(PaymentGateway $paymentGateway = null)
    {
        $paymentGateway ??= new PaymentGateway;

        return view('wncms::backend.payment_gateways.create', [
            'page_title' =>  wncms_model_word('payment_gateway', 'management'),
            'paymentGateway' => $paymentGateway,
            'statuses' => PaymentGateway::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());

        $paymentGateway = PaymentGateway::create([
            'name' => $request->name,
            'status' => $request->status ?? 'active',
            'slug' => $request->slug,
            'type' => $request->type,
            'account_id' => $request->account_id,
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'endpoint' => $request->endpoint,
            'attributes' => $request->attributes ?? [],
            'description' => $request->description,
        ]);

        wncms()->cache()->flush(['payment_gateways']);

        return redirect()->route('payment_gateways.edit', [
            'paymentGateway' => $paymentGateway,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(PaymentGateway $paymentGateway)
    {
        return view('wncms::backend.payment_gateways.edit', [
            'page_title' =>  wncms_model_word('payment_gateway', 'management'),
            'paymentGateway' => $paymentGateway,
            'statuses' => PaymentGateway::STATUSES,
        ]);
    }

    public function update(Request $request, PaymentGateway $paymentGateway)
    {
        $attributes = collect($request->input('attributes', []))
        ->filter(fn($attr) => isset($attr['key'], $attr['value']) && $attr['key'] !== null && $attr['value'] !== null)
        ->values() // Reset array keys
        ->toArray();

        $paymentGateway->update([
            'name' => $request->name,
            'status' => $request->status ?? 'active',
            'slug' => $request->slug,
            'type' => $request->type,
            'account_id' => $request->account_id,
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'endpoint' => $request->endpoint,
            'attributes' => $attributes,
            'description' => $request->description,
        ]);

        wncms()->cache()->flush(['payment_gateways']);

        return redirect()->route('payment_gateways.edit', [
            'paymentGateway' => $paymentGateway,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }


    public function destroy(PaymentGateway $paymentGateway)
    {
        $paymentGateway->delete();
        return redirect()->route('payment_gateways.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        if (!is_array($request->model_ids)) {
            $modelIds = explode(",", $request->model_ids);
        } else {
            $modelIds = $request->model_ids;
        }

        $count = PaymentGateway::whereIn('id', $modelIds)->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('payment_gateways.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
