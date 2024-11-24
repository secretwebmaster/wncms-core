<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\OrderItem;
use Wncms\Models\Order;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the order items.
     */
    public function index(Request $request)
    {
        $q = OrderItem::query();

        // Filters
        if ($request->filled('order_id')) {
            $q->where('order_id', $request->order_id);
        }

        if ($request->filled('order_itemable_type')) {
            $q->where('order_itemable_type', $request->order_itemable_type);
        }

        if ($request->filled('order_itemable_id')) {
            $q->where('order_itemable_id', $request->order_itemable_id);
        }

        $orderItems = $q->paginate($request->page_size ?? 100)->withQueryString();

        return view('wncms::backend.order_items.index', [
            'page_title' => wncms_model_word('order_item', 'management'),
            'orderItems' => $orderItems,
            'itemTypes' => $this->getItemTypes(), // Retrieve all possible item types
        ]);
    }

    /**
     * Show the form for creating a new order item.
     */
    public function create()
    {
        return view('wncms::backend.order_items.create', [
            'page_title' => wncms_model_word('order_item', 'create'),
            'orders' => Order::all(),
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

        $orderItem = OrderItem::create($validated);

        return redirect()->route('order_items.index')
            ->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified order item.
     */
    public function edit(OrderItem $orderItem)
    {
        return view('wncms::backend.order_items.edit', [
            'page_title' => wncms_model_word('order_item', 'edit'),
            'orderItem' => $orderItem,
            'orders' => Order::all(),
            'itemTypes' => $this->getItemTypes(), // Retrieve all possible item types
        ]);
    }

    /**
     * Update the specified order item in storage.
     */
    public function update(Request $request, OrderItem $orderItem)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_itemable_type' => 'required|string|in:' . implode(',', $this->getItemTypes()),
            'order_itemable_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
        ]);

        $orderItem->update($validated);

        return redirect()->route('order_items.index')
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    /**
     * Remove the specified order item from storage.
     */
    public function destroy(OrderItem $orderItem)
    {
        $orderItem->delete();

        return redirect()->route('order_items.index')
            ->withMessage(__('wncms::word.successfully_deleted'));
    }

    /**
     * Bulk delete order items.
     */
    public function bulk_delete(Request $request)
    {
        $modelIds = is_array($request->model_ids)
            ? $request->model_ids
            : explode(',', $request->model_ids);

        $count = OrderItem::whereIn('id', $modelIds)->delete();

        return redirect()->route('order_items.index')
            ->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
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
        ];
    }
}
