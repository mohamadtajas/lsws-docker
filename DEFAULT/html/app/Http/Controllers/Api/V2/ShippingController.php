<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Order\ShippingCostRequest;
use App\Http\Resources\V2\PickupPointResource;
use App\Models\Cart;
use App\Models\PickupPoint;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Shop;

class ShippingController extends Controller
{
    public function pickup_list()
    {
        $pickup_point_list = PickupPoint::where('pick_up_status', '=', 1)->get();

        return PickupPointResource::collection($pickup_point_list);
        // return response()->json(['result' => true, 'pickup_points' => $pickup_point_list], 200);
    }

    public function shipping_cost(ShippingCostRequest $request)
    {
        $main_carts = Cart::where('user_id', auth()->user()->id)->get();
        $shippingTrendyolDone = false;
        $accessToken = trendyol_account_login();
        $subtotal = 0;
        $tax = 0;
        $shipping = 0;

        $providerProducts = [];

        $providersProductsId =  Cart::where('user_id', auth()->user()->id)
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

        foreach ($request->seller_list as $key => $seller) {
            $seller['shipping_cost'] = 0;

            $carts = Cart::where('user_id', auth()->user()->id)->where("owner_id", $seller['seller_id'])->get();

            foreach ($carts as $key => $cartItem) {
                if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    $cartItem['shipping_cost'] = 0;

                    if ($seller['shipping_type'] == 'pickup_point') {
                        $cartItem['shipping_type'] = 'pickup_point';
                        $cartItem['pickup_point'] = $seller['shipping_id'];
                    } else
                    if ($seller['shipping_type'] == 'home_delivery') {
                        $cartItem['shipping_type'] = 'home_delivery';
                        $cartItem['pickup_point'] = 0;

                        $cartItem['shipping_cost'] = getShippingCost($main_carts, $key);
                    } else
                    if ($seller['shipping_type'] == 'carrier') {
                        $cartItem['shipping_type'] = 'carrier';
                        $cartItem['pickup_point'] = 0;
                        $cartItem['carrier_id'] = $seller['shipping_id'];

                        $cartItem['shipping_cost'] = getShippingCost($carts, $key, $seller['shipping_id']);
                    }
                } elseif ($cartItem['trendyol'] == 1) {
                    $productArray = trendyol_product_details($accessToken, $cartItem['product_id'], $cartItem['urunNo']);
                    $subtotal += floatval(str_replace(',', '', $productArray['new_price'])) * $cartItem['quantity'];
                    $shipping += $cartItem['shipping_cost'];
                    $cartItem['shipping_cost'] = 0;
                    if ($seller['shipping_type'] == 'pickup_point') {
                        $cartItem['shipping_type'] = 'pickup_point';
                        $cartItem['pickup_point'] = $seller['shipping_id'];
                    } else
                    if ($seller['shipping_type'] == 'home_delivery') {
                        $cartItem['shipping_type'] = 'home_delivery';
                        $cartItem['pickup_point'] = 0;

                        $cartItem['shipping_cost'] += getShippingCost($carts, $key, '', $productArray);
                    } else
                    if ($seller['shipping_type'] == 'carrier') {
                        $cartItem['shipping_type'] = 'carrier';
                        $cartItem['pickup_point'] = 0;
                        $cartItem['carrier_id'] = $seller['shipping_id'];

                        $cartItem['shipping_cost'] += getShippingCost($carts, $key, $cartItem['carrier_id'], $productArray);
                    }
                    $sameShop = $total = $carts->where('trendyol_shop_id', $cartItem['trendyol_shop_id'])
                        ->sum(function ($item) {
                            return ($item['trendyol_price']) * $item['quantity'];
                        });
                    if ($shippingTrendyolDone || $sameShop > env('TRENDYOL_MIN_SHIPPING_COST')) {
                        $cartItem['shipping_cost']  += 0;
                    } else {
                        $cartItem['shipping_cost'] += $productArray['base_price'] * $cartItem['quantity']  < env('TRENDYOL_MIN_SHIPPING_COST') ? env('TRENDYOL_SHIPPING_COST') : 0;
                        $shippingTrendyolDone = true;
                    }
                } elseif ($cartItem['provider_id'] != null) {
                    $product = $providerProducts[$cartItem['id']];
                    $subtotal += $product['new_price'] * $cartItem['quantity'];
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
                $total = $subtotal + $tax + $shipping;
                $cartItem->save();
            }
        }

        //Total shipping cost $calculate_shipping
        $total_shipping_cost = Cart::where('user_id', auth()->user()->id)->sum('shipping_cost');
        return response()->json(['result' => true, 'shipping_type' => get_setting('shipping_type'), 'value' => convert_price($total_shipping_cost), 'value_string' => format_price(convert_price($total_shipping_cost))], 200);
    }


    public function getDeliveryInfo()
    {
        $owner_ids = Cart::where('user_id', auth()->user()->id)->select('owner_id')->groupBy('owner_id')->pluck('owner_id')->toArray();
        $shops = [];
        if (!empty($owner_ids)) {
            foreach ($owner_ids as $owner_id) {
                $shop = array();
                $shop_items_raw_data = Cart::where('user_id', auth()->user()->id)->where('owner_id', $owner_id)->get()->toArray();
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
                $shop_items_data = array();
                if (!empty($shop_items_raw_data)) {
                    foreach ($shop_items_raw_data as $shop_items_raw_data_item) {
                        if ($shop_items_raw_data_item['trendyol'] == 1) {
                            $accessToken = trendyol_search_account_login();
                            $productArray = trendyol_cart_product_details($accessToken, $shop_items_raw_data_item["product_id"], $shop_items_raw_data_item["urunNo"]);
                            $product = new Product($productArray);
                            $shop_items_data_item["id"] = intval($shop_items_raw_data_item["id"]);
                            $shop_items_data_item["owner_id"] = intval($shop_items_raw_data_item["owner_id"]);
                            $shop_items_data_item["user_id"] = intval($shop_items_raw_data_item["user_id"]);
                            $shop_items_data_item["product_id"] = intval($shop_items_raw_data_item["product_id"]);
                            $shop_items_data_item["product_name"] = $product->name;
                            $shop_items_data_item["product_thumbnail_image"] = $productArray['photos'][0];
                            $shop_items_data_item["product_is_digital"] = $product->digital == 1;
                            $shop_items_data_item["trendyol"] = intval($shop_items_raw_data_item['trendyol']);
                            $shop_items_data_item["urunNo"] = intval($shop_items_raw_data_item['urunNo']);
                            $shop_items_data_item["variation"] = $shop_items_raw_data_item['variation'];
                            $shop_items_data[] = $shop_items_data_item;
                        } elseif ($shop_items_raw_data_item['trendyol'] == 0 && $shop_items_raw_data_item['provider_id'] == null) {
                            $product = Product::where('id', $shop_items_raw_data_item["product_id"])->first();
                            $shop_items_data_item["id"] = intval($shop_items_raw_data_item["id"]);
                            $shop_items_data_item["owner_id"] = intval($shop_items_raw_data_item["owner_id"]);
                            $shop_items_data_item["user_id"] = intval($shop_items_raw_data_item["user_id"]);
                            $shop_items_data_item["product_id"] = intval($shop_items_raw_data_item["product_id"]);
                            $shop_items_data_item["product_name"] = $product->getTranslation('name');
                            $shop_items_data_item["product_thumbnail_image"] = uploaded_asset($product->thumbnail_img);
                            $shop_items_data_item["product_is_digital"] = $product->digital == 1;
                            $shop_items_data_item["trendyol"] = intval($shop_items_raw_data_item['trendyol']);
                            $shop_items_data_item["urunNo"] = intval($shop_items_raw_data_item['urunNo']);
                            $shop_items_data_item["variation"] = $shop_items_raw_data_item['variation'];
                            $shop_items_data[] = $shop_items_data_item;
                        } elseif ($shop_items_raw_data_item['provider_id'] != null) {
                            $product = $providers_products[$shop_items_raw_data_item["id"]];
                            $shop_items_data_item["id"] = intval($shop_items_raw_data_item["id"]);
                            $shop_items_data_item["owner_id"] = intval($shop_items_raw_data_item["owner_id"]);
                            $shop_items_data_item["user_id"] = intval($shop_items_raw_data_item["user_id"]);
                            $shop_items_data_item["product_id"] = intval($shop_items_raw_data_item["product_id"]);
                            $shop_items_data_item["product_name"] = $product['name'];
                            $shop_items_data_item["product_thumbnail_image"] = $product['thumbnail'];
                            $shop_items_data_item["product_is_digital"] = $product['digital'] == 1;
                            $shop_items_data_item["trendyol"] = intval($shop_items_raw_data_item['trendyol']);
                            $shop_items_data_item["urunNo"] = intval($shop_items_raw_data_item['urunNo']);
                            $shop_items_data_item["variation"] = $shop_items_raw_data_item['variation'];
                            $shop_items_data[] = $shop_items_data_item;
                        }
                    }
                }


                $shop_data = Shop::where('user_id', $owner_id)->first();


                if ($shop_data) {
                    $shop['name'] = $shop_data->name;
                    $shop['owner_id'] = (int) $owner_id;
                    $shop['cart_items'] = $shop_items_data;
                } else {
                    $shop['name'] = "Inhouse";
                    $shop['owner_id'] = (int) $owner_id;
                    $shop['cart_items'] = $shop_items_data;
                }
                $shop['carriers'] = seller_base_carrier_list($owner_id);
                $shop['pickup_points'] = [];
                if (get_setting('pickup_point') == 1) {
                    $pickup_point_list = PickupPoint::where('pick_up_status', '=', 1)->get();
                    $shop['pickup_points']  = PickupPointResource::collection($pickup_point_list);
                }
                $shops[] = $shop;
            }
        }

        //dd($shops);

        return response()->json($shops);
    }
}
