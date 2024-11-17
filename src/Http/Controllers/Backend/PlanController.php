<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the plans.
     */
    public function index(Request $request)
    {
        $q = Plan::query();

        // Apply filters
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('billing_cycle')) {
            $q->where('billing_cycle', $request->billing_cycle);
        }

        $plans = $q->paginate($request->page_size ?? 100)->withQueryString();

        return view('wncms::backend.plans.index', [
            'page_title' => wncms_model_word('plan', 'management'),
            'plans' => $plans,
        ]);
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create()
    {
        return view('wncms::backend.plans.create', [
            'page_title' => wncms_model_word('plan', 'create'),
        ]);
    }

    /**
     * Store a newly created plan in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:daily,weekly,monthly,yearly,one-time',
            'is_lifetime' => 'boolean',
            'free_trial_duration' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $plan = Plan::create($validated);

        // Clear cache
        wncms()->cache()->flush(['plans']);

        return redirect()->route('plans.edit', $plan)
            ->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan)
    {
        return view('wncms::backend.plans.edit', [
            'page_title' => wncms_model_word('plan', 'edit'),
            'plan' => $plan,
        ]);
    }

    /**
     * Update the specified plan in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:daily,weekly,monthly,yearly,one-time',
            'is_lifetime' => 'boolean',
            'free_trial_duration' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $plan->update($validated);

        // Clear cache
        wncms()->cache()->flush(['plans']);

        return redirect()->route('plans.edit', $plan)
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    /**
     * Remove the specified plan from storage.
     */
    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()->route('plans.index')
            ->withMessage(__('wncms::word.successfully_deleted'));
    }

    /**
     * Bulk delete plans.
     */
    public function bulk_delete(Request $request)
    {
        $modelIds = is_array($request->model_ids)
            ? $request->model_ids
            : explode(",", $request->model_ids);

        $count = Plan::whereIn('id', $modelIds)->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('plans.index')
            ->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
