<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Facades\PlanManager;
use Wncms\Http\Requests\PlanFormRequest;

class PlanController extends BackendController
{
    /**
     * Display a listing of the plans.
     */
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $plans = $q->paginate($request->page_size ?? 100);

        return $this->view('backend.plans.index', [
            'page_title' => wncms_model_word('plan', 'management'),
            'plans' => $plans,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    /**
     * Show the form for creating a new plan.
     * To clone a plan, pass the existing plan model as parameter.
     * 
     * @param $id
     * @return \Illuminate\View\View
     */
    public function create($id = null)
    {
        if ($id) {
            $plan = $this->modelClass::find($id);
            if (!$plan) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $plan = new $this->modelClass;
        }

        return $this->view('backend.plans.create', [
            'page_title' => wncms_model_word('plan', 'create'),
            'plan' => $plan,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    /**
     * Store a newly created plan in storage.
     */
    public function store(Request $request)
    {
        $validated = resolve(PlanFormRequest::class)->validated();

        // Create plan
        $plan = PlanManager::create($validated);

        // Clear cache
        $this->flush();

        return redirect()->route('plans.edit', $plan)->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit($id)
    {
        $plan = $this->modelClass::find($id);
        if (!$plan) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.plans.edit', [
            'page_title' => wncms_model_word('plan', 'edit'),
            'plan' => $plan,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $plan = $this->modelClass::find($id);
        if (!$plan) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $validated = resolve(PlanFormRequest::class)->validated();

        PlanManager::update($plan, $validated);

        $this->flush();

        return redirect()->route('plans.edit', $plan)->withMessage(__('wncms::word.successfully_updated'));
    }
}
