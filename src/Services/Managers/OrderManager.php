<?php

namespace Wncms\Services\Managers;

use Wncms\Facades\PlanManager;
use Wncms\Facades\Wncms;
use Wncms\Models\Order;
use Wncms\Models\Plan;
use Wncms\Models\Price;
use Wncms\Models\Subscription;
use Wncms\Models\Transaction;

class OrderManager
{
    public function create($user, $price, $quantity = 1)
    {
        // find pending order with the same plan and price
        $order = $user->orders()->where('status', 'pending_payment')->whereHas('order_items', function ($query) use ($price) {
            $query->where('order_itemable_type', get_class($price))->where('order_itemable_id', $price->id);
        })->first();

        if($order){
            return $order;
        }

        // create empty order
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending_payment',
            'total_amount' => 0,
        ]);

        $itemClass = get_class($price);

        // creater order item for the plan
        $orderItem = $order->order_items()->create([
            'order_id' => $order->id,
            'order_itemable_type' => $itemClass,
            'order_itemable_id' => $price->id,
            'quantity' => $quantity,
            'amount' => $price->amount,
        ]);

        foreach ($order->order_items as $item) {
            $order->total_amount += $item->amount * $item->quantity;
        }

        $order->save();

        return $order;
    }

    public function complete(Order $order, $ref_id = null)
    {
        Transaction::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'status' => 'completed',
            'payment_method' => $order->payment_gateway->slug ?? 'unknown',
            'ref_id' => $ref_id,
        ]);
        // find order items

        foreach ($order->order_items as $item) {
            if($item->order_itemable instanceof Price){
                if($item->order_itemable->priceable instanceof Plan){
                    $user = $order->user;
                    $plan = $item->order_itemable->priceable;
                    $price = $item->order_itemable;
                    PlanManager::subscribe($user, $plan, $price);

                }elseif($item->order_itemable->priceable instanceof Product){
                    $product = $item->order_itemable->priceable;
                    $user = $order->user;

                    // grant access to product

                    // create shipping task

                    dd('handle product download or create shipping task');
                }

                // notify user
            }else{
                dd('unknown order item type');
            }
        }

        $order->update([
            'payment_method' => $order->payment_gateway->slug ?? 'unknown',
            'status' => 'completed',
        ]);

        return $order;
    }
}