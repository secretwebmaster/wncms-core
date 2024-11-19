<?php

namespace Wncms\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Wncms\Facades\PlanManager;
use Wncms\Facades\Wncms;
use Wncms\Models\Plan;

class PlanController extends FrontendController
{
    // Show the list of plans
    public function index()
    {
        $plans = Plan::all();

        return Wncms::view(
            name: "frontend.theme.{$this->theme}.plans.index",
            params: [
                'plans' => $plans,
            ],
            fallback: 'wncms::frontend.theme.default.plans.index',
        );
    }

    // Show the details of a specific plan
    public function show(Plan $plan)
    {
        return Wncms::view(
            name: "frontend.theme.{$this->theme}.plans.show",
            params: [
                'plan' => $plan,
            ],
            fallback: 'wncms::frontend.theme.default.plans.show',
        );
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request)
    {
        // find plan
        $plan = Plan::find($request->plan_id);
        if (!$plan) {
            return redirect()->back()->with('error', 'Plan not found');
        }

        // find price
        $price = $plan->prices()->where('id', $request->price_id)->first();
        if (!$price) {
            return redirect()->back()->with('error', 'Price not found');
        }

        $result = PlanManager::subscribe(auth()->user(), $plan, $price);

        if (isset($result['error'])) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->back()->with('message', 'Subscribed successfully');

    }

    /**
     * Unsubscribe from a plan
     */
    public function unsubscribe(Request $request)
    {
        if(!$request->subscription_id) {
            return redirect()->back()->with('error', 'Subscription ID is required');
        }

        try {
            $subscription = PlanManager::unsubscribe(auth()->user(), $request->subscription_id);
    
            return redirect()->route('frontend.users.subscription')->with('message', 'Unsubscribed successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
