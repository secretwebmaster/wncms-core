<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $q = Transaction::query();

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $transactions = $q->paginate($request->page_size ?? 100);

        return view('wncms::backend.transactions.index', [
            'page_title' => wncms_model_word('transaction', 'management'),
            'transactions' => $transactions,
        ]);
    }

    public function create(Transaction $transaction = null)
    {
        $transaction ??= new Transaction;

        return view('wncms::backend.transactions.create', [
            'page_title' => wncms_model_word('transaction', 'management'),
            'transaction' => $transaction,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'transaction_id' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'nullable|string|max:255',
        ]);

        $transaction = Transaction::create($validated);

        wncms()->cache()->flush(['transactions']);

        return redirect()->route('transactions.edit', [
            'transaction' => $transaction,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Transaction $transaction)
    {
        return view('wncms::backend.transactions.edit', [
            'page_title' => wncms_model_word('transaction', 'management'),
            'transaction' => $transaction,
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'transaction_id' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'nullable|string|max:255',
        ]);

        $transaction->update($validated);

        wncms()->cache()->flush(['transactions']);

        return redirect()->route('transactions.edit', [
            'transaction' => $transaction,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return redirect()->route('transactions.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        $modelIds = is_array($request->model_ids)
            ? $request->model_ids
            : explode(",", $request->model_ids);

        $count = Transaction::whereIn('id', $modelIds)->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('transactions.index')->withMessage(
            __('wncms::word.successfully_deleted_count', ['count' => $count])
        );
    }
}
