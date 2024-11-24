<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Wncms\Models\PaymentGateway;

class PaymentGatewayController extends Controller
{
    public function notify(Request $request, $payment_gateway_slug = null)
    {
        info($request->all());

        // find the gateway
        $paymentGateway = PaymentGateway::where('status', 'active')->where('slug', $request->payment_gateway)->first();

        // find payment gateway class
        if (!$paymentGateway) {
            return response()->json(['error' => 'Payment gateway not found'], 404);
        }

        if (!$paymentGateway->processor()) {
            info("Process class of {$paymentGateway->slug} is not found");
            return response()->json(['error' => "Process class of {$paymentGateway->slug} is not found"], 404);
        }

        // call notify method of the gateway class (where order is processed)
        $result = $paymentGateway->processor()->notify($request);

        // TODO: check response type
        // if($paymentGateway->response_type == 'json'){
        //     return response()->json(['status' => 'success']);
        // }

        // return result to provider
        return $result;
    }
}