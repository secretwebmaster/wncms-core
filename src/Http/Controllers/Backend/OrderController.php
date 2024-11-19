<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Order;
use Wncms\Models\User;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders.
     */
    public function index(Request $request)
    {
        $q = Order::query();

        // Filters
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $orders = $q->paginate($request->page_size ?? 100)->withQueryString();

        return view('wncms::backend.orders.index', [
            'page_title' => wncms_model_word('orders', 'management'),
            'orders' => $orders,
            'users' => User::all(),
        ]);
    }

    /**
     * Show the form for creating a new order.
     */
    public function create()
    {
        return view('wncms::backend.orders.create', [
            'page_title' => wncms_model_word('orders', 'create'),
            'users' => User::all(),
        ]);
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string|in:pending,paid,failed,cancelled,completed',
            'total_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:255',
        ]);

        $order = Order::create(array_merge($validated, [
            'slug' => uniqid('order_'), // Generate a unique slug
        ]));

        wncms()->cache()->flush(['orders']);

        return redirect()->route('orders.edit', $order)
            ->withMessage(__('wncms::word.successfully_created'));
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order)
    {
        return view('wncms::backend.orders.edit', [
            'page_title' => wncms_model_word('orders', 'edit'),
            'order' => $order,
            'users' => User::all(),
        ]);
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string|in:pending,paid,failed,cancelled,completed',
            'total_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:255',
        ]);

        $order->update($validated);

        wncms()->cache()->flush(['orders']);

        return redirect()->route('orders.edit', $order)
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();

        wncms()->cache()->flush(['orders']);

        return redirect()->route('orders.index')
            ->withMessage(__('wncms::word.successfully_deleted'));
    }

    /**
     * Bulk delete orders.
     */
    public function bulk_delete(Request $request)
    {
        $modelIds = is_array($request->model_ids)
            ? $request->model_ids
            : explode(",", $request->model_ids);

        $count = Order::whereIn('id', $modelIds)->delete();

        wncms()->cache()->flush(['orders']);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('orders.index')
            ->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
