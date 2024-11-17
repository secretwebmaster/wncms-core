<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Enums\CardStatus;
use Wncms\Http\Controllers\Controller;
use Wncms\Models\Card;
use Wncms\Models\Plan;
use Illuminate\Http\Request;
use Wncms\Facades\Wncms;

class CardController extends Controller
{
    public function index(Request $request)
    {
        $q = Card::query();
    
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
    
        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }
    
        $cards = $q->paginate($request->page_size ?? 100);
    
        return view('wncms::backend.cards.index', [
            'page_title' => wncms_model_word('card', 'management'),
            'cards' => $cards,
            'statuses' => CardStatus::values(), // Passing statuses from the enum
        ]);
    }
    

    public function create(Card $card = null)
    {
        $card ??= new Card;
        $userModal = Wncms::getUserModelClass();

        return view('wncms::backend.cards.create', [
            'page_title' => wncms_model_word('card', 'create'),
            'card' => $card,
            'users' => $userModal::all(),
            'plans' => Plan::all(),
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

        $card = Card::create($validated);

        wncms()->cache()->flush(['cards']);

        return redirect()->route('cards.edit', $card)
            ->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Card $card)
    {
        $userModal = Wncms::getUserModelClass();

        return view('wncms::backend.cards.edit', [
            'page_title' => wncms_model_word('card', 'edit'),
            'card' => $card,
            'users' => $userModal::all(),
            'plans' => Plan::all(),
        ]);
    }

    public function update(Request $request, Card $card)
    {
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

        wncms()->cache()->flush(['cards']);

        return redirect()->route('cards.edit', $card)
            ->withMessage(__('wncms::word.successfully_updated'));
    }
}
