<?php

namespace Wncms\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Wncms\Facades\OrderManager;
use Wncms\Facades\Wncms;
use Wncms\Models\Order;
use Wncms\Models\PaymentGateway;

class OrderController extends FrontendController
{
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())->get();

        return Wncms::view(
            name: "frontend.theme.{$this->theme}.orders.index",
            params: [
                'orders' => $orders,
            ],
            fallback: 'wncms::frontend.theme.default.orders.index',
        );
    }
    
    public function show(Order $order)
    {
        // check if order belongs to user
        if ($order->user_id != auth()->id()) {
            return redirect()->back()->with('error', 'Order not found');
        }

        // get payment gateways
        $paymentGateways = PaymentGateway::where('status', 'active')->get();

        // load from database
        return Wncms::view(
            name: "frontend.theme.{$this->theme}.orders.show",
            params: [
                'order' => $order,
                'paymentGateways' => $paymentGateways,
            ],
            fallback: 'wncms::frontend.theme.default.orders.show',
        );
    }

    public function pay(Request $request, Order $order)
    {
        // find the payment gateway
        $paymentGateway = PaymentGateway::where('status', 'active')->where('slug', $request->payment_gateway)->first();

        // call the process method of the payment gateway
        if ($paymentGateway) {
            return $paymentGateway->processor()->process($order->id);
        }

        dd('Payment gateway not found');
        // return response
    }

    public function success(Request $request, Order $order)
    {
        // dd($request->all(), $order);

        // find the payment gateway

        // send webhook response to provider if needed

        // redirect to success page
        return Wncms::view(
            name: "frontend.theme.{$this->theme}.orders.success",
            params: [
                'order' => $order,
            ],
            fallback: 'wncms::frontend.theme.default.orders.success',
        );
    }
}
