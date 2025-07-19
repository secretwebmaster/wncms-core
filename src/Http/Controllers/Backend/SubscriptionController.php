<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Subscription;
use Wncms\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        // Add filters if needed
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $subscriptions = $q->paginate($request->page_size ?? 100)->withQueryString();

        $plans = wncms()->getModelClass('plan')::all();

        return $this->view('backend.subscriptions.index', [
            'page_title' => wncms_model_word('subscription', 'management'),
            'subscriptions' => $subscriptions,
            'plans' => $plans,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $subscription = $this->modelClass::find($id);
            if (!$subscription) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $subscription = new $this->modelClass;
        }

        $plans = wncms()->getModelClass('plan')::all();
        $users = wncms()->getModelClass('user')::orderBy('username', 'asc')->get();

        return $this->view('backend.subscriptions.create', [
            'page_title' => wncms_model_word('subscription', 'management'),
            'subscription' => $subscription,
            'plans' => $plans,
            'users' => $users,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'subscribed_at' => 'required|date',
            'expired_at' => 'nullable|date|after:subscribed_at',
            'status' => 'required|in:active,expired,cancelled',
        ], [
            'user_id.required' => __('wncms::word.user_required'),
            'plan_id.required' => __('wncms::word.plan_required'),
            'subscribed_at.required' => __('wncms::word.subscribed_at_required'),
            'expired_at.after' => __('wncms::word.expired_after_subscribed'),
            'status.required' => __('wncms::word.status_required'),
        ]);

        $subscription = $this->modelClass::create([
            'user_id' => $request->user_id,
            'plan_id' => $request->plan_id,
            'subscribed_at' => $request->subscribed_at,
            'expired_at' => $request->expired_at,
            'status' => $request->status,
        ]);

        $this->flush();

        return redirect()->route('subscriptions.edit', [
            'subscription' => $subscription,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $subscription = $this->modelClass::find($id);
        if (!$subscription) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $plans = wncms()->getModelClass('plan')::all();
        $users = wncms()->getModelClass('user')::orderBy('username', 'asc')->get();

        return $this->view('backend.subscriptions.edit', [
            'page_title' => wncms_model_word('subscription', 'management'),
            'subscription' => $subscription,
            'plans' => $plans,
            'users' => $users,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $subscription = $this->modelClass::find($id);
        if (!$subscription) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'subscribed_at' => 'required|date',
            'expired_at' => 'nullable|date|after:subscribed_at',
            'status' => 'required|in:active,expired,cancelled',
        ]);

        $subscription->update($validated);

        $this->flush();

        return redirect()->route('subscriptions.edit', [
            'subscription' => $subscription,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }
}
