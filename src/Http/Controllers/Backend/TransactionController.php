<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $transactions = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.transactions.index', [
            'page_title' => wncms_model_word('transaction', 'management'),
            'transactions' => $transactions,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $transaction = $this->modelClass::find($id);
            if (!$transaction) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $transaction = new $this->modelClass;
        }

        $orders = wncms()->getModelClass('order')::all();

        return $this->view('backend.transactions.create', [
            'page_title' => wncms_model_word('transaction', 'management'),
            'transaction' => $transaction,
            'orders' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'ref_id' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'nullable|string|max:255',
        ], [
            'order_id.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.order_id')]),
            'amount.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.amount')]),
            'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
            'payment_method.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.payment_method')]),
        ]);

        $transaction = $this->modelClass::create([
            'order_id' => $request->order_id,
            'status' => $request->status,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'ref_id' => $request->ref_id,
            'is_fraud' => $request->is_fraud ? true : false,
        ]);

        $this->flush();

        return redirect()->route('transactions.edit', [
            'id' => $transaction,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $transaction = $this->modelClass::find($id);
        if (!$transaction) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $orders = wncms()->getModelClass('order')::all();
        
        return $this->view('backend.transactions.edit', [
            'page_title' => wncms_model_word('transaction', 'management'),
            'transaction' => $transaction,
            'orders' => $orders,
        ]);
    }

    public function update(Request $request, $id)
    {
        $transaction = $this->modelClass::find($id);
        if (!$transaction) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'ref_id' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'nullable|string|max:255',
        ], [
            'order_id.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.order_id')]),
            'amount.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.amount')]),
            'status.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
            'payment_method.required' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.payment_method')]),
        ]);

        $transaction->update([
            'order_id' => $request->order_id,
            'status' => $request->status,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'ref_id' => $request->ref_id,
            'is_fraud' => $request->is_fraud ? true : false,
        ]);

        $this->flush();

        return redirect()->route('transactions.edit', [
            'id' => $transaction,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
