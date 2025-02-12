<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Order\PurcahseHistoryRequest;
use App\Http\Resources\V2\PurchasedResource;
use App\Http\Resources\V2\PurchaseHistoryMiniCollection;
use App\Http\Resources\V2\PurchaseHistoryCollection;
use App\Http\Resources\V2\PurchaseHistoryItemsCollection;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Provider;
use App\Utility\CartUtility;
use DB;
use Illuminate\Http\JsonResponse;

class PurchaseHistoryController extends Controller
{
    public function index(PurcahseHistoryRequest $request)
    {
        $order_query = Order::query();
        if ($request->payment_status != "" || $request->payment_status != null) {
            $order_query->where('payment_status', $request->payment_status);
        }
        if ($request->delivery_status != "" || $request->delivery_status != null) {
            $delivery_status = $request->delivery_status;
            $order_query->whereIn("id", function ($query) use ($delivery_status) {
                $query->select('order_id')
                    ->from('order_details')
                    ->where('delivery_status', $delivery_status);
            });
        }
        return new PurchaseHistoryMiniCollection($order_query->where('user_id', auth()->user()->id)->latest()->paginate(5));
    }

    public function details(string $id)
    {
        $order_detail = Order::where('id', $id)->where('user_id', auth()->user()->id)->get();

        return new PurchaseHistoryCollection($order_detail);
    }

    public function items(string $id)
    {
        $order_id = Order::select('id')->where('id', $id)->where('user_id', auth()->user()->id)->first();
        $order_query = OrderDetail::where('order_id', $order_id->id);
        return new PurchaseHistoryItemsCollection($order_query->get());
    }

    public function digital_purchased_list()
    {
        $order_detail_products = DB::table('orders')
            ->orderBy('code', 'desc')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->leftJoin('products', 'order_details.product_id', '=', 'products.id')
            ->where('orders.user_id', auth()->user()->id)
            ->where(function ($query) {
                $query->where('products.digital', '1')
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNotNull('order_details.provider_id')
                            ->where('order_details.digital', '1');
                    });
            })
            ->where('order_details.payment_status', 'paid')
            ->select('order_details.id', 'order_details.product_name', 'order_details.product_image', 'order_details.provider_id', 'order_details.external_order_id')
            ->paginate(5);
        return PurchasedResource::collection($order_detail_products);
    }

    public function re_order(string $id)
    {
        $user_id = auth()->user()->id;
        $success_msgs = [];
        $failed_msgs = [];

        $carts = Cart::where('user_id', auth()->user()->id)->get();
        $check_auction_in_cart = CartUtility::check_auction_in_cart($carts);
        if ($check_auction_in_cart) {
            array_push($failed_msgs, translate('Remove auction product from cart to add products.'));
            return response()->json([
                'success_msgs' => $success_msgs,
                'failed_msgs' => $failed_msgs
            ]);
        }

        $order = Order::findOrFail($id);

        $providers = [];
        $providers_products = [];
        $providers = OrderDetail::where('order_id', $order->id)->whereNotNull('provider_id')
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
        $data['user_id'] = $user_id;
        foreach ($order->orderDetails as $key => $orderDetail) {
            if ($orderDetail->trendyol == 0 && $orderDetail->provider_id == null) {
                $product = $orderDetail->product;
            } elseif ($orderDetail->trendyol == 1) {
                $accessToken = trendyol_search_account_login();
                $productArray = trendyol_cart_product_details($accessToken, $orderDetail->product_id, $orderDetail->urunNo);
                $product = new Product($productArray);
            } elseif ($orderDetail->provider_id != null) {
                $product = $providers_products[$orderDetail->id];
            }

            if ($orderDetail->trendyol == 0 && $orderDetail->provider_id == null) {
                if (
                    !$product || $product->published == 0 ||
                    $product->approved == 0 || ($product->wholesale_product && !addon_is_activated("wholesale"))
                ) {
                    array_push($failed_msgs, translate('An item from this order is not available now.'));
                    continue;
                }
            }

            if ($product['auction_product'] == 1) {
                array_push($failed_msgs, translate('You can not re order an auction product.'));
                break;
            }



            // If product min qty is greater then the ordered qty, then update the order qty
            $order_qty = $orderDetail->quantity;
            if ($product['digital'] == 0 && $order_qty < $product['min_qty']) {
                $order_qty = $product->min_qty;
            }

            if ($orderDetail->trendyol == 0 && $orderDetail->provider_id == null) {
                $cart = Cart::firstOrNew([
                    'variation' => $orderDetail->variation,
                    'user_id' => $user_id,
                    'product_id' => $product->id
                ]);

                $product_stock = $product->stocks->where('variant', $orderDetail->variation)->first();
                if ($product_stock) {
                    $quantity = 1;

                    if ($product->digital != 1) {
                        $quantity = $product_stock->qty;
                        if ($quantity > 0) {
                            if ($cart->exists) {
                                $order_qty = $cart->quantity + $order_qty;
                            }
                            //If order qty is greater then the product stock, set order qty = current product stock qty
                            $quantity = ($quantity >= $order_qty) ? $order_qty : $quantity;
                        } else {
                            array_push($failed_msgs, $product->getTranslation('name') . ' ' . translate('is stock out.'));
                            continue;
                        }
                    }

                    $price = CartUtility::get_price($product, $product_stock, $quantity);
                    $tax = CartUtility::tax_calculation($product, $price);

                    CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity);
                    array_push($success_msgs, $product->getTranslation('name') . ' ' . translate('added to cart.'));
                } else {
                    array_push($failed_msgs, $product->getTranslation('name') . ' ' . translate(' is stock out.'));
                }
            } elseif ($orderDetail->trendyol == 1) {
                $trendyol_price = 0;
                $cart = Cart::firstOrNew([
                    'variation' => $orderDetail->variation,
                    'user_id' => auth()->user()->id,
                    'product_id' => $product->id,
                    'trendyol' => 1,
                    'urunNo' => $productArray['urunNo'],
                    'trendyol_shop_id' => $productArray['shopId']
                ]);
                $product_stock = $productArray['stock'];
                if ($product_stock > 0) {
                    $quantity = 1;
                    if ($product->digital != 1) {
                        $quantity = $product_stock;
                        if ($quantity > 0) {
                            if ($cart->exists) {
                                $order_qty = $cart->quantity + $order_qty;
                            }
                            //If order qty is greater then the product stock, set order qty = current product stock qty
                            $quantity = $quantity >= $order_qty ? $order_qty : $quantity;
                        } else {
                            array_push($failed_msgs, $product->name . ' ' . translate(' is stock out.'));
                            continue;
                        }
                    }
                    $price = $product->unit_price;
                    $trendyol_price = $productArray['base_price'] ;
                    $tax = $product->tax;

                    CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity, $trendyol_price);
                    array_push($success_msgs, $product->name . ' ' . translate('added to cart.'));
                } else {
                    array_push($failed_msgs, $product->name . ' ' . translate('is stock out.'));
                }
            }elseif ($orderDetail->provider_id != null) {
                $trendyol_price = 0;
                $cart = Cart::firstOrNew([
                    'variation' => $orderDetail->variation,
                    'user_id' => auth()->user()->id,
                    'product_id' => $orderDetail->product_id,
                    'provider_id' => $orderDetail->provider_id
                ]);
                $product_stock = $product['stock'];
                if ($product_stock > 0) {
                    $quantity = 1;
                    if ($product['digital'] != 1) {
                        $quantity = $product_stock;
                        if ($quantity > 0) {
                            if ($cart->exists) {
                                $order_qty = $cart->quantity + $order_qty;
                            }
                            //If order qty is greater then the product stock, set order qty = current product stock qty
                            $quantity = $quantity >= $order_qty ? $order_qty : $quantity;
                        } else {
                            array_push($failed_msgs, $product['name'] . ' ' . translate(' is stock out.'));
                            continue;
                        }
                    }
                    $price = $product['new_price'];
                    $trendyol_price = $product['base_price'];
                    $tax = $product['tax'] ?? 0;

                    CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity, $trendyol_price);
                    array_push($success_msgs, $product['name'] . ' ' . translate('added to cart.'));
                } else {
                    array_push($failed_msgs, $product['name'] . ' ' . translate('is stock out.'));
                }
            }
        }


        return response()->json([
            'success_msgs' => $success_msgs,
            'failed_msgs' => $failed_msgs
        ]);
    }
}
