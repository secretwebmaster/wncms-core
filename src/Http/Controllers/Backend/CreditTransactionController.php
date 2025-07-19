<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class CreditTransactionController extends BackendController
{
    /**
     * Display a listing of the credit transactions.
     */
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        // Optional filters
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->user_id);
        }

        if ($request->filled('credit_type')) {
            $q->where('credit_type', $request->credit_type);
        }

        if ($request->filled('transaction_type')) {
            $q->where('transaction_type', $request->transaction_type);
        }

        $creditTransactions = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.credit_transactions.index', [
            'page_title' => wncms_model_word('credit_transaction', 'management'),
            'creditTransactions' => $creditTransactions,
            'transactionTypes' => $this->modelClass::TRANSACTION_TYPES,
            'creditTypes' => wncms()->getModelClass('credit')::TYPES,
            'users' => wncms()->getModelClass('user')::all(),
        ]);
    }

    /**
     * Show the form for creating a new credit transaction.
     */
    public function create($id = null)
    {
        return $this->view('backend.credit_transactions.create', [
            'page_title' => wncms_model_word('credit_transaction', 'create'),
            'users' => wncms()->getModelClass('user')::all(),
            'creditTypes' => wncms()->getModelClass('credit')::TYPES,
        ]);
    }

    /**
     * Store a newly created credit transaction in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'credit_type' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'transaction_type' => 'required|string|in:earn,spend,recharge,refund,adjustment',
            'remark' => 'nullable|string|max:255',
        ],[
            'user_id.required' => __('wncms::word.user_required'),
            'credit_type.required' => __('wncms::word.credit_type_required'),
            'amount.required' => __('wncms::word.amount_required'),
            'transaction_type.required' => __('wncms::word.transaction_type_required'),
        ]);


        $creditTransaction = $this->modelClass::create($validated);

        $this->flush();

        return redirect()->route('credit_transactions.edit', $creditTransaction)->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified credit transaction.
     */
    public function edit($id)
    {
        $creditTransaction = $this->modelClass::find($id);
        if (!$creditTransaction) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.credit_transactions.edit', [
            'page_title' => wncms_model_word('credit_transaction', 'edit'),
            'creditTransaction' => $creditTransaction,
            'creditTypes' => wncms()->getModelClass('credit')::TYPES,
            'users' => wncms()->getModelClass('user')::all(),
        ]);
    }

    /**
     * Update the specified credit transaction in storage.
     */
    public function update(Request $request, $id)
    {
        $creditTransaction = $this->modelClass::find($id);
        if (!$creditTransaction) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'credit_type' => 'required|string|in:balance,points',
            'amount' => 'required|numeric|min:0.01',
            'transaction_type' => 'required|string|in:earn,spend,recharge,refund,adjustment',
            'remark' => 'nullable|string|max:255',
        ]);

        $creditTransaction->update($validated);

        $this->flush();

        return redirect()->route('credit_transactions.edit', $creditTransaction)->withMessage(__('wncms::word.successfully_updated'));
    }
}
