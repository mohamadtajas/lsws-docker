<?php

namespace App\Http\Controllers\Seller;

use App\Http\Requests\Order\IndexRequest;
use App\Http\Requests\Order\InvoiceNumberRequest;
use App\Http\Requests\Order\UpdateDeliveryStatusRequest;
use App\Http\Requests\Order\UpdateDetailsDeliveryStatusRequest;
use App\Http\Requests\Order\UpdatePaymentStatusRequest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductStock;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Utility\NotificationUtility;
use App\Utility\SmsUtility;
use Auth;
use DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource to seller.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexRequest $request)
    {
        $payment_status = null;
        $delivery_status = null;
        $sort_search = null;
        $orders = DB::table('orders')
            ->orderBy('id', 'desc')
            ->where('seller_id', Auth::user()->id)
            ->select('orders.id')
            ->distinct();

        if ($request->payment_status != null) {
            $orders = $orders->where('payment_status', $request->payment_status);
            $payment_status = $request->payment_status;
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }

        $orders = $orders->paginate(15);

        foreach ($orders as $key => $value) {
            $order = Order::find($value->id);
            $order->viewed = 1;
            $order->save();
        }

        return view('seller.orders.index', compact('orders', 'payment_status', 'delivery_status', 'sort_search'));
    }

    public function show(string $id)
    {
        $order = Order::findOrFail(decrypt($id));
        $order_shipping_address = json_decode($order->shipping_address);
        $delivery_boys = User::where('city', $order_shipping_address->city)
            ->where('user_type', 'delivery_boy')
            ->get();

        $order->viewed = 1;
        $order->save();
        return view('seller.orders.show', compact('order', 'delivery_boys'));
    }

    // Update Delivery Status
    public function update_delivery_status(UpdateDeliveryStatusRequest $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->delivery_viewed = '0';

        if ($order->delivery_status != 'cancelled' && $order->delivery_status != 'delivered') {
            $order->delivery_status = $request->status;
            $order->save();
        }

        if ($request->status == 'cancelled' && $order->payment_type == 'wallet' && $order->payment_status == 'paid') {
            $user = User::where('id', $order->user_id)->first();
            foreach ($order->orderDetails as $orderDetail) {
                if ($orderDetail->delivery_status != 'cancelled' && $orderDetail->delivery_status != 'delivered') {
                    $user->balance += ($orderDetail->price + $orderDetail->tax + $orderDetail->shipping_cost);
                    $user->save();
                }
                $seller = $orderDetail->seller;
                if ($seller && $seller->shop) {
                    $shop = $seller->shop;
                    $shop->admin_to_pay = $shop->admin_to_pay - $orderDetail->price;
                    $shop->save();
                }
            }
        }


        foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
            $orderDetail->delivery_status = $request->status;
            $orderDetail->save();

            if ($request->status == 'cancelled') {
                $variant = $orderDetail->variation;
                if ($orderDetail->variation == null) {
                    $variant = '';
                }

                $product_stock = ProductStock::where('product_id', $orderDetail->product_id)
                    ->where('variant', $variant)
                    ->first();

                if ($product_stock != null) {
                    $product_stock->qty += $orderDetail->quantity;
                    $product_stock->save();
                }
            }
        }

        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'delivery_status_change')->first()->status == 1) {
            try {
                SmsUtility::delivery_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {
            }
        }

        //sends Notifications to user
        NotificationUtility::sendNotification($order, $request->status);
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $status = str_replace("_", "", $order->delivery_status);
            $request->text = " Your order {$order->code} has been {$status}";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;
            $request->url = route('purchase_history.details', encrypt($order->id));
            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('delivery_boy')) {
            if (Auth::user()->user_type == 'delivery_boy') {
                $deliveryBoyController = new DeliveryBoyController;
                $deliveryBoyController->store_delivery_history($order);
            }
        }

        return 1;
    }

    public function update_order_detail_delivery_status(UpdateDetailsDeliveryStatusRequest $request)
    {

        $order_detail = OrderDetail::findOrFail($request->order_detail_id);
        $order = $order_detail->order;

        if ($request->status == 'cancelled' && $order->payment_type == 'wallet' && $order_detail->payment_status == 'paid') {
            if ($order_detail->delivery_status != 'cancelled' && $order_detail->delivery_status != 'delivered') {
                $user = User::where('id', $order->user_id)->first();
                $user->balance += ($order_detail->price + $order_detail->tax + $order_detail->shipping_cost);
                $user->save();
            }
            $seller = $order_detail->seller;
            if ($seller && $seller->shop) {
                $shop = $seller->shop;
                $shop->admin_to_pay = $shop->admin_to_pay - $order_detail->price;
                $shop->save();
            }
        }

        $order_detail->delivery_status = $request->status;
        $order_detail->save();

        if (Auth::user()->user_type == 'seller') {
            if ($request->status == 'cancelled') {
                $variant = $order_detail->variation;
                if ($order_detail->variation == null) {
                    $variant = '';
                }

                $product_stock = ProductStock::where('product_id', $order_detail->product_id)
                    ->where('variant', $variant)
                    ->first();

                if ($product_stock != null) {
                    $product_stock->qty += $order_detail->quantity;
                    $product_stock->save();
                }
            }
        } else {
            if ($request->status == 'cancelled') {
                $variant = $order_detail->variation;
                if ($order_detail->variation == null) {
                    $variant = '';
                }

                $product_stock = ProductStock::where('product_id', $order_detail->product_id)
                    ->where('variant', $variant)
                    ->first();

                if ($product_stock != null) {
                    $product_stock->qty += $order_detail->quantity;
                    $product_stock->save();
                }
            }

            if (addon_is_activated('affiliate_system')) {
                if (($request->status == 'delivered' || $request->status == 'cancelled') &&
                    $order_detail->product_referral_code
                ) {

                    $no_of_delivered = 0;
                    $no_of_canceled = 0;

                    if ($request->status == 'delivered') {
                        $no_of_delivered = $order_detail->quantity;
                    }
                    if ($request->status == 'cancelled') {
                        $no_of_canceled = $order_detail->quantity;
                    }

                    $referred_by_user = User::where('referral_code', $order_detail->product_referral_code)->first();

                    $affiliateController = new AffiliateController;
                    $affiliateController->processAffiliateStats($referred_by_user->id, 0, 0, $no_of_delivered, $no_of_canceled);
                }
            }
        }
        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'delivery_status_change')->first()->status == 1) {
            try {
                SmsUtility::delivery_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {
            }
        }

        //sends Notifications to user
        NotificationUtility::sendNotification($order, $request->status);
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $status = str_replace("_", "", $order_detail->delivery_status);
            $request->text = " Your order {$order->code} has been {$status}";

            $request->type = "order";
            $request->id = $order_detail->id;
            $request->user_id = $order->user->id;
            $request->url = route('purchase_history.details', encrypt($order->id));
            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('delivery_boy')) {
            if (Auth::user()->user_type == 'delivery_boy') {
                $deliveryBoyController = new DeliveryBoyController;
                $deliveryBoyController->store_delivery_history($order);
            }
        }

        return 1;
    }

    // Update Payment Status
    public function update_payment_status(UpdatePaymentStatusRequest $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->payment_status_viewed = '0';
        $order->save();

        foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
            $orderDetail->payment_status = $request->status;
            $orderDetail->save();
        }

        $status = 'paid';
        foreach ($order->orderDetails as $key => $orderDetail) {
            if ($orderDetail->payment_status != 'paid') {
                $status = 'unpaid';
            }
        }
        $order->payment_status = $status;
        $order->save();


        if ($order->payment_status == 'paid' && $order->commission_calculated == 0) {
            calculateCommissionAffilationClubPoint($order);
        }

        //sends Notifications to user
        NotificationUtility::sendNotification($order, $request->status);
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = translate("Order updated !");
            $status = str_replace("_", "", $order->payment_status);
            $request->text = translate("Your order") . " " . $order->code . " " . translate("has been") . " " . $status;

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;
            $request->url = route('purchase_history.details', encrypt($order->id));
            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'payment_status_change')->first()->status == 1) {
            try {
                SmsUtility::payment_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {
            }
        }
        return 1;
    }

    public function edit_invoice_number(InvoiceNumberRequest $request, $orderDetail)
    {
        $orderDetail = OrderDetail::find($orderDetail);
        if (!$orderDetail) {
            flash(translate('Something went wrong'))->error();
            return back();
        }
        $orderDetail->invoice_number = $request->invoice_number;
        $orderDetail->save();
        flash(translate('Invoice number updated'))->success();
        return back();
    }

    public function orders_details(IndexRequest $request)
    {
        $payment_status = null;
        $delivery_status = null;
        $sort_search = null;
        $ordersDetails = OrderDetail::orderBy('id', 'desc')
            ->where('seller_id', Auth::user()->id);

        if ($request->payment_status != null) {
            $ordersDetails = $ordersDetails->where('payment_status', $request->payment_status);
            $payment_status = $request->payment_status;
        }
        if ($request->delivery_status != null) {
            $ordersDetails = $ordersDetails->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->has('search')) {
            $sort_search = $request->search;
            $ordersDetails = $ordersDetails->whereHas('order', function ($query) use ($sort_search) {
                $query->where('code', 'like', '%' . $sort_search . '%');
            });
        }

        $ordersDetails = $ordersDetails->paginate(15);

        return view('seller.orders.details', compact('ordersDetails', 'payment_status', 'delivery_status', 'sort_search'));
    }
}
