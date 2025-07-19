<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Order;
use Illuminate\Http\Request;

class OrderItemController extends BackendController
{
    /**
     * Display a listing of the order items.
     */
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        // Filters
        if ($request->filled('order_id')) {
            $orderId = $request->order_id;
            $q->where(function ($subq) use ($orderId) {
                $subq->where('order_id', $orderId)
                    ->orWhereHas('order', function ($subq2) use ($orderId) {
                        $subq2->where('slug', $orderId)->orWhere('slug', 'ORD-' . $orderId);
                    });
            });
        }

        if ($request->filled('order_itemable_type')) {
            $q->where('order_itemable_type', $request->order_itemable_type);
        }

        if ($request->filled('order_itemable_id')) {
            $q->where('order_itemable_id', $request->order_itemable_id);
        }

        $orderItems = $q->paginate($request->page_size ?? 100)->withQueryString();

        return $this->view('backend.order_items.index', [
            'page_title' => wncms_model_word('order_item', 'management'),
            'orderItems' => $orderItems,
            'itemTypes' => $this->getItemTypes(), // Retrieve all possible item types
        ]);
    }

    /**
     * Show the form for creating a new order item.
     */
    public function create($id = null)
    {
        if ($id) {
            $model = $this->modelClass::find($id);
            if (!$model) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $model = new $this->modelClass;
        }

        return $this->view('backend.order_items.create', [
            'page_title' => wncms_model_word('order_item', 'create'),
            'orders' => wncms()->getModelClass('order')::all(),
            'itemTypes' => $this->getItemTypes(), // Retrieve all possible item types
        ]);
    }

    /**
     * Store a newly created order item in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_itemable_type' => 'required|string|in:' . implode(',', $this->getItemTypes()),
            'order_itemable_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
        ]);

        $orderItem = $this->modelClass::create($validated);

        return redirect()->route('order_items.index')->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified order item.
     */
    public function edit($id)
    {
        $orderItem = $this->modelClass::find($id);
        if (!$orderItem) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('backend.order_items.edit', [
            'page_title' => wncms_model_word('order_item', 'edit'),
            'orderItem' => $orderItem,
            'orders' => wncms()->getModelClass('order')::all(),
            'itemTypes' => $this->getItemTypes(), // Retrieve all possible item types
        ]);
    }

    /**
     * Update the specified order item in storage.
     */
    public function update(Request $request, $id)
    {
        $orderItem = $this->modelClass::find($id);
        if (!$orderItem) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_itemable_type' => 'required|string|in:' . implode(',', $this->getItemTypes()),
            'order_itemable_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'amount' => 'required|numeric|min:0',
        ]);

        $orderItem->update($validated);

        return redirect()->route('order_items.index')->withMessage(__('wncms::word.successfully_updated'));
    }

    /**
     * Retrieve all possible item types for the order items.
     */
    private function getItemTypes(): array
    {
        // Add all morphable models here.
        return [
            'Wncms\Models\Product',
            'Wncms\Models\Subscription',
            'Wncms\Models\Price',
        ];
    }
}
