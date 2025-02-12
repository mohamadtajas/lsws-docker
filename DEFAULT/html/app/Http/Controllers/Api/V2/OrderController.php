<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Address;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\BusinessSetting;
use App\Models\User;
use DB;
use \App\Utility\NotificationUtility;
use App\Models\CombinedOrder;
use App\Http\Controllers\AffiliateController;
use App\Http\Requests\Wishlist\ProcessPaymentRequest;
use App\Models\Provider;
use Auth;

class OrderController extends Controller
{

    public function store(ProcessPaymentRequest $request, $set_paid = false)
    {
        $accaessToken = trendyol_account_login();
        $providers = [];
        $providers_products = [];
        $providers = Cart::whereNotNull('provider_id')
            ->select('provider_id', 'id', 'product_id')
            ->get()
            ->groupBy('provider_id')
            ->map(function ($items) {
                return $items->pluck('product_id', 'id');
            })
            ->toArray();

        if (count($providers) > 0) {
            foreach ($providers as $provider => $products) {
                $provider = Provider::find($provider);
                if ($provider) {
                    $providers_products += $provider->service()->productsDetails($products);
                }
            }
        }
        if (get_setting('minimum_order_amount_check') == 1) {
            $subtotal = 0;
            foreach (Cart::where('user_id', auth()->user()->id)->get() as $key => $cartItem) {
                if ($cartItem['trendyol'] == 1) {
                    $product = trendyol_product_details($accaessToken, $cartItem['product_id'], $cartItem['urunNo']);
                    $subtotal += (float) str_replace(',', '', $product['new_price']) * $cartItem['quantity'];
                } elseif ($cartItem['trendyol'] ==  0 && $cartItem['provider_id'] == null) {
                    $product = Product::find($cartItem['product_id']);
                    $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                } elseif ($cartItem['provider_id'] != null) {
                    $product = $providers_products[$cartItem['id']];
                    $subtotal += (float) str_replace(',', '', $product['new_price']) * $cartItem['quantity'];
                }
            }
            if ($subtotal < get_setting('minimum_order_amount')) {
                return $this->failed(translate("You order amount is less then the minimum order amount") . ' ' . get_setting('minimum_order_amount'));
            }
        }


        $cartItems = Cart::where('user_id', auth()->user()->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'combined_order_id' => 0,
                'result' => false,
                'message' => translate('Cart is Empty')
            ]);
        }

        $user = User::find(auth()->user()->id);


        $address = Address::where('id', $cartItems->first()->address_id)->first();

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
            return response()->json(['result' => false, 'message' => translate("Please Edit shipping address and add all required fields")]);
        }

        $shippingAddress = [];
        if ($address != null) {
            $shippingAddress['name']        = $address->first_name . ' ' . $address->last_name;
            $shippingAddress['id_number']   = $address->id_number;
            $shippingAddress['email']       = $user->email;
            $shippingAddress['address']     = $address->address;
            $shippingAddress['country']     = $address->country->name;
            $shippingAddress['state']       = $address->state->name;
            $shippingAddress['city']        = $address->city->name;
            $shippingAddress['postal_code'] = $address->postal_code;
            $shippingAddress['phone']       = $address->phone;
            if ($address->latitude || $address->longitude) {
                $shippingAddress['lat_lang'] = $address->latitude . ',' . $address->longitude;
            }
        }

        $combined_order = new CombinedOrder;
        $combined_order->user_id = $user->id;
        $combined_order->shipping_address = json_encode($shippingAddress);
        $combined_order->save();

        $seller_products = array();
        foreach ($cartItems as $cartItem) {
            $product_ids = array();
            if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                $product = Product::find($cartItem['product_id']);
            } elseif ($cartItem['trendyol'] == 1) {
                $product = new Product(trendyol_product_details(trendyol_account_login(), $cartItem['product_id'], $cartItem['urunNo']));
            } elseif ($cartItem['provider_id'] != null) {
                $product = $providers_products[$cartItem['id']];
            }

            if (isset($seller_products[$product['user_id']])) {
                $product_ids = $seller_products[$product->user_id];
            }
            array_push($product_ids, $cartItem);
            $seller_products[$product['user_id']] = $product_ids;
        }

        foreach ($seller_products as $seller_product) {
            $order = new Order;
            $order->combined_order_id = $combined_order->id;
            $order->user_id = $user->id;
            $order->shipping_address = $combined_order->shipping_address;

            // $order->shipping_type = $cartItems->first()->shipping_type;
            // if ($cartItems->first()->shipping_type == 'pickup_point') {
            //     $order->pickup_point_id = $cartItems->first()->pickup_point;
            // }

            $order->order_from = 'app';
            $order->payment_type = $request->payment_type;
            $order->delivery_viewed = '0';
            $order->payment_status_viewed = '0';
            $order->code = date('Ymd-His') . rand(10, 99);
            $order->date = strtotime('now');
            if ($set_paid) {
                $order->payment_status = 'paid';
            } else {
                $order->payment_status = 'unpaid';
            }

            $order->save();

            $subtotal = 0;
            $subtotal_trendyol = 0;
            $tax = 0;
            $shipping = 0;
            $coupon_discount = 0;

            //Order Details Storing
            foreach ($seller_product as $cartItem) {
                $product_variation = $cartItem['variation'];
                if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    $product = Product::find($cartItem['product_id']);

                    $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                    $subtotal_trendyol += $cartItem['trendyol_price'] * $cartItem['quantity'];
                    $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                    $product_stock = $product->stocks->where('variant', $product_variation)->first();
                } elseif ($cartItem['trendyol'] == 1) {
                    $product = trendyol_product_details(trendyol_account_login(), $cartItem['product_id'], $cartItem['urunNo']);
                    $subtotal += floatval(str_replace(',', '', $product['new_price'])) * $cartItem['quantity'];
                    $subtotal_trendyol += floatval(str_replace(',', '', $product['base_price'])) * $cartItem['quantity'];
                    $tax +=  0;
                    $product_stock = $product['stock'];
                } elseif ($cartItem['provider_id'] != null) {
                    $product = $providers_products[$cartItem['id']];
                    $subtotal += floatval(str_replace(',', '', $product['new_price'])) * $cartItem['quantity'];
                    $subtotal_trendyol += floatval(str_replace(',', '', $product['base_price'])) * $cartItem['quantity'];
                    $tax +=  0;
                    $product_stock = $product['stock'];
                }

                $coupon_discount += $cartItem['discount'];



                if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    if ($product->digital != 1 && $cartItem['quantity'] > $product_stock->qty) {
                        $order->delete();
                        $combined_order->delete();
                        return response()->json([
                            'combined_order_id' => 0,
                            'result' => false,
                            'message' => translate('The requested quantity is not available for ') . $product->name
                        ]);
                    } elseif ($product->digital != 1) {
                        $product_stock->qty -= $cartItem['quantity'];
                        $product_stock->save();
                    }
                } elseif ($cartItem['trendyol'] == 1) {
                    if ($product['digital'] != 1 && $cartItem['quantity'] > $product_stock) {
                        $order->delete();
                        $combined_order->delete();
                        return response()->json([
                            'combined_order_id' => 0,
                            'result' => false,
                            'message' => translate('The requested quantity is not available for ') . $product->name
                        ]);
                    }
                } elseif ($cartItem['provider_id'] != null) {
                    if ($product['digital'] != 1 && $cartItem['quantity'] > $product_stock) {
                        $order->delete();
                        $combined_order->delete();
                        return response()->json([
                            'combined_order_id' => 0,
                            'result' => false,
                            'message' => translate('The requested quantity is not available for ') . $product->name
                        ]);
                    }
                }

                if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    $order_detail = new OrderDetail;
                    $order_detail->order_id = $order->id;
                    $order_detail->seller_id = $product->user_id;
                    $order_detail->product_id = $product->id;
                    $order_detail->variation = $product_variation;
                    $order_detail->price = cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                    $order_detail->tax = cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                    $order_detail->shipping_type = $cartItem['shipping_type'];
                    $order_detail->product_referral_code = $cartItem['product_referral_code'];
                    $order_detail->shipping_cost = $cartItem['shipping_cost'];
                } elseif ($cartItem['trendyol'] == 1) {
                    $order_detail = new OrderDetail;
                    $order_detail->order_id = $order->id;
                    $order_detail->seller_id = $product['user_id'];
                    $order_detail->product_id = $product['id'];
                    $order_detail->variation = $product_variation;
                    $order_detail->price = trendyol_product_indirimliFiyat(trendyol_account_login(), $cartItem['product_id'], $cartItem['urunNo']) * $cartItem['quantity'];
                    $order_detail->tax = 0;
                    $order_detail->shipping_type = $cartItem['shipping_type'];
                    $order_detail->product_referral_code = $cartItem['product_referral_code'];
                    $order_detail->shipping_cost = $cartItem['shipping_cost'];
                    $order_detail->trendyol = 1;
                    $order_detail->urunNo = $cartItem['urunNo'];
                } elseif ($cartItem['provider_id'] != null) {
                    $order_detail = new OrderDetail;
                    $order_detail->order_id = $order->id;
                    $order_detail->seller_id = $product['user_id'];
                    $order_detail->product_id = $product['id'];
                    $order_detail->variation = $product_variation;
                    $order_detail->price = $product['new_price'] * $cartItem['quantity'];
                    $order_detail->tax = 0;
                    $order_detail->shipping_type = $cartItem['shipping_type'];
                    $order_detail->product_referral_code = $cartItem['product_referral_code'];
                    $order_detail->shipping_cost = $cartItem['shipping_cost'];
                    $order_detail->provider_id = $cartItem['provider_id'];
                    $order_detail->product_image = $product['thumbnail'] ?? null;
                    $order_detail->product_name = $product['name'];
                    $order_detail->category_name = $product['categoryName'] ?? null;
                    $order_detail->digital = $product['digital'] ?? 0;
                }


                $shipping += $order_detail->shipping_cost;

                // if ($cartItem['shipping_type'] == 'pickup_point') {
                //     $order_detail->pickup_point_id = $cartItem['pickup_point'];
                // }
                //End of storing shipping cost
                if (addon_is_activated('club_point') && $cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    $order_detail->earn_point = $product->earn_point;
                }

                $order_detail->quantity = $cartItem['quantity'];
                $order_detail->save();
                if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    $product->num_of_sale = $product->num_of_sale + $cartItem['quantity'];
                    $product->save();
                    $order->seller_id = $product->user_id;
                    $order->shipping_type = $cartItem['shipping_type'];
                } elseif ($cartItem['trendyol'] == 1) {
                    $order->seller_id = $product['user_id'];
                    $order->shipping_type = $cartItem['shipping_type'];
                }elseif ($cartItem['provider_id'] != null) {
                    $order->seller_id = $product['user_id'];
                    $order->shipping_type = $cartItem['shipping_type'];
                }

                if ($cartItem['shipping_type'] == 'pickup_point') {
                    $order->pickup_point_id = $cartItem['pickup_point'];
                }
                if ($cartItem['shipping_type'] == 'carrier') {
                    $order->carrier_id = $cartItem['carrier_id'];
                }

                if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    if ($product->added_by == 'seller' && $product->user->seller != null) {
                        $seller = $product->user->seller;
                        $seller->num_of_sale += $cartItem['quantity'];
                        $seller->save();
                    }
                }


                if (addon_is_activated('affiliate_system')) {
                    if ($order_detail->product_referral_code) {
                        $referred_by_user = User::where('referral_code', $order_detail->product_referral_code)->first();

                        $affiliateController = new AffiliateController;
                        $affiliateController->processAffiliateStats($referred_by_user->id, 0, $order_detail->quantity, 0, 0);
                    }
                }
            }

            $order->grand_total = $subtotal + $tax + $shipping;
            $order->grand_total_trendyol = $subtotal_trendyol + $shipping;

            if ($seller_product[0]->coupon_code != null) {
                // if (Session::has('club_point')) {
                //     $order->club_point = Session::get('club_point');
                // }
                $order->coupon_discount = $coupon_discount;
                $order->grand_total -= $coupon_discount;

                $coupon_usage = new CouponUsage;
                $coupon_usage->user_id = $user->id;
                $coupon_usage->coupon_id = Coupon::where('code', $seller_product[0]->coupon_code)->first()->id;
                $coupon_usage->save();
            }

            $combined_order->grand_total += $order->grand_total;

            if (strpos($request->payment_type, "manual_payment_") !== false) { // if payment type like  manual_payment_1 or  manual_payment_25 etc)

                $order->manual_payment = 1;
                $order->save();
            }

            $order->save();
        }
        $combined_order->save();



        Cart::where('user_id', auth()->user()->id)->delete();

        if (
            $request->payment_type == 'cash_on_delivery'
            || $request->payment_type == 'wallet'
            || strpos($request->payment_type, "manual_payment_") !== false // if payment type like  manual_payment_1 or  manual_payment_25 etc
        ) {
            NotificationUtility::sendOrderPlacedNotification($order);
        }


        return response()->json([
            'combined_order_id' => $combined_order->id,
            'result' => true,
            'message' => translate('Your order has been placed successfully')
        ]);
    }

    public function order_cancel(string $id)
    {
        $order = Order::where('id', $id)->where('user_id', auth()->user()->id)->first();
        if ($order && ($order->delivery_status == 'pending' && $order->payment_status == 'unpaid')) {
            $order->delivery_status = 'cancelled';
            $order->save();


            foreach ($order->orderDetails as $key => $orderDetail) {
                $orderDetail->delivery_status = 'cancelled';
                $orderDetail->save();
                if ($orderDetail->trendyol == 0) {
                    product_restock($orderDetail);
                }
            }

            return $this->success(translate('Order has been canceled successfully'));
        } else {
            return  $this->failed(translate('Something went wrong'));
        }
    }
}
