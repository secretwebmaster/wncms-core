<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Credit;
use Wncms\Models\User;
use Illuminate\Http\Request;
use Wncms\Models\CreditTransaction;

class CreditController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        if ($request->filled('username')) {
            $q->whereRelation('user', 'username', $request->username);
        }

        if ($request->filled('amount')) {
            $q->where('amount', $request->amount);
        }

        if($request->filled('keyword')){
            $q->where(function ($subq) use ($request) {
                $subq->orWhere('id', $request->keyword)
                    ->orWhereHas('user', function ($subq) use ($request) {
                        $subq->where('username', 'like', "%$request->keyword%");
                    });
            });
        }

        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        $credits = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.credits.index', [
            'page_title' => wncms_model_word('credit', 'management'),
            'credits' => $credits,
            'types' => $this->modelClass::TYPES,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $credit = $this->modelClass::find($id);
            if (!$credit) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $credit = new $this->modelClass;
        }

        return $this->view('backend.credits.create', [
            'page_title' => wncms_model_word('credit', 'create'),
            'credit' => $credit,
            'users' => wncms()->getModelClass('user')::all(),
            'types' => $this->modelClass::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:' . implode(',', $this->modelClass::TYPES),
            'amount' => 'required|numeric|min:0',
        ]);

        $existingCredit = $this->modelClass::where([
            'user_id' => $validated['user_id'],
            'type' => $validated['type'],
        ])->first();

        if ($existingCredit) {
            return back()
                ->withErrors([
                    'type' => __('wncms::word.credit_already_exists', [
                        'id' => $existingCredit->id,
                        'value' => $existingCredit->amount,
                    ]),
                ])
                ->withInput();
        }

        $credit = $this->modelClass::create($validated);

        return redirect()->route('credits.edit', $credit)->withMessage(__('wncms::word.successfully_created_or_updated'));
    }

    public function edit($id)
    {
        $credit = $this->modelClass::find($id);
        if (!$credit) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.credits.edit', [
            'page_title' => wncms_model_word('credit', 'edit'),
            'credit' => $credit,
            'users' => wncms()->getModelClass('user')::all(),
            'types' => $this->modelClass::TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $credit = $this->modelClass::find($id);
        if (!$credit) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $validated = $request->validate([
            // 'user_id' => 'required|exists:users,id',
            // 'type' => 'required|string|in:' . implode(',', $this->modelClass::TYPES),
            'amount' => 'required|numeric|min:0',
        ]);

        $credit->update($validated);

        return redirect()->route('credits.edit', $credit)->withMessage(__('wncms::word.successfully_updated'));
    }

    /**
     * Show the recharge form.
     */
    public function show_recharge()
    {
        return $this->view('backend.credits.recharge', [
            'page_title' => __('wncms::word.credit_recharge'),
            'users' => wncms()->getModelClass('user')::all(),
            'types' => $this->modelClass::TYPES,
        ]);
    }

    /**
     * Handle recharge submission.
     */
    public function handle_recharge(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:' . implode(',', $this->modelClass::TYPES),
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Find the credit entry or create a new one
        $credit = $this->modelClass::firstOrNew([
            'user_id' => $validated['user_id'],
            'type' => $validated['type'],
        ]);

        // Update the amount
        $credit->amount = ($credit->exists ? $credit->amount : 0) + $validated['amount'];
        $credit->save();

        // Create a credit transaction record
        wncms()->getModelClass('credit_transaction')::create([
            'user_id' => $validated['user_id'],
            'credit_type' => $validated['type'],
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
                'type' => $validated['type'],
            ]));
    }
}
