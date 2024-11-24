<?php

namespace Wncms\PaymentGateways;

use Wncms\Exceptions\PaymentGatewayException;
use Wncms\Models\Order;
use Wncms\Models\PaymentGateway;

abstract class BasePaymentGateway
{
    protected PaymentGateway $paymentGateway;
    
    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function checkOrder($orderId)
    {
        // find order
        if($orderId instanceof Order){
            $order = $orderId;
        }else{
            $order = Order::find($orderId);
        }

        // check order status
        if($order->status != 'pending_payment'){
            throw new PaymentGatewayException('Order is not pending payment');
        }

        return $order;
    }

    public function load($paymentGatewayId)
    {
        // find payment gateway
        $paymentGateway = PaymentGateway::where('slug', $paymentGatewayId)->first();
        if(!$paymentGateway){
            throw new PaymentGatewayException('Payment gateway not found');
        }

        return $paymentGateway;
    }
}