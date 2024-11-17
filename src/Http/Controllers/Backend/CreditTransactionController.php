<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\CreditTransaction;
use Wncms\Models\User;
use Illuminate\Http\Request;

class CreditTransactionController extends Controller
{
    /**
     * Display a listing of the credit transactions.
     */
    public function index(Request $request)
    {
        $q = CreditTransaction::query();

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

        return view('wncms::backend.credit_transactions.index', [
            'page_title' => wncms_model_word('credit_transaction', 'management'),
            'creditTransactions' => $creditTransactions,
            'users' => User::all(),
        ]);
    }

    /**
     * Show the form for creating a new credit transaction.
     */
    public function create()
    {
        return view('wncms::backend.credit_transactions.create', [
            'page_title' => wncms_model_word('credit_transaction', 'create'),
            'users' => User::all(),
        ]);
    }

    /**
     * Store a newly created credit transaction in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'credit_type' => 'required|string|in:balance,points',
            'amount' => 'required|numeric|min:0.01',
            'transaction_type' => 'required|string|in:earn,spend,recharge,refund,adjustment',
            'remark' => 'nullable|string|max:255',
        ]);

        $creditTransaction = CreditTransaction::create($validated);

        wncms()->cache()->flush(['creditTransactions']);

        return redirect()->route('credit_transactions.edit', $creditTransaction)
            ->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified credit transaction.
     */
    public function edit(CreditTransaction $creditTransaction)
    {
        return view('wncms::backend.credit_transactions.edit', [
            'page_title' => wncms_model_word('credit_transaction', 'edit'),
            'creditTransaction' => $creditTransaction,
            'users' => User::all(),
        ]);
    }

    /**
     * Update the specified credit transaction in storage.
     */
    public function update(Request $request, CreditTransaction $creditTransaction)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'credit_type' => 'required|string|in:balance,points',
            'amount' => 'required|numeric|min:0.01',
            'transaction_type' => 'required|string|in:earn,spend,recharge,refund,adjustment',
            'remark' => 'nullable|string|max:255',
        ]);

        $creditTransaction->update($validated);

        wncms()->cache()->flush(['creditTransactions']);

        return redirect()->route('credit_transactions.edit', $creditTransaction)
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    /**
     * Remove the specified credit transaction from storage.
     */
    public function destroy(CreditTransaction $creditTransaction)
    {
        $creditTransaction->delete();

        wncms()->cache()->flush(['creditTransactions']);

        return redirect()->route('credit_transactions.index')
            ->withMessage(__('wncms::word.successfully_deleted'));
    }

    /**
     * Bulk delete credit transactions.
     */
    public function bulk_delete(Request $request)
    {
        $modelIds = is_array($request->model_ids)
            ? $request->model_ids
            : explode(",", $request->model_ids);

        $count = CreditTransaction::whereIn('id', $modelIds)->delete();

        wncms()->cache()->flush(['creditTransactions']);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('credit_transactions.index')
            ->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
