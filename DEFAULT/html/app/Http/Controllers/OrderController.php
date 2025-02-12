<?php

namespace App\Http\Controllers;

use App\Exports\OrdersDetailsExport;
use App\Http\Controllers\AffiliateController;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Address;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\OrderDetail;
use App\Models\CouponUsage;
use App\Models\Coupon;
use App\Models\User;
use App\Models\CombinedOrder;
use App\Models\SmsTemplate;
use App\Models\TrendyolOrder;
use Auth;
use Mail;
use App\Mail\InvoiceEmailManager;
use App\Utility\NotificationUtility;
use CoreComponentRepository;
use App\Utility\SmsUtility;
use Illuminate\Support\Facades\Route;
use App\Exports\OrdersExport;
use App\Http\Requests\CheckOut\CheckoutRequest;
use App\Http\Requests\Order\AssignDeliveryBoyRequest;
use App\Http\Requests\Order\IdsRequest;
use App\Http\Requests\Order\IndexRequest;
use App\Http\Requests\Order\InvoiceNumberRequest;
use App\Http\Requests\Order\OrderDetailsRequest;
use App\Http\Requests\Order\OrderIdRequest;
use App\Http\Requests\Order\TrendyolUpdateBulkOrderNumberRequest;
use App\Http\Requests\Order\TrendyolUpdateOrderNumberRequest;
use App\Http\Requests\Order\UpdateDeliveryStatusRequest;
use App\Http\Requests\Order\UpdateDetailsDeliveryStatusRequest;
use App\Http\Requests\Order\UpdatePaymentStatusRequest;
use App\Http\Requests\Order\UpdateTrackingCodeRequest;
use App\Models\Provider;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{

    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_all_orders|view_inhouse_orders|view_seller_orders|view_pickup_point_orders'])->only('all_orders');
        $this->middleware(['permission:view_order_details'])->only('show');
        $this->middleware(['permission:orders_report'])->only('orders_report');
        $this->middleware(['permission:delete_order'])->only('destroy', 'bulk_order_delete');
        $this->middleware(['permission:trendyol_manual_order'])->only('trendyol_manual_order');
        $this->middleware(['permission:trendyol_manual_order'])->only('trendyol_update_order_number');
        $this->middleware(['permission:trendyol_manual_order'])->only('bulk_trendyol_manual_order');
        $this->middleware(['permission:trendyol_manual_order'])->only('bulk_trendyol_update_order_number');
    }

    // All Orders
    public function all_orders(IndexRequest $request)
    {
        // CoreComponentRepository::instantiateShopRepository();

        $date = $request->date;
        $sort_search = null;
        $delivery_status = null;
        $payment_status = '';
        $customer = null;

        $orders = Order::orderBy('id', 'desc');
        $admin_user_id = User::where('user_type', 'admin')->first()->id;


        if (
            Route::currentRouteName() == 'inhouse_orders.index' &&
            Auth::user()->can('view_inhouse_orders')
        ) {
            $orders = $orders->where('orders.seller_id', '=', $admin_user_id);
        } else if (
            Route::currentRouteName() == 'seller_orders.index' &&
            Auth::user()->can('view_seller_orders')
        ) {
            $orders = $orders->where('orders.seller_id', '!=', $admin_user_id);
        } else if (
            Route::currentRouteName() == 'pick_up_point.index' &&
            Auth::user()->can('view_pickup_point_orders')
        ) {
            if (get_setting('vendor_system_activation') != 1) {
                $orders = $orders->where('orders.seller_id', '=', $admin_user_id);
            }
            $orders->where('shipping_type', 'pickup_point')->orderBy('code', 'desc');
            if (
                Auth::user()->user_type == 'staff' &&
                Auth::user()->staff->pick_up_point != null
            ) {
                $orders->where('shipping_type', 'pickup_point')
                    ->where('pickup_point_id', Auth::user()->staff->pick_up_point->id);
            }
        } else if (
            Route::currentRouteName() == 'all_orders.index' &&
            Auth::user()->can('view_all_orders')
        ) {
            if (get_setting('vendor_system_activation') != 1) {
                $orders = $orders->where('orders.seller_id', '=', $admin_user_id);
            }
        } else {
            abort(403);
        }

        if ($request->search) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
            $orders = $orders->orWhereHas('trendyol_order', function ($query) use ($sort_search) {
                $query->where('trendyol_orderNumber', 'like', '%' . $sort_search . '%');
            });
        }

        if ($request->payment_status != null) {
            $orders = $orders->where('payment_status', $request->payment_status);
            $payment_status = $request->payment_status;
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }

        if ($request->customer) {
            $customer = User::where('user_type', 'customer')
                ->where(function ($query) use ($request) {
                    $query->where('name', 'LIKE', '%' . $request->customer . '%')
                        ->orWhere('email', 'LIKE', '%' . $request->customer . '%');
                })->first();
            if ($customer) {
                $orders = $orders->where('user_id', $customer->id);
            }
            $customer = $request->customer;
        }

        if ($date != null) {
            $orders = $orders->where('created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])) . '  00:00:00')
                ->where('created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])) . '  23:59:59');
        }

        if ($request->has('export') && $request->export == 'excel') {
            $export = new OrdersExport($orders);
            return Excel::download($export, 'orders.xlsx');
        }

        $orders = $orders->paginate(25);
        return view('backend.sales.index', compact('orders', 'sort_search', 'payment_status', 'delivery_status', 'date', 'customer'));
    }

    public function orders_details(OrderDetailsRequest $request)
    {
        $date = $request->date;
        $sort_search = null;
        $delivery_status = null;
        $payment_status = '';
        $product_name = '';
        $invoice_number = '';
        $invoice_option = null;
        $category_name = '';

        //filter by seller info
        $seller_name = '';
        $seller_email = '';
        $seller_tax_number = '';

        $ordersDetails = OrderDetail::orderBy('id', 'desc');

        if ($request->search) {
            $sort_search = $request->search;
            $ordersDetails = $ordersDetails->whereHas('order', function ($query) use ($sort_search) {
                $query->where('code', 'like', '%' . $sort_search . '%');
            });
            $orderDetails = $ordersDetails->orWhereHas('trendyol_order', function ($query) use ($sort_search) {
                $query->where('trendyol_orderNumber', 'like', '%' . $sort_search . '%');
            });
            $ordersDetails = $ordersDetails->orWhere('tracking_code', 'like', '%' . $sort_search . '%');
        }

        if ($request->payment_status != null) {
            $ordersDetails = $ordersDetails->where('payment_status', $request->payment_status);
            $payment_status = $request->payment_status;
        }

        if ($request->delivery_status != null) {
            $ordersDetails = $ordersDetails->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }

        if ($date != null) {
            $ordersDetails = $ordersDetails->where('created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])) . '  00:00:00')
                ->where('created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])) . '  23:59:59');
        }

        if ($request->product_name != null) {
            $product_name = $request->product_name;
            $ordersDetails = $ordersDetails->where('product_name', 'like', '%' . $product_name . '%');
        }

        if ($request->invoice_option != null) {
            $invoice_option = $request->invoice_option;
            if ($invoice_option == 'null') {
                $ordersDetails = $ordersDetails->whereNull('invoice_number');
            } elseif ($invoice_option == 'not_null') {
                $ordersDetails = $ordersDetails->whereNotNull('invoice_number');
            } elseif ($invoice_option == 'add_number') {
                $invoice_number = $request->invoice_number;
                $ordersDetails = $ordersDetails->where('invoice_number', 'like', '%' . $invoice_number . '%');
            }
        }

        if ($request->category_name != null) {
            $category_name = $request->category_name;
            $ordersDetails = $ordersDetails->where('category_name', 'like', '%' . $category_name . '%');
        }


        if ($request->seller_name != null) {
            $seller_name = $request->seller_name;
            $ordersDetails = $ordersDetails->whereHas('trendyolMerchant', function ($query) use ($seller_name) {
                $query->where('name', 'like', '%' . $seller_name . '%');
                $query->orWhere('official_name', 'like', '%' . $seller_name . '%');
            });
            $orderDetails = $ordersDetails->orWhereHas('order.shop', function ($query) use ($seller_name) {
                $query->where('name', 'like', '%' . $seller_name . '%');
            });
            $orderDetails = $ordersDetails->orWhereHas('order.shop.user', function ($query) use ($seller_name) {
                $query->where('name', 'like', '%' . $seller_name . '%');
            });
        }

        if ($request->seller_email != null) {
            $seller_email = $request->seller_email;
            $ordersDetails = $ordersDetails->whereHas('trendyolMerchant', function ($query) use ($seller_email) {
                $query->Where('email', 'like', '%' . $seller_email . '%');
            });
            $orderDetails = $ordersDetails->orWhereHas('order.shop.user', function ($query) use ($seller_email) {
                $query->where('email', 'like', '%' . $seller_email . '%');
            });
        }

        if ($request->seller_tax_number != null) {
            $seller_tax_number = $request->seller_tax_number;
            $ordersDetails = $ordersDetails->whereHas('trendyolMerchant', function ($query) use ($seller_tax_number) {
                $query->where('tax_number', 'like', '%' . $seller_tax_number . '%');
            });
        }


        if ($request->has('export') && $request->export == 'excel') {
            $export = new OrdersDetailsExport($ordersDetails);
            return Excel::download($export, 'ordersDetails.xlsx');
        }

        $ordersDetails = $ordersDetails->paginate(25);

        return view('backend.sales.order_details', compact(
            'ordersDetails',
            'sort_search',
            'payment_status',
            'delivery_status',
            'date',
            'product_name',
            'invoice_number',
            'invoice_option',
            'category_name',
            'seller_name',
            'seller_email',
            'seller_tax_number'
        ));
    }

    public function show(string $id)
    {
        $AlltrendyolDetails = [];
        $trendyolOrders = TrendyolOrder::where('order_id', decrypt($id))->where('trendyol_orderNumber', '>', 0)->groupBy('trendyol_orderNumber')->get();
        if (count($trendyolOrders) > 0) {
            foreach ($trendyolOrders as  $trendyolOrder) {
                if ($trendyolOrder->trendyol_orderNumber > 0) {
                    $AlltrendyolDetails[] = trendyol_order_details($trendyolOrder->trendyol_orderNumber);
                }
            }
        }
        $order = Order::findOrFail(decrypt($id));
        $order_shipping_address = json_decode($order->shipping_address);
        $delivery_boys = User::where('city', $order_shipping_address->city)
            ->where('user_type', 'delivery_boy')
            ->get();

        $order->viewed = 1;
        $order->save();
        return view('backend.sales.show', compact('order', 'delivery_boys', 'AlltrendyolDetails'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(CheckoutRequest $request)
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
        $address = Address::where('id', $carts[0]['address_id'])->first();

        $shippingAddress = [];
        if ($address != null) {
            $shippingAddress['name']        = $address->first_name . ' ' . $address->last_name;
            $shippingAddress['id_number']   = $address->id_number;
            $shippingAddress['email']       = Auth::user()->email;
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
        $combined_order->user_id = Auth::user()->id;
        $combined_order->shipping_address = json_encode($shippingAddress);
        $combined_order->save();
        $seller_products = array();
        foreach ($carts as $cartItem) {
            $product_ids = array();
            if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                $product = Product::find($cartItem['product_id']);
            } elseif ($cartItem['trendyol'] == 1) {
                $product = new Product(trendyol_product_details(trendyol_account_login(), $cartItem['product_id'], $cartItem['urunNo']));
            } elseif ($cartItem['provider_id'] != null) {
                $product = $providerProducts[$cartItem['id']];
            }
            if (isset($seller_products[$product['user_id']])) {
                $product_ids = $seller_products[$product['user_id']];
            }
            array_push($product_ids, $cartItem);
            $seller_products[$product['user_id']] = $product_ids;
        }

        foreach ($seller_products as $seller_product) {
            $order = new Order;
            $order->combined_order_id = $combined_order->id;
            $order->user_id = Auth::user()->id;
            $order->shipping_address = $combined_order->shipping_address;

            $order->additional_info = $request->additional_info;

            // $order->shipping_type = $carts[0]['shipping_type'];
            // if ($carts[0]['shipping_type'] == 'pickup_point') {
            //     $order->pickup_point_id = $cartItem['pickup_point'];
            // }
            // if ($carts[0]['shipping_type'] == 'carrier') {
            //     $order->carrier_id = $cartItem['carrier_id'];
            // }

            $order->payment_type = $request->payment_option;
            $order->delivery_viewed = '0';
            $order->payment_status_viewed = '0';
            $order->code = date('Ymd-His') . rand(10, 99);
            $order->date = strtotime('now');
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
                    $tax +=  cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                    $product_stock = $product->stocks->where('variant', $product_variation)->first();
                } elseif ($cartItem['trendyol'] == 1) {
                    $product = trendyol_product_details(trendyol_account_login(), $cartItem['product_id'], $cartItem['urunNo']);
                    $subtotal += floatval(str_replace(',', '', $product['new_price'])) * $cartItem['quantity'];
                    $subtotal_trendyol += floatval(str_replace(',', '', $product['base_price'])) * $cartItem['quantity'];
                    $tax +=  0;
                    $product_stock = $product['stock'];
                } elseif ($cartItem['provider_id'] != null) {
                    $product = $providerProducts[$cartItem['id']];
                    $subtotal += $product['new_price'] * $cartItem['quantity'];
                    $subtotal_trendyol += $product['base_price'] * $cartItem['quantity'];
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
                            'message' => translate('The requested quantity is not available for ') . $product['name']
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
                //End of storing shipping cost

                $order_detail->quantity = $cartItem['quantity'];

                if (addon_is_activated('club_point') && $cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    $order_detail->earn_point = $product->earn_point;
                }

                $order_detail->save();
                if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    $product->num_of_sale += $cartItem['quantity'];
                    $product->save();
                    $order->seller_id = $product->user_id;
                    $order->shipping_type = $cartItem['shipping_type'];
                } elseif ($cartItem['trendyol'] == 1) {
                    $order->seller_id = $product['user_id'];
                    $order->shipping_type = $cartItem['shipping_type'];
                } elseif ($cartItem['provider_id'] != null) {
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
                $order->coupon_discount = $coupon_discount;
                $order->grand_total -= $coupon_discount;

                $coupon_usage = new CouponUsage;
                $coupon_usage->user_id = Auth::user()->id;
                $coupon_usage->coupon_id = Coupon::where('code', $seller_product[0]->coupon_code)->first()->id;
                $coupon_usage->save();
            }

            $combined_order->grand_total += $order->grand_total;

            $order->save();
        }


        $combined_order->save();

        foreach ($combined_order->orders as $order) {
            NotificationUtility::sendOrderPlacedNotification($order);
        }
        $request->session()->put('combined_order_id', $combined_order->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);
        if ($order != null) {
            foreach ($order->orderDetails as $key => $orderDetail) {
                try {

                    $product_stock = ProductStock::where('product_id', $orderDetail->product_id)->where('variant', $orderDetail->variation)->first();
                    if ($product_stock != null) {
                        $product_stock->qty += $orderDetail->quantity;
                        $product_stock->save();
                    }
                } catch (\Exception $e) {
                }

                $orderDetail->delete();
            }
            $order->delete();
            flash(translate('Order has been deleted successfully'))->success();
        } else {
            flash(translate('Something went wrong'))->error();
        }
        return back();
    }

    public function bulk_order_delete(IdsRequest $request)
    {
        if ($request->id) {
            foreach ($request->id as $order_id) {
                $this->destroy($order_id);
            }
        }

        return 1;
    }
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
                    $seller = $orderDetail->seller;
                    if ($seller && $seller->shop) {
                        $shop = $seller->shop;
                        $shop->admin_to_pay = $shop->admin_to_pay - $orderDetail->price;
                        $shop->save();
                    }
                }
            }
        }

        if (Auth::user()->user_type == 'seller') {
            foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
                if ($orderDetail->delivery_status != 'cancelled' && $orderDetail->delivery_status != 'delivered') {
                    $orderDetail->delivery_status = $request->status;
                    $orderDetail->save();
                }

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
        } else {
            foreach ($order->orderDetails as $key => $orderDetail) {
                if ($orderDetail->delivery_status != 'cancelled' && $orderDetail->delivery_status != 'delivered') {
                    $orderDetail->delivery_status = $request->status;
                    $orderDetail->save();
                }

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

                if (addon_is_activated('affiliate_system')) {
                    if (($request->status == 'delivered' || $request->status == 'cancelled') &&
                        $orderDetail->product_referral_code
                    ) {

                        $no_of_delivered = 0;
                        $no_of_canceled = 0;

                        if ($request->status == 'delivered') {
                            $no_of_delivered = $orderDetail->quantity;
                        }
                        if ($request->status == 'cancelled') {
                            $no_of_canceled = $orderDetail->quantity;
                        }

                        $referred_by_user = User::where('referral_code', $orderDetail->product_referral_code)->first();

                        $affiliateController = new AffiliateController;
                        $affiliateController->processAffiliateStats($referred_by_user->id, 0, 0, $no_of_delivered, $no_of_canceled);
                    }
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
            $request->title = translate("Order updated !");
            $status = str_replace("_", "", $order->delivery_status);
            $request->text = translate("Your order") . " " . $order->code . " " . translate("has been") . " " . translate($status);

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
                $seller = $order_detail->seller;
                if ($seller && $seller->shop) {
                    $shop = $seller->shop;
                    $shop->admin_to_pay = $shop->admin_to_pay - $order_detail->price;
                    $shop->save();
                }
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
            $request->title = translate("Parcel updated !");
            $status = str_replace("_", "", $order_detail->delivery_status);
            $request->text = translate("Your parcel") . " " . $order_detail->tracking_code . " " . translate("has been") . " " . translate($status);

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

    public function update_tracking_code(UpdateTrackingCodeRequest $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->tracking_code = $request->tracking_code;
        $order->save();

        return 1;
    }

    public function update_payment_status(UpdatePaymentStatusRequest $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->payment_status_viewed = '0';
        $order->save();

        if (Auth::user()->user_type == 'seller') {
            foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
                $orderDetail->payment_status = $request->status;
                $orderDetail->save();
            }
        } else {
            foreach ($order->orderDetails as $key => $orderDetail) {
                $orderDetail->payment_status = $request->status;
                $orderDetail->save();
            }
        }

        $status = 'paid';
        foreach ($order->orderDetails as $key => $orderDetail) {
            if ($orderDetail->payment_status != 'paid') {
                $status = 'unpaid';
            }
        }
        $order->payment_status = $status;
        $order->save();


        if (
            $order->payment_status == 'paid' &&
            $order->commission_calculated == 0
        ) {
            calculateCommissionAffilationClubPoint($order);
        }

        //sends Notifications to user
        NotificationUtility::sendNotification($order, $request->status);
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = translate("Order updated !");
            $status = str_replace("_", "", $order->payment_status);
            $request->text = translate("Your order") . " " . $order->code . " " . translate("has been ") . " " . translate($status);

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

    public function assign_delivery_boy(AssignDeliveryBoyRequest $request)
    {
        if (addon_is_activated('delivery_boy')) {

            $order = Order::findOrFail($request->order_id);
            $order->assign_delivery_boy = $request->delivery_boy;
            $order->delivery_history_date = date("Y-m-d H:i:s");
            $order->save();

            $delivery_history = \App\Models\DeliveryHistory::where('order_id', $order->id)
                ->where('delivery_status', $order->delivery_status)
                ->first();

            if (empty($delivery_history)) {
                $delivery_history = new \App\Models\DeliveryHistory;

                $delivery_history->order_id = $order->id;
                $delivery_history->delivery_status = $order->delivery_status;
                $delivery_history->payment_type = $order->payment_type;
            }
            $delivery_history->delivery_boy_id = $request->delivery_boy;

            $delivery_history->save();

            if (env('MAIL_USERNAME') != null && get_setting('delivery_boy_mail_notification') == '1') {
                $array['view'] = 'emails.invoice';
                $array['subject'] = translate('You are assigned to delivery an order. Order code') . ' - ' . $order->code;
                $array['from'] = env('MAIL_FROM_ADDRESS');
                $array['order'] = $order;

                try {
                    Mail::to($order->delivery_boy->email)->queue(new InvoiceEmailManager($array));
                } catch (\Exception $e) {
                }
            }

            if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'assign_delivery_boy')->first()->status == 1) {
                try {
                    SmsUtility::assign_delivery_boy($order->delivery_boy->phone, $order->code);
                } catch (\Exception $e) {
                }
            }
        }

        return 1;
    }

    public function trendyol_manual_order(OrderIdRequest $request)
    {
        $accessToken = env('TRENDYOL_PUBLIC_TOKEN');
        $cartEmpty =  trendyol_empty_cart($accessToken);
        if ($cartEmpty == 'false') {
            return array(
                'status' => 0,
            );
        }
        $order = Order::where('delivery_status', 'pending')->where('id', $request->orderId)->first();

        if (!$order) {
            return array(
                'status' => 0,
            );
        }

        $order->payment_status = 'paid';
        $order->save();

        foreach ($order->orderDetails as $orderDetails) {
            if ($orderDetails->trendyol == 1) {
                $orderDetails->payment_status = 'paid';
                $orderDetails->save();
                $trendyolOrder = TrendyolOrder::where('order_id', $order->id)->where('order_detail_id', $orderDetails->id)->where('trendyol_success', 0)->first();
                if ($trendyolOrder) {
                    trendyol_add_to_cart($accessToken, $trendyolOrder->trendyol_product_id, $trendyolOrder->trendyol_kampanyaID, $trendyolOrder->trendyol_listeID, $trendyolOrder->trendyol_saticiID, $trendyolOrder->trendyol_adet);
                }
            }
        }
        return array(
            'status' => 1,
            'modal_view' => view('modals.trendyol_manual_order', compact('order'))->render()
        );
    }

    public function trendyol_update_order_number(TrendyolUpdateOrderNumberRequest $request)
    {
        if (!$request->trendyol_order_number) {
            flash(translate('Something went wrong'))->error();
            return back();
        }
        $trendyol_order_number = $request->trendyol_order_number;
        $orderId = $request->orderId;
        $order = Order::where('delivery_status', 'pending')->where('id', $orderId)->first();

        if (!$order) {
            flash(translate('Something went wrong'))->error();
            return back();
        }

        $trendyolOrderProductIds = [];
        $trendyolOrderDetails = trendyol_order_details($trendyol_order_number);
        if (count($trendyolOrderDetails) > 0) {
            foreach ($trendyolOrderDetails['products'] as $orderDetail) {
                array_push($trendyolOrderProductIds, $orderDetail['productId']);
            }
        }
        $allorderComplete = true;
        foreach ($order->orderDetails as $orderDetails) {
            if ($orderDetails->trendyol == 1) {
                if (in_array($orderDetails->product_id, $trendyolOrderProductIds)) {
                    $orderDetails->delivery_status = 'confirmed';
                    $orderDetails->save();

                    $trendyolOrder = TrendyolOrder::where('order_id', $order->id)->where('order_detail_id', $orderDetails->id)->where('trendyol_success', 0)->first();
                    if ($trendyolOrder) {
                        $trendyolOrder->trendyol_orderNumber = $trendyol_order_number;
                        $trendyolOrder->trendyol_success = 1;
                        $trendyolOrder->save();
                    }
                    if ($orderDetails->tracking_code == null || empty($orderDetails->tracking_code)) {
                        $accessToken = trendyol_search_account_login();
                        $product     = trendyol_product_details($accessToken, $orderDetails->product_id, $orderDetails->urunNo);
                        $parcel_name = $product['name'];
                        $parcel_url =  route('trendyol-product', ['id' => $product['id'], 'urunNo' => $product['urunNo']]);
                        $parcel_image = $product['photos'][0];
                        $product_id = $product['id'];
                        $urunNo = $product['urunNo'];
                        $parcel_price = (float) str_replace(',', '', $product['new_price']);
                        $quantity = $orderDetails->quantity;
                        $order_number = $order->code;
                        $delivery_type = ($orderDetails->shipping_type == 'home_delivery') ? 'home_delivery' : 'pickup_center';
                        $note = $order->additional_info;
                        $address = json_decode($order->shipping_address);
                        $receiver_name = $address->name;
                        $receiver_id_number = $address->id_number ?? '0';
                        $receiver_email = $address->email;
                        $receiver_phone = $address->phone;
                        $receiver_address = $address->address;
                        $receiver_city = $address->city;
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
                            $tracking_code = $response->data->tracking_number;
                            $orderDetails->tracking_code = $tracking_code;
                            $orderDetails->save();
                        }
                    }
                } else {
                    $allorderComplete = false;
                }
            }
        }

        if ($allorderComplete) {
            $order->delivery_status = 'confirmed';
            $order->save();

            $AlltrendyolDetails = [];
            $trendyolOrder = TrendyolOrder::where('order_id', $orderId)->first();
            if ($trendyolOrder) {
                if ($trendyolOrder->trendyol_orderNumber > 0) {
                    $AlltrendyolDetails[] = trendyol_order_details($trendyolOrder->trendyol_orderNumber);
                }
            }
            $order_shipping_address = json_decode($order->shipping_address);
            $delivery_boys = User::where('city', $order_shipping_address->city)
                ->where('user_type', 'delivery_boy')
                ->get();

            $order->viewed = 1;
            $order->save();

            flash(translate('Order completed'))->success();
            return view('backend.sales.show', compact('order', 'delivery_boys', 'AlltrendyolDetails'));
        } else {

            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

    public function bulk_trendyol_manual_order(IdsRequest $request)
    {
        $accessToken = env('TRENDYOL_PUBLIC_TOKEN');
        $cartEmpty =  trendyol_empty_cart($accessToken);
        $order_ids = $request->id;
        $orderIds = [];
        if ($cartEmpty == 'false') {
            return array(
                'status' => 0,
            );
        }
        if ($order_ids) {
            foreach ($order_ids as $order_id) {
                $order = Order::where('delivery_status', 'pending')->where('id', $order_id)->first();
                if ($order) {
                    $order->payment_status = 'paid';
                    $order->save();
                    foreach ($order->orderDetails as $orderDetails) {
                        if ($orderDetails->trendyol == 1) {
                            $orderDetails->payment_status = 'paid';
                            $orderDetails->save();
                            $trendyolOrder = TrendyolOrder::where('order_id', $order->id)->where('order_detail_id', $orderDetails->id)->where('trendyol_success', 0)->first();
                            if ($trendyolOrder) {
                                array_push($orderIds, $order->id);
                                trendyol_add_to_cart($accessToken, $trendyolOrder->trendyol_product_id, $trendyolOrder->trendyol_kampanyaID, $trendyolOrder->trendyol_listeID, $trendyolOrder->trendyol_saticiID, $trendyolOrder->trendyol_adet);
                            }
                        }
                    }
                }
            }
        }
        $itemsCount = Order::wherein('id', $orderIds)->with('orderDetails')->get()->pluck('orderDetails')->flatten()->pluck('quantity')->sum();
        if (count($orderIds)) {
            return array(
                'status' => 1,
                'modal_view' => view('modals.bulk_trendyol_manual_order', compact('orderIds', 'itemsCount'))->render(),
                'orderIds' => $orderIds
            );
        } else {
            return array(
                'status' => 0,
            );
        }
    }

    public function bulk_trendyol_update_order_number(TrendyolUpdateBulkOrderNumberRequest $request)
    {
        if (!$request->trendyol_order_number) {
            flash(translate('Something went wrong'))->error();
            return back();
        }
        $trendyol_order_number = $request->trendyol_order_number;

        $orderIds = json_decode($request->orderIds, true);

        $trendyolOrderProductIds = [];
        $trendyolOrderDetails = trendyol_order_details($trendyol_order_number);
        if (count($trendyolOrderDetails) > 0) {
            foreach ($trendyolOrderDetails['products'] as $orderDetail) {
                array_push($trendyolOrderProductIds, $orderDetail['productId']);
            }
        }

        foreach ($orderIds as $orderId) {
            $order = Order::where('delivery_status', 'pending')->where('id', $orderId)->first();
            $allorderComplete = true;
            if ($order) {
                foreach ($order->orderDetails as $orderDetails) {
                    if ($orderDetails->trendyol == 1) {
                        if (in_array($orderDetails->product_id, $trendyolOrderProductIds)) {
                            $orderDetails->delivery_status = 'confirmed';
                            $orderDetails->save();
                            $trendyolOrder = TrendyolOrder::where('order_id', $order->id)->where('order_detail_id', $orderDetails->id)->where('trendyol_success', 0)->first();
                            if ($trendyolOrder) {
                                $trendyolOrder->trendyol_orderNumber = $trendyol_order_number;
                                $trendyolOrder->trendyol_success = 1;
                                $trendyolOrder->save();
                            }
                            if ($orderDetails->tracking_code == null || empty($orderDetails->tracking_code)) {
                                $accessToken = trendyol_search_account_login();
                                $product     = trendyol_product_details($accessToken, $orderDetails->product_id, $orderDetails->urunNo);
                                $parcel_name = $product['name'];
                                $parcel_url =  route('trendyol-product', ['id' => $product['id'], 'urunNo' => $product['urunNo']]);
                                $parcel_image = $product['photos'][0];
                                $product_id = $product['id'];
                                $urunNo = $product['urunNo'];
                                $parcel_price = (float) str_replace(',', '', $product['new_price']);
                                $quantity = $orderDetails->quantity;
                                $order_number = $order->code;
                                $delivery_type = ($orderDetails->shipping_type == 'home_delivery') ? 'home_delivery' : 'pickup_center';
                                $note = $order->additional_info;
                                $address = json_decode($order->shipping_address);
                                $receiver_name = $address->name;
                                $receiver_id_number = $address->id_number ?? '0';
                                $receiver_email = $address->email;
                                $receiver_phone = $address->phone;
                                $receiver_address = $address->address;
                                $receiver_city = $address->city;
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
                                    $tracking_code = $response->data->tracking_number;
                                    $orderDetails->tracking_code = $tracking_code;
                                    $orderDetails->save();
                                }
                            }
                        } else {
                            $allorderComplete = false;
                        }
                    }
                }
                if ($allorderComplete) {
                    $order->delivery_status = 'confirmed';
                    $order->save();
                }
            }
        }
        flash(translate('Order completed'))->success();
        return back();
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
}
