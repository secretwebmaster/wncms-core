<?php

namespace Wncms\Services\Managers;

use Wncms\Facades\Wncms;
use Wncms\Models\Plan;
use Wncms\Models\Price;

class PlanManager
{
    public function calculateExpiredAt(Price $price, $from = null)
    {
        if($price->is_lifetime){
            return null;
        }
        
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

    /**
     * Subscribe a user to a plan
     * @param User $user
     * @param Plan $plan
     * @param Price $price
     * @return Subscription
     */
    public function subscribe($user, Plan $plan, Price $price)
    {
        // check if user has an subscription to this plan but with different duration
        $existingSubscription = $user->subscriptions()->where('plan_id', $plan->id)->where('price_id', '!=', $price->id)->where('status', 'active')->first();

        // switch duration
        if($existingSubscription){
            $existingSubscription->update([
                'price_id' => $price->id,
                'expired_at' => $this->calculateExpiredAt($price, $existingSubscription->expired_at),
            ]);
            return $existingSubscription;
        }

        // create new subscription
        $newSubscription = $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'price_id' => $price->id,
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
    public function canSubscribe($user, Plan $plan, Price $price)
    {
        // check if user already subscribed to this plan
        if ($user->subscriptions()->where('plan_id', $plan->id)->where('price_id', $price->id)->where('status', 'active')->exists()) {
            return false;
        }

        // check if user has enough balance
        if ($user->balance < $price->amount) {
            return false;
        }

        return true;
    }

    /**
     * Create a new plan
     * @param array $data
     * @return Plan
     */
    public function create($data)
    {
        $plan = Plan::create([
            'name' => $data['name'],
            'slug' => Wncms::getUniqueSlug('plans'),
            'description' => $data['description'],
            'free_trial_duration' => $data['free_trial_duration'] ?? 0,
            'status' => $data['status'],
        ]);

        // Create associated plan prices
        foreach ($data['prices'] as $priceData) {
            // dd($priceData);
            $plan->prices()->create([
                'amount' => $priceData['amount'],
                'duration' => $priceData['duration'],
                'duration_unit' => $priceData['duration_unit'],
                'is_lifetime' => $priceData['is_lifetime'],
                // 'attributes' => [],
                // 'stock' => 0,
            ]);
        }

        return $plan;
    }

    /**
     * Update a plan
     * @param Plan $plan
     * @param array $data
     * @return Plan
     */
    public function update(Plan $plan, $data)
    {
        $plan->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Wncms::getUniqueSlug('plans'),
            'description' => $data['description'],
            'free_trial_duration' => $data['free_trial_duration'] ?? 0,
            'status' => $data['status'],
        ]);

        $priceUds = [];

        // Update associated plan prices
        foreach ($data['prices'] as $priceData) {

            // get the price model
            $price = $plan->prices()
                ->where('duration', $priceData['duration'])
                ->where('duration_unit', $priceData['duration_unit'])
                ->first();

            if ($price) {
                $price->update([
                    'amount' => $priceData['amount'],
                    'is_lifetime' => $priceData['is_lifetime'],
                ]);
            } else {
                $price = $plan->prices()->create([
                    'amount' => $priceData['amount'],
                    'duration' => $priceData['duration'],
                    'duration_unit' => $priceData['duration_unit'],
                    'is_lifetime' => $priceData['is_lifetime'],
                ]);
            }

            $priceUds[] = $price->id;
        }

        // remove deleted prices
        $plan->prices()->whereNotIn('id', $priceUds)->delete();

        return $plan;
    }
}
