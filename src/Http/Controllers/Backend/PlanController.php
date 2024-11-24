<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Plan;
use Wncms\Models\Price;
use Illuminate\Http\Request;
use App\Enums\DurationUnit;
use Wncms\Facades\PlanManager;
use Wncms\Facades\Wncms;
use Wncms\Http\Requests\PlanFormRequest;

class PlanController extends BackendController
{
    /**
     * Display a listing of the plans.
     */
    public function index(Request $request)
    {
        $q = Plan::query();

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $plans = $q->paginate($request->page_size ?? 100)->withQueryString();

        return view('wncms::backend.plans.index', [
            'page_title' => wncms_model_word('plan', 'management'),
            'plans' => $plans,
            'statuses' => Plan::STATUSES,
        ]);
    }

    /**
     * Show the form for creating a new plan.
     * To clone a plan, pass the existing plan model as parameter.
     * 
     * @param Plan $plan
     * @return \Illuminate\View\View
     */
    public function create(Plan $plan = null)
    {
        $plan ??= new Plan;

        return view('wncms::backend.plans.create', [
            'page_title' => wncms_model_word('plan', 'create'),
            'plan' => $plan,
            'statuses' => Plan::STATUSES,
        ]);
    }

    /**
     * Store a newly created plan in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validated();

        // Create plan
        $plan = PlanManager::create($validated);

        // Clear cache
        $this->flush('plans');

        return redirect()->route('plans.edit', $plan)->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan)
    {
        return view('wncms::backend.plans.edit', [
            'page_title' => wncms_model_word('plan', 'edit'),
            'plan' => $plan,
            'statuses' => Plan::STATUSES,
        ]);
    }

    /**
     * Update the specified plan in storage.
     */
    public function update(PlanFormRequest $request, Plan $plan)
    {
        $validated = $request->validated();

        // Update plan
        PlanManager::update($plan, $validated);

        // Clear cache
        $this->flush('plans');

        return redirect()->route('plans.edit', $plan)->withMessage(__('wncms::word.successfully_updated'));
    }

    /**
     * Remove the specified plan from storage.
     */
    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()->route('plans.index')->withMessage(__('wncms::word.successfully_deleted'));
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

        return redirect()->route('plans.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
