<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Subscription;
use Wncms\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $q = Subscription::query();

        // Add filters if needed
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $subscriptions = $q->paginate($request->page_size ?? 100)->withQueryString();

        return view('wncms::backend.subscriptions.index', [
            'page_title' => wncms_model_word('subscription', 'management'),
            'subscriptions' => $subscriptions,
        ]);
    }

    public function create(Subscription $subscription = null)
    {
        $subscription ??= new Subscription;

        return view('wncms::backend.subscriptions.create', [
            'page_title' => wncms_model_word('subscription', 'management'),
            'subscription' => $subscription,
            'plans' => Plan::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'subscribed_at' => 'required|date',
            'expired_at' => 'nullable|date|after:subscribed_at',
            'status' => 'required|in:active,expired,cancelled',
        ]);

        $subscription = Subscription::create($validated);

        wncms()->cache()->flush(['subscriptions']);

        return redirect()->route('subscriptions.edit', [
            'subscription' => $subscription,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Subscription $subscription)
    {
        return view('wncms::backend.subscriptions.edit', [
            'page_title' => wncms_model_word('subscription', 'management'),
            'subscription' => $subscription,
            'plans' => Plan::all(),
        ]);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'subscribed_at' => 'required|date',
            'expired_at' => 'nullable|date|after:subscribed_at',
            'status' => 'required|in:active,expired,cancelled',
        ]);

        $subscription->update($validated);

        wncms()->cache()->flush(['subscriptions']);

        return redirect()->route('subscriptions.edit', [
            'subscription' => $subscription,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return redirect()->route('subscriptions.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        $modelIds = is_array($request->model_ids)
            ? $request->model_ids
            : explode(",", $request->model_ids);

        $count = Subscription::whereIn('id', $modelIds)->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('subscriptions.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
