<?php

namespace Wncms\Services\Managers;

use Wncms\Facades\Wncms;
use Wncms\Models\Plan;
use Wncms\Models\PlanPrice;

class PlanManager
{
    public function calculateExpiredAt(PlanPrice $price, $from = null)
    {
        $from = $from ?? now();

        $duration = $price->duration;
        $durationUnit = $price->duration_unit;

        return match ($durationUnit) {
            'day' => $from->addDays($duration),
            'week' => $from->addWeeks($duration),
            'month' => $from->addMonths($duration),
            'year' => $from->addYears($duration),
            default => $from,
        };
    }

    public function subscribe($user, Plan $plan, PlanPrice $price)
    {
        // check if user already subscribed to this plan
        if ($user->subscriptions()->where('plan_id', $plan->id)->where('plan_price_id', $price->id)->where('status', 'active')->exists()) {
            return ['error' => 'Already subscribed to this plan'];
        }

        // check if user has enough balance
        if ($user->balance < $price->price) {
            return ['error' => 'Not enough balance'];
        }

        // deduct balance
        $user->credits()->where('type', 'balance')->decrement('amount', $price->price);

        // check if user has an subscription to this plan but with different duration
        $existingSubscription = $user->subscriptions()->where('plan_id', $plan->id)->where('plan_price_id', '!=', $price->id)->where('status', 'active')->first();

        // switch duration
        if($existingSubscription){
            $existingSubscription->update([
                'plan_price_id' => $price->id,
                'expired_at' => $this->calculateExpiredAt($price, $existingSubscription->expired_at),
            ]);
            return $existingSubscription;
        }

        // create new subscription
        $newSubscription = $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'plan_price_id' => $price->id,
            'subscribed_at' => now(),
            'expired_at' => $this->calculateExpiredAt($price),
        ]);

        return $newSubscription;
    }

    public function unsubscribe($user, $subscription)
    {
        if(!$subscription instanceof Subscription) {
            $subscription = $user->subscriptions()->find($subscription);
        }

        if (!$subscription) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        // check if subscription belongs to user
        if ($subscription->user_id != $user->id) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        if ($subscription->status == 'cancelled') {
            return response()->json(['error' => 'Subscription already cancelled'], 400);
        }

        $subscription->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Subscription cancelled'], 200);
    }

    /**
     * Check if a user can subscribe to a plan
     */
    public function canSubscribe($user, Plan $plan, PlanPrice $price)
    {
        // check if user already subscribed to this plan
        if ($user->subscriptions()->where('plan_id', $plan->id)->where('plan_price_id', $price->id)->where('status', 'active')->exists()) {
            return false;
        }

        // check if user has enough balance
        if ($user->balance < $price->price) {
            return false;
        }

        return true;
    }
}
