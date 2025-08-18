<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class PaymentGatewayController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        $paymentGateways = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.payment_gateways.index', [
            'page_title' =>  wncms_model_word('payment_gateway', 'management'),
            'payment_gateways' => $paymentGateways,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $paymentGateway = $this->modelClass::find($id);
            if (!$paymentGateway) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $paymentGateway = new $this->modelClass;
        }

        return $this->view('backend.payment_gateways.create', [
            'page_title' =>  wncms_model_word('payment_gateway', 'management'),
            'paymentGateway' => $paymentGateway,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());

        $paymentGateway = $this->modelClass::create([
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

        $this->flush();

        return redirect()->route('payment_gateways.edit', [
            'id' => $paymentGateway,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $paymentGateway = $this->modelClass::find($id);
        if (!$paymentGateway) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.payment_gateways.edit', [
            'page_title' =>  wncms_model_word('payment_gateway', 'management'),
            'paymentGateway' => $paymentGateway,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $paymentGateway = $this->modelClass::find($id);
        if (!$paymentGateway) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

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

        $this->flush();

        return redirect()->route('payment_gateways.edit', [
            'id' => $paymentGateway,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
