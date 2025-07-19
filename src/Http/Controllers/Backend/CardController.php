<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;

class CardController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        $cards = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.cards.index', [
            'page_title' => wncms_model_word('card', 'management'),
            'cards' => $cards,
            'statuses' => $this->modelClass::STATUSES, // Passing statuses from the enum
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $card = $this->modelClass::find($id);
            if (!$card) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $card = new $this->modelClass;
        }
        $users = wncms()->getModel('user')->orderBy('username', 'asc')->get();

        return $this->view('backend.cards.create', [
            'page_title' => wncms_model_word('card', 'create'),
            'card' => $card,
            'users' => $users,
            'plans' => wncms()->getModelClass('plan')::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:cards,code|max:255',
            'type' => 'required|string|in:credit,plan,product',
            'value' => 'nullable|numeric|min:0',
            'plan_id' => 'nullable|exists:plans,id',
            'product_id' => 'nullable|exists:products,id',
            'user_id' => 'nullable|exists:users,id',
            'redeemed_at' => 'nullable|date',
            'expired_at' => 'nullable|date',
            'status' => 'required|string|in:active,redeemed,expired',
        ]);

        $card = $this->modelClass::create($validated);

        $this->flush();

        return redirect()->route('cards.edit', $card)->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $card = $this->modelClass::find($id);
        if (!$card) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.cards.edit', [
            'page_title' => wncms_model_word('card', 'edit'),
            'card' => $card,
            'users' => wncms()->getModelClass('user')::all(),
            'plans' => wncms()->getModelClass('plan')::all(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $card = $this->modelClass::find($id);
        if (!$card) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $validated = $request->validate([
            'code' => 'required|string|unique:cards,code,' . $card->id . '|max:255',
            'type' => 'required|string|in:credit,plan,product',
            'value' => 'nullable|numeric|min:0',
            'plan_id' => 'nullable|exists:plans,id',
            'product_id' => 'nullable|exists:products,id',
            'user_id' => 'nullable|exists:users,id',
            'redeemed_at' => 'nullable|date',
            'expired_at' => 'nullable|date',
            'status' => 'required|string|in:active,redeemed,expired',
        ]);

        $card->update($validated);

        $this->flush();

        return redirect()->route('cards.edit', $card)
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    public function bulkCreate(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:credit,plan,product',
            'value' => 'nullable|numeric|min:0',
            'plan_id' => 'nullable|exists:plans,id',
            'product_id' => 'nullable|exists:products,id',
            'amount' => 'required|integer|min:1|max:1000', // Limit to 1000 for safety
        ]);

        $cards = [];
        for ($i = 0; $i < $validated['amount']; $i++) {
            $cards[] = [
                'code' => \Str::uuid()->toString(), // Generate UUID
                'type' => $validated['type'],
                'value' => $validated['value'],
                'plan_id' => $validated['plan_id'],
                'product_id' => $validated['product_id'],
                'status' => 'active', // Default status
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->modelClass::insert($cards);

        $this->flush();

        return redirect()->route('cards.index')
            ->withMessage(__('wncms::word.bulk_created_successfully_count', ['count' => $validated['amount']]));
    }
}
