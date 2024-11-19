<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Plan;
use Wncms\Models\PlanPrice;
use Illuminate\Http\Request;
use App\Enums\DurationUnit;

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
            'free_trial_duration' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'prices' => 'required|array', // New field for prices
            'prices.*.duration' => 'nullable|integer|min:1', // Duration in days
            'prices.*.price' => 'required|numeric|min:0',
            'prices.*.duration_unit' => 'required|in:day,week,month,year',
            'prices.*.is_lifetime' => 'required|boolean', // Whether it's a lifetime price
        ]);

        $plan = Plan::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'free_trial_duration' => $validated['free_trial_duration'] ?? 0,
            'status' => $validated['status'],
        ]);

        // Create associated plan prices
        foreach ($validated['prices'] as $priceData) {
            // dd($priceData);
            $plan->prices()->create($priceData);
        }

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
            'free_trial_duration' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'prices' => 'required|array', // New field for prices
            'prices.*.duration' => 'nullable|integer|min:1', // Duration in days
            'prices.*.price' => 'required|numeric|min:0',
            'prices.*.duration_unit' => 'required|in:day,week,month,year',
            'prices.*.is_lifetime' => 'required|boolean', // Whether it's a lifetime price
        ]);

        // Update plan details
        $plan->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'free_trial_duration' => $validated['free_trial_duration'],
            'status' => $validated['status'],
        ]);

        // Update associated plan prices
        foreach ($validated['prices'] as $priceData) {
            // Update or create plan prices
            PlanPrice::updateOrCreate(
                ['plan_id' => $plan->id, 'duration' => $priceData['duration']],
                $priceData
            );
        }

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
