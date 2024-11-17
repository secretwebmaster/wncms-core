<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Credit;
use Wncms\Models\User;
use Illuminate\Http\Request;
use Wncms\Models\CreditTransaction;

class CreditController extends Controller
{
    public function index(Request $request)
    {
        $q = Credit::query();

        if ($request->filled('credit_type')) {
            $q->where('credit_type', $request->credit_type);
        }

        $credits = $q->paginate($request->page_size ?? 100);

        return view('wncms::backend.credits.index', [
            'page_title' => wncms_model_word('credit', 'management'),
            'credits' => $credits,
        ]);
    }

    public function create(Credit $credit = null)
    {
        $credit ??= new Credit;

        return view('wncms::backend.credits.create', [
            'page_title' => wncms_model_word('credit', 'create'),
            'credit' => $credit,
            'users' => User::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'credit_type' => 'required|string|in:' . implode(',', \Wncms\Enums\CreditType::values()),
            'amount' => 'required|numeric|min:0',
        ]);

        $existingCredit = Credit::where([
            'user_id' => $validated['user_id'],
            'credit_type' => $validated['credit_type'],
        ])->first();

        if ($existingCredit) {
            return back()
                ->withErrors([
                    'credit_type' => __('wncms::word.credit_already_exists', [
                        'id' => $existingCredit->id,
                        'value' => $existingCredit->amount,
                    ]),
                ])
                ->withInput();
        }

        $credit = Credit::create($validated);

        return redirect()->route('credits.edit', $credit)
            ->withMessage(__('wncms::word.successfully_created_or_updated'));
    }


    public function edit(Credit $credit)
    {
        return view('wncms::backend.credits.edit', [
            'page_title' => wncms_model_word('credit', 'edit'),
            'credit' => $credit,
            'users' => User::all(),
        ]);
    }

    public function update(Request $request, Credit $credit)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'credit_type' => 'required|string|in:' . implode(',', \Wncms\Enums\CreditType::values()),
            'amount' => 'required|numeric|min:0',
        ]);

        $credit->update($validated);

        return redirect()->route('credits.edit', $credit)
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Credit $credit)
    {
        $credit->delete();

        return redirect()->route('credits.index')
            ->withMessage(__('wncms::word.successfully_deleted'));
    }

    /**
     * Show the recharge form.
     */
    public function show_recharge()
    {
        return view('wncms::backend.credits.recharge', [
            'page_title' => __('wncms::word.credit_recharge'),
            'users' => User::all(),
            'types' => \Wncms\Enums\CreditType::values(),
        ]);
    }

    /**
     * Handle recharge submission.
     */
    public function handle_recharge(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'credit_type' => 'required|string|in:' . implode(',', \Wncms\Enums\CreditType::values()),
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Find the credit entry or create a new one
        $credit = Credit::firstOrNew([
            'user_id' => $validated['user_id'],
            'credit_type' => $validated['credit_type'],
        ]);

        // Update the amount
        $credit->amount = ($credit->exists ? $credit->amount : 0) + $validated['amount'];
        $credit->save();

        // Create a credit transaction record
        CreditTransaction::create([
            'user_id' => $validated['user_id'],
            'credit_type' => $validated['credit_type'],
            'amount' => $validated['amount'],
            'transaction_type' => 'recharge',
            'remark' => __('wncms::word.recharge_added_by_admin', [
                'admin_id' => auth()->id(),
            ]),
        ]);

        return redirect()->route('credits.index')
            ->withMessage(__('wncms::word.credit_recharged_successfully', [
                'user' => $credit->user->username,
                'amount' => $validated['amount'],
                'type' => $validated['credit_type'],
            ]));
    }
}
