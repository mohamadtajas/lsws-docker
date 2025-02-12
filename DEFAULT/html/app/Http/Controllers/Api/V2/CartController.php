<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Cart\ApiAddToCartRequest;
use App\Http\Requests\Cart\ChangeQuantityRequest;
use App\Http\Requests\Cart\ProcessRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Shop;
use App\Utility\CartUtility;

class CartController extends Controller
{
    public function summary()
    {
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
        $invite_discount = 0;
        $items = auth()->user()->carts;
        if ($items->isEmpty()) {
            return response()->json([
                'sub_total' => format_price(0.00),
                'tax' => format_price(0.00),
                'shipping_cost' => format_price(0.00),
                'discount' => format_price(0.00),
                'grand_total' => format_price(0.00),
                'grand_total_value' => 0.00,
                'coupon_code' => "",
                'coupon_applied' => false,
            ]);
        }

        $sum = 0.00;
        $subtotal = 0.00;
        $tax = 0.00;
        foreach ($items as $cartItem) {
            if ($cartItem['trendyol'] == 1) {
                $accessToken = trendyol_search_account_login();
                $productArray = trendyol_product_details($accessToken, $cartItem['product_id'], $cartItem['urunNo']);
                $product = new Product($productArray);
                $subtotal += floatval(str_replace(',', '', $productArray['new_price'])) * $cartItem['quantity'];
                $tax += 0;
            } elseif ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                $product = Product::find($cartItem['product_id']);
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
            } elseif ($cartItem['provider_id'] != null) {
                $product = $providers_products[$cartItem['id']];
                $subtotal += floatval(str_replace(',', '', $product['new_price'])) * $cartItem['quantity'];
                $tax += 0;
            }
        }

        $shipping_cost = $items->sum('shipping_cost');
        $discount = $items->sum('discount');
        $sum = ($subtotal + $tax + $shipping_cost) - $discount;

        if (get_setting('invite_system') == 1 && auth()->user()->invitation->count() == 1) {
            $invite_discount = env('INVITE_DISCOUNT');
            $sum = $sum - $invite_discount;
        }
        return response()->json([
            'sub_total' => single_price($subtotal),
            'tax' => single_price($tax),
            'shipping_cost' => single_price($shipping_cost),
            'discount' => single_price($discount),
            'grand_total' => single_price($sum),
            'grand_total_value' => convert_price($sum),
            'coupon_code' => $items[0]->coupon_code,
            'coupon_applied' => $items[0]->coupon_applied == 1,
            'invite_discount' => single_price($invite_discount),
        ]);
    }

    public function count()
    {
        $items = auth()->user()->carts;

        return response()->json([
            'count' => sizeof($items),
            'status' => true,
        ]);
    }

    public function getList()
    {
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
        $owner_ids = Cart::where('user_id', auth()->user()->id)->select('owner_id')->groupBy('owner_id')->pluck('owner_id')->toArray();
        $currency_symbol = currency_symbol();
        $shops = [];
        $sub_total = 0.00;
        $grand_total = 0.00;
        if (!empty($owner_ids)) {
            foreach ($owner_ids as $owner_id) {
                $shop = array();
                $shop_items_raw_data = Cart::where('user_id', auth()->user()->id)->where('owner_id', $owner_id)->get()->toArray();
                $shop_items_data = array();
                if (!empty($shop_items_raw_data)) {
                    foreach ($shop_items_raw_data as $shop_items_raw_data_item) {
                        if ($shop_items_raw_data_item['trendyol'] == 1) {
                            $accessToken = trendyol_search_account_login();
                            $productArray = trendyol_cart_product_details($accessToken, $shop_items_raw_data_item["product_id"], $shop_items_raw_data_item["urunNo"]);
                            $product = new Product($productArray);
                            $price =  $product->getAttributes()['unit_price'] * intval($shop_items_raw_data_item["quantity"]);
                            $tax   = $shop_items_raw_data_item['tax'];
                            $shop_items_data_item["id"] = intval($shop_items_raw_data_item["id"]);
                            $shop_items_data_item["owner_id"] = intval($shop_items_raw_data_item["owner_id"]);
                            $shop_items_data_item["user_id"] = intval($shop_items_raw_data_item["user_id"]);
                            $shop_items_data_item["product_id"] = intval($shop_items_raw_data_item["product_id"]);
                            $shop_items_data_item["product_name"] = $product->name ?? translate('Product not found');
                            $shop_items_data_item["auction_product"] = $product->auction_product;
                            $shop_items_data_item["product_thumbnail_image"] = $productArray['photos'][0] ?? static_asset('assets/img/placeholder.webp');
                            $shop_items_data_item["variation"] = $shop_items_raw_data_item["variation"];
                            $shop_items_data_item["price"] = (float) $product->getAttributes()['unit_price'];
                            $shop_items_data_item["currency_symbol"] = $currency_symbol;
                            $shop_items_data_item["tax"] = (float) $shop_items_raw_data_item['tax'];
                            $shop_items_data_item["price"] = single_price($price);
                            $shop_items_data_item["currency_symbol"] = $currency_symbol;
                            $shop_items_data_item["tax"] = single_price($tax);
                            $shop_items_data_item["shipping_cost"] = (float) $shop_items_raw_data_item["shipping_cost"];
                            $shop_items_data_item["quantity"] = intval($shop_items_raw_data_item["quantity"]);
                            $shop_items_data_item["lower_limit"] = intval($product->min_qty);
                            $shop_items_data_item["upper_limit"] = intval($productArray['stock'] ?? 0);
                            $shop_items_data_item["trendyol"] = intval($shop_items_raw_data_item['trendyol']);
                            $shop_items_data_item["urunNo"] = intval($shop_items_raw_data_item['urunNo']);
                            $shop_items_data_item["provider_id"] = intval($shop_items_raw_data_item['provider_id']);
                            $sub_total += $price + $tax;
                            $shop_items_data[] = $shop_items_data_item;
                        } elseif ($shop_items_raw_data_item['trendyol'] == 0 && $shop_items_raw_data_item['provider_id'] == null) {
                            $product = Product::where('id', $shop_items_raw_data_item["product_id"])->first();
                            $price = cart_product_price($shop_items_raw_data_item, $product, false, false) * intval($shop_items_raw_data_item["quantity"]);
                            $tax = cart_product_tax($shop_items_raw_data_item, $product, false);
                            $shop_items_data_item["id"] = intval($shop_items_raw_data_item["id"]);
                            $shop_items_data_item["owner_id"] = intval($shop_items_raw_data_item["owner_id"]);
                            $shop_items_data_item["user_id"] = intval($shop_items_raw_data_item["user_id"]);
                            $shop_items_data_item["product_id"] = intval($shop_items_raw_data_item["product_id"]);
                            $shop_items_data_item["product_name"] = $product->getTranslation('name');
                            $shop_items_data_item["auction_product"] = $product->auction_product;
                            $shop_items_data_item["product_thumbnail_image"] = uploaded_asset($product->thumbnail_img);
                            $shop_items_data_item["variation"] = $shop_items_raw_data_item["variation"];
                            $shop_items_data_item["price"] = (float) cart_product_price($shop_items_raw_data_item, $product, false, false);
                            $shop_items_data_item["currency_symbol"] = $currency_symbol;
                            $shop_items_data_item["tax"] = (float) cart_product_tax($shop_items_raw_data_item, $product, false);
                            $shop_items_data_item["price"] = single_price($price);
                            $shop_items_data_item["currency_symbol"] = $currency_symbol;
                            $shop_items_data_item["tax"] = single_price($tax);
                            // $shop_items_data_item["tax"] = (float) cart_product_tax($shop_items_raw_data_item, $product, false);
                            $shop_items_data_item["shipping_cost"] = (float) $shop_items_raw_data_item["shipping_cost"];
                            $shop_items_data_item["quantity"] = intval($shop_items_raw_data_item["quantity"]);
                            $shop_items_data_item["lower_limit"] = intval($product->min_qty);
                            $shop_items_data_item["upper_limit"] = intval($product->stocks->where('variant', $shop_items_raw_data_item['variation'])->first()->qty);
                            $shop_items_data_item["trendyol"] = intval($shop_items_raw_data_item['trendyol']);
                            $shop_items_data_item["urunNo"] = intval($shop_items_raw_data_item['urunNo']);
                            $shop_items_data_item["provider_id"] = intval($shop_items_raw_data_item['provider_id']);
                            $sub_total += $price + $tax;
                            $shop_items_data[] = $shop_items_data_item;
                        } elseif ($shop_items_raw_data_item['provider_id'] != null) {
                            $product = $providers_products[$shop_items_raw_data_item["id"]];

                            $price =  $product['unit_price'] * intval($shop_items_raw_data_item["quantity"]);
                            $tax   = $shop_items_raw_data_item['tax'];
                            $shop_items_data_item["id"] = intval($shop_items_raw_data_item["id"]);
                            $shop_items_data_item["owner_id"] = intval($shop_items_raw_data_item["owner_id"]);
                            $shop_items_data_item["user_id"] = intval($shop_items_raw_data_item["user_id"]);
                            $shop_items_data_item["product_id"] = intval($shop_items_raw_data_item["product_id"]);
                            $shop_items_data_item["product_name"] = $product['name'] ?? translate('Product not found');
                            $shop_items_data_item["auction_product"] = $product['auction_product'] ?? 0;
                            $shop_items_data_item["product_thumbnail_image"] = $product['thumbnail'] ?? static_asset('assets/img/placeholder.webp');
                            $shop_items_data_item["variation"] = $shop_items_raw_data_item["variation"];
                            $shop_items_data_item["price"] = (float) $product['unit_price'];
                            $shop_items_data_item["currency_symbol"] = $currency_symbol;
                            $shop_items_data_item["tax"] = (float) $shop_items_raw_data_item['tax'];
                            $shop_items_data_item["price"] = single_price($price);
                            $shop_items_data_item["currency_symbol"] = $currency_symbol;
                            $shop_items_data_item["tax"] = single_price($tax);
                            $shop_items_data_item["shipping_cost"] = (float) $shop_items_raw_data_item["shipping_cost"];
                            $shop_items_data_item["quantity"] = intval($shop_items_raw_data_item["quantity"]);
                            $shop_items_data_item["lower_limit"] = intval($product['min_qty']);
                            $shop_items_data_item["upper_limit"] = intval($product['stock'] ?? 0);
                            $shop_items_data_item["trendyol"] = intval($shop_items_raw_data_item['trendyol']);
                            $shop_items_data_item["urunNo"] = intval($shop_items_raw_data_item['urunNo']);
                            $shop_items_data_item["provider_id"] = intval($shop_items_raw_data_item['provider_id']);
                            $sub_total += $price + $tax;
                            $shop_items_data[] = $shop_items_data_item;
                        }
                    }
                }

                $grand_total += $sub_total;
                $shop_data = Shop::where('user_id', $owner_id)->first();
                if ($shop_data) {
                    $shop['name'] = translate($shop_data->name);
                    $shop['owner_id'] = (int) $owner_id;
                    $shop['sub_total'] = single_price($sub_total);
                    $shop['cart_items'] = $shop_items_data;
                } else {
                    $shop['name'] = translate("Inhouse");
                    $shop['owner_id'] = (int) $owner_id;
                    $shop['sub_total'] = single_price($sub_total);
                    $shop['cart_items'] = $shop_items_data;
                }
                $shops[] = $shop;
                $sub_total = 0.00;
            }
        }

        //dd($shops);

        return response()->json([
            "grand_total" => single_price($grand_total),
            "data" => $shops
        ]);
    }

    public function add(ApiAddToCartRequest $request)
    {
        $carts = Cart::where('user_id', auth()->user()->id)->get();
        $check_auction_in_cart = CartUtility::check_auction_in_cart($carts);

        if (isset($request->trendyol) && $request->trendyol == 1) {
            $trendyol_price = 0;
            $accessToken = trendyol_search_account_login();
            $productArray = trendyol_cart_product_details($accessToken, $request->id, $request->urunNo);
            $product = new Product($productArray);
            $carts = array();
            $quantity = $request['quantity'];
            if ($product->min_qty > $quantity) {
                return response()->json([
                    'result' => false,
                    'message' => translate("Minimum") . " {$product->min_qty} " . translate("item(s) should be ordered")
                ], 200);
            }
            $price = $product->getAttributes()['unit_price'];
            $trendyol_price = $productArray['base_price'];
            $tax = $product->tax;
            $product_stock = $productArray['stock'];
            if ($product_stock <= 0) {
                return response()->json([
                    'result' => false,
                    'message' => translate("Stock out")
                ], 400);
            }
            $cart = Cart::firstOrNew([
                'variation' => $productArray['varyantlar'],
                'user_id' => auth()->user()->id,
                'product_id' =>  $product->id,
                'trendyol' => 1,
                'urunNo' => $productArray['urunNo'],
                'shipping_cost' =>  env('TRENDYOL_SHIPPING_COST'),
                'trendyol_shop_id' => $productArray['shopId']
            ]);
            if ($cart->exists && $product->digital == 0) {
                if ($product->auction_product == 1 && ($cart->product_id == $product->id)) {
                    return response()->json([
                        'result' => false,
                        'message' => translate('Some thing went wrong')
                    ], 400);
                }
                if ($product_stock < $cart->quantity + $request['quantity']) {
                    return response()->json([
                        'result' => false,
                        'message' => translate('Some thing went wrong')
                    ], 400);
                }
                $quantity = $cart->quantity + $request['quantity'];
            }


            CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity, $trendyol_price);

            $carts = Cart::where('user_id', auth()->user()->id)->get();
            return response()->json([
                'result' => true,
                'message' => translate('Product added to cart successfully')
            ]);
        } elseif (isset($request->provider) && $request->provider != null) {
            $provider = Provider::find($request->provider);
            $trendyol_price = 0;
            $product =  $provider->service()->productDetails($request->id);
            $carts = array();
            $quantity = $request->quantity;
            if ($product['min_qty'] > $quantity) {
                return response()->json([
                    'result' => false,
                    'message' => translate("Minimum") . " {$product['min_qty']} " . translate("item(s) should be ordered")
                ], 200);
            }
            $price = $product['unit_price'];
            $trendyol_price = $product['base_price'];
            $tax = $product['tax'] ?? 0;
            $product_stock = $product['stock'];
            if ($product_stock <= 0) {
                return response()->json([
                    'result' => false,
                    'message' => translate("Stock out")
                ], 400);
            }
            $cart = Cart::firstOrNew([
                'user_id' => auth()->user()->id,
                'product_id' =>  $product['id'],
                'trendyol' => 0,
                'provider_id' => $provider->id,
                'urunNo' => null,
                'shipping_cost' =>  env('TRENDYOL_SHIPPING_COST')
            ]);
            if ($cart->exists && $product['digital'] == 0) {
                if ($product['auction_product'] == 1 && ($cart->product_id == $product['id'])) {
                    return response()->json([
                        'result' => false,
                        'message' => translate('Some thing went wrong')
                    ], 400);
                }
                if ($product_stock < $cart->quantity + $request['quantity']) {
                    return response()->json([
                        'result' => false,
                        'message' => translate('Some thing went wrong')
                    ], 400);
                }
                if ($product['digital'] == 1 && ($cart->product_id == $product['id'])) {
                    return response()->json([
                        'result' => false,
                        'message' => translate('Already added this product')
                    ]);
                }
                $quantity = $cart->quantity + $request['quantity'];
            }

            CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity, $trendyol_price);

            $carts = Cart::where('user_id', auth()->user()->id)->get();
            return response()->json([
                'result' => true,
                'message' => translate('Product added to cart successfully')
            ]);
        } else {
            $product = Product::findOrFail($request->id);
        }

        if ($check_auction_in_cart && $product->auction_product == 0) {
            return response()->json([
                'result' => false,
                'message' => translate('Remove auction product from cart to add this product.')
            ], 200);
        }
        if ($check_auction_in_cart == false && count($carts) > 0 && $product->auction_product == 1) {
            return response()->json([
                'result' => false,
                'message' => translate('Remove other products from cart to add this auction product.')
            ], 200);
        }

        if ($product->min_qty > $request->quantity) {
            return response()->json([
                'result' => false,
                'message' => translate("Minimum") . " {$product->min_qty} " . translate("item(s) should be ordered")
            ], 200);
        }

        $variant = $request->variant;
        $tax = 0;
        $quantity = $request->quantity;

        $product_stock = $product->stocks->where('variant', $variant)->first();
        if ($product_stock->qty <= 0) {
            return response()->json([
                'result' => false,
                'message' => translate("Stock out")
            ], 400);
        }

        $cart = Cart::firstOrNew([
            'variation' => $variant,
            'user_id' => auth()->user()->id,
            'product_id' => $request['id']
        ]);

        $variant_string = $variant != null && $variant != "" ? translate("for") . " ($variant)" : "";

        if ($cart->exists && $product->digital == 0) {
            if ($product->auction_product == 1 && ($cart->product_id == $product->id)) {
                return response()->json([
                    'result' => false,
                    'message' => translate('This auction product is already added to your cart.')
                ], 200);
            }
            if ($product_stock->qty < $cart->quantity + $request['quantity']) {
                if ($product_stock->qty == 0) {
                    return response()->json([
                        'result' => false,
                        'message' => translate("Stock out")
                    ], 200);
                } else {
                    return response()->json([
                        'result' => false,
                        'message' => translate("Only") . " {$product_stock->qty} " . translate("item(s) are available") . " {$variant_string}"
                    ], 200);
                }
            }
            if ($product->digital == 1 && ($cart->product_id == $product->id)) {
                return response()->json([
                    'result' => false,
                    'message' => translate('Already added this product')
                ]);
            }
            $quantity = $cart->quantity + $request['quantity'];
        }

        $price = CartUtility::get_price($product, $product_stock, $request->quantity);
        $tax = CartUtility::tax_calculation($product, $price);
        CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity);

        return response()->json([
            'result' => true,
            'message' => translate('Product added to cart successfully')
        ]);
    }

    public function changeQuantity(ChangeQuantityRequest $request)
    {
        $cart = Cart::find($request->id);
        if ($cart != null) {
            if ($cart->trendyol == 1) {
                $accessToken = trendyol_search_account_login();
                $product = trendyol_cart_product_details($accessToken, $cart->product_id, $cart->urunNo);
                if ($product['stock'] >= $request->quantity) {
                    $cart->update([
                        'quantity' => $request->quantity
                    ]);

                    return response()->json(['result' => true, 'message' => translate('Cart updated')], 200);
                } else {
                    return response()->json(['result' => false, 'message' => translate('Maximum available quantity reached')], 200);
                }
            } elseif ($cart->trendyol == 0 && $cart->provider_id == null) {
                $product = Product::find($cart->product_id);
                if ($product->auction_product == 1) {
                    return response()->json(['result' => false, 'message' => translate('Maximum available quantity reached')], 200);
                }
                if ($cart->product->stocks->where('variant', $cart->variation)->first()->qty >= $request->quantity) {
                    $cart->update([
                        'quantity' => $request->quantity
                    ]);

                    return response()->json(['result' => true, 'message' => translate('Cart updated')], 200);
                } else {
                    return response()->json(['result' => false, 'message' => translate('Maximum available quantity reached')], 200);
                }
            } elseif ($cart->provider_id != null) {
                $provider = Provider::find($cart->provider_id);
                $product =  $provider->service()->productDetails($cart->product_id);
                if ($product['stock'] >= $request->quantity && $product['digital'] == 0) {
                    $cart->update([
                        'quantity' => $request->quantity
                    ]);

                    return response()->json(['result' => true, 'message' => translate('Cart updated')], 200);
                } else {
                    return response()->json(['result' => false, 'message' => translate('Maximum available quantity reached')], 200);
                }
            }
        }

        return response()->json(['result' => false, 'message' => translate('Something went wrong')], 200);
    }

    public function process(ProcessRequest $request)
    {
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
        $cart_ids = explode(",", $request->cart_ids);
        $cart_quantities = explode(",", $request->cart_quantities);

        if (!empty($cart_ids)) {
            $i = 0;
            foreach ($cart_ids as $cart_id) {
                $cart_item = Cart::where('id', $cart_id)->first();

                if ($cart_item->trendyol == 0 && $cart_item->provider_id == null) {
                    $product = Product::where('id', $cart_item->product_id)->first();

                    if ($product->min_qty > $cart_quantities[$i]) {
                        return response()->json(['result' => false, 'message' => translate("Minimum") . " {$product->min_qty} " . translate("item(s) should be ordered for") . " {$product->name}"], 200);
                    }

                    $stock = $cart_item->product->stocks->where('variant', $cart_item->variation)->first()->qty;
                    $variant_string = $cart_item->variation != null && $cart_item->variation != "" ? " ($cart_item->variation)" : "";
                    if ($stock >= $cart_quantities[$i] || $product->digital == 1) {
                        $cart_item->update([
                            'quantity' => $cart_quantities[$i]
                        ]);
                    } else {
                        if ($stock == 0) {
                            return response()->json(['result' => false, 'message' => translate("No item is available for") . " {$product->name}{$variant_string}," . translate("remove this from cart")], 200);
                        } else {
                            return response()->json(['result' => false, 'message' => translate("Only") . " {$stock} " . translate("item(s) are available for") . " {$product->name}{$variant_string}"], 200);
                        }
                    }

                    $i++;
                } elseif ($cart_item->trendyol == 1) {
                    $accessToken = trendyol_search_account_login();
                    $product = trendyol_cart_product_details($accessToken, $cart_item->product_id, $cart_item->urunNo);
                    if ($product['min_qty'] > $cart_quantities[$i]) {
                        return response()->json(['result' => false, 'message' => translate("Minimum") . " {$product['min_qty']} " . translate("item(s) should be ordered for") . " {$product['name']}"], 200);
                    }
                    $stock = $product['stock'];
                    $variant_string = $cart_item->variation != null && $cart_item->variation != "" ? " ($cart_item->variation)" : "";
                    if ($stock >= $cart_quantities[$i] && $product['digital'] == 0) {
                        $cart_item->update([
                            'quantity' => $cart_quantities[$i]
                        ]);
                    } else {
                        if ($stock == 0) {
                            return response()->json(['result' => false, 'message' => translate("No item is available for") . " {$product['name']}{$variant_string}," . translate("remove this from cart")], 200);
                        } else {
                            return response()->json(['result' => false, 'message' => translate("Only") . " {$stock} " . translate("item(s) are available for") . " {$product['name']}{$variant_string}"], 200);
                        }
                    }

                    $i++;
                } elseif ($cart_item->provider_id != null) {
                    $product = $providers_products[$cart_id];
                    if ($product['min_qty'] > $cart_quantities[$i]) {
                        return response()->json(['result' => false, 'message' => translate("Minimum") . " {$product['min_qty']} " . translate("item(s) should be ordered for") . " {$product['name']}"], 200);
                    }
                    $stock = $product['stock'];
                    $variant_string = $cart_item->variation != null && $cart_item->variation != "" ? " ($cart_item->variation)" : "";
                    if ($stock >= $cart_quantities[$i]) {
                        if ($product['digital'] == 0) {
                            $cart_item->update([
                                'quantity' => $cart_quantities[$i]
                            ]);
                        }
                    } else {
                        if ($stock == 0) {
                            return response()->json(['result' => false, 'message' => translate("No item is available for") . " {$product['name']}{$variant_string}," . translate("remove this from cart")], 200);
                        } else {
                            return response()->json(['result' => false, 'message' => translate("Only") . " {$stock} " . translate("item(s) are available for") . " {$product['name']}{$variant_string}"], 200);
                        }
                    }

                    $i++;
                }
            }
            return response()->json(['result' => true, 'message' => translate('Cart updated')], 200);
        } else {
            return response()->json(['result' => false, 'message' => translate('Cart is empty')], 200);
        }
    }

    public function destroy(string $id)
    {
        Cart::destroy($id);
        return response()->json(['result' => true, 'message' => translate('Product is successfully removed from your cart')], 200);
    }
}
