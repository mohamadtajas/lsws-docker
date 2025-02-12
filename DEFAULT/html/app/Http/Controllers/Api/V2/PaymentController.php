<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Wishlist\ProcessPaymentRequest;

class PaymentController extends Controller
{
    public function cashOnDelivery(ProcessPaymentRequest $request)
    {
        $order = new OrderController;
        return $order->store($request);
    }

    public function manualPayment(ProcessPaymentRequest $request)
    {
        $order = new OrderController;
        return $order->store($request);
    }
}
