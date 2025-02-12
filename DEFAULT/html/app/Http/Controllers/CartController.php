<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\IdRequest;
use App\Http\Requests\Cart\IndexRequest;
use App\Http\Requests\Cart\ShowCartModalRequest;
use App\Http\Requests\Cart\UpdateQuantityRequest;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Provider;
use Auth;
use App\Utility\CartUtility;
use PDO;
use Session;

class CartController extends Controller
{
    public function index(IndexRequest $request)
    {
        $providerProducts = [];
        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            if ($request->session()->get('temp_user_id')) {
                Cart::where('temp_user_id', $request->session()->get('temp_user_id'))
                    ->update(
                        [
                            'user_id' => $user_id,
                            'temp_user_id' => null
                        ]
                    );

                Session::forget('temp_user_id');
            }
            $carts = Cart::where('user_id', $user_id)->get();
            $providersProductsId =  Cart::where('user_id', $user_id)
                ->whereNotNull('provider_id')
                ->get(['id', 'provider_id', 'product_id'])
                ->groupBy('provider_id')
                ->map(fn($group) => $group->pluck('product_id', 'id'))
                ->toArray() ?? [];
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            // $carts = Cart::where('temp_user_id', $temp_user_id)->get();
            $carts = ($temp_user_id != null) ? Cart::where('temp_user_id', $temp_user_id)->get() : [];
            $providersProductsId = ($temp_user_id != null) ? Cart::where('user_id', $temp_user_id)
                ->whereNotNull('provider_id')
                ->get(['id', 'provider_id', 'product_id'])
                ->groupBy('provider_id')
                ->map(fn($group) => $group->pluck('product_id', 'id'))
                ->toArray() : [];
        }

        if (count($providersProductsId) > 0) {
            foreach ($providersProductsId as $key => $providerProductsId) {
                $provider = Provider::find($key);
                if ($provider) {
                    $providerProducts += $provider->service()->productsDetails($providerProductsId);
                }
            }
        }
        return view('frontend.view_cart', compact('carts', 'providerProducts'));
    }

    public function showCartModal(ShowCartModalRequest $request)
    {

        if (isset($request->trendyol) && $request->trendyol == 1) {
            $propreties = [];
            $accessToken = trendyol_search_account_login();
            $product = trendyol_product_details($accessToken, $request->id, $request->urunNo);
            if (!empty($product)) {
                $propreties = trendyol_product_propreties($accessToken, $product['urunGrupNo']);
                return view('frontend.' . get_setting('homepage_select') . '.partials.addToCartTrendyol', compact('product', 'propreties'));
            } else {
                $null = true;
                return view('frontend.' . get_setting('homepage_select') . '.partials.addToCartTrendyol', compact('null'));
            }
        } elseif (isset($request->provider)) {
            $provider = Provider::find($request->provider);
            $product = $provider->Service()->productDetails($request->id);
            if (!empty($product)) {
                return view('frontend.' . get_setting('homepage_select') . '.partials.addToCartProvider', compact('product'));
            } else {
                $null = true;
                return view('frontend.' . get_setting('homepage_select') . '.partials.addToCartProvider', compact('null'));
            }
        } else {
            $product = Product::find($request->id);
            return view('frontend.' . get_setting('homepage_select') . '.partials.addToCart', compact('product'));
        }
    }

    public function showCartModalAuction(ShowCartModalRequest $request)
    {
        $product = Product::find($request->id);
        return view('auction.frontend.addToCartAuction', compact('product'));
    }

    public function addToCart(AddToCartRequest $request)
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
            if ($quantity < $product->min_qty) {
                return array(
                    'status' => 0,
                    'cart_count' => count($carts),
                    'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.minQtyNotSatisfied', ['min_qty' => $product->min_qty])->render(),
                    'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
                );
            }
            $str = null;
            if (isset($product->choice_options) && count($product->choice_options) > 0) {
                //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
                foreach ($product->choice_options as $choice) {
                    if ($str != null) {
                        $str .= '-' . str_replace(' ', '', $request['attribute_id_' . $choice['id']]);
                    } else {
                        $str .= str_replace(' ', '', $request['attribute_id_' . $choice['id']]);
                    }
                }
            }
            $price = $product->getAttributes()['unit_price'];
            $trendyol_price = $productArray['base_price'];
            $tax = $product->tax;
            $product_stock = $productArray['stock'];
            if ($product_stock <= 0) {
                return array(
                    'status' => 0,
                    'cart_count' => 0,
                    'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.outOfStockCart')->render(),
                    'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
                );
            }
            $cart = Cart::firstOrNew([
                'variation' => $str,
                'user_id' => auth()->user()->id,
                'product_id' =>  $product->id,
                'trendyol' => 1,
                'urunNo' => $productArray['urunNo'],
                'shipping_cost' =>  env('TRENDYOL_SHIPPING_COST'),
                'trendyol_shop_id' => $productArray['shopId']
            ]);
            if ($cart->exists && $product->digital == 0) {
                if ($product->auction_product == 1 && ($cart->product_id == $product->id)) {
                    return array(
                        'status' => 0,
                        'cart_count' => count($carts),
                        'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.auctionProductAlredayAddedCart')->render(),
                        'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
                    );
                }
                if ($product_stock < $cart->quantity + $request['quantity']) {
                    return array(
                        'status' => 0,
                        'cart_count' => count($carts),
                        'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.outOfStockCart')->render(),
                        'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
                    );
                }
                $quantity = $cart->quantity + $request['quantity'];
            }


            CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity, $trendyol_price);

            $carts = Cart::where('user_id', auth()->user()->id)->get();
            return array(
                'status' => 1,
                'cart_count' => count($carts),
                'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.addedToCartTrendyol', compact('product', 'cart'))->render(),
                'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
            );
        } elseif (isset($request->provider)) {
            $provider = Provider::findOrFail($request->provider);
            $product = $provider->Service()->productDetails($request->id);
            $carts = array();
            $quantity = $request['quantity'];
            if ($quantity < $product['min_qty']) {
                return array(
                    'status' => 0,
                    'cart_count' => count($carts),
                    'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.minQtyNotSatisfied', ['min_qty' => $product->min_qty])->render(),
                    'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
                );
            }
            $str = null;
            if (isset($product['choice_options']) && count($product['choice_options']) > 0) {
                //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
                foreach ($product['choice_options'] as $choice) {
                    if ($str != null) {
                        $str .= '-' . str_replace(' ', '', $request['attribute_id_' . $choice['id']]);
                    } else {
                        $str .= str_replace(' ', '', $request['attribute_id_' . $choice['id']]);
                    }
                }
            }
            $price = $product['new_price'];
            $base_price = $product['base_price'];
            $tax = $product['tax'] ?? 0;
            $product_stock = $product['stock'];
            if ($product_stock <= 0) {
                return array(
                    'status' => 0,
                    'cart_count' => 0,
                    'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.outOfStockCart')->render(),
                    'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
                );
            }
            $cart = Cart::firstOrNew([
                'variation' => $str,
                'user_id' => auth()->user()->id,
                'product_id' =>  $product['id'],
                'provider_id' => $product['provider_id'],
                'shipping_cost' =>  0
            ]);
            if ($cart->exists && $product['digital'] == 0) {
                if ($product->auction_product == 1 && ($cart->product_id == $product->id)) {
                    return array(
                        'status' => 0,
                        'cart_count' => count($carts),
                        'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.auctionProductAlredayAddedCart')->render(),
                        'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
                    );
                }
                if ($product_stock < $cart->quantity + $request['quantity']) {
                    return array(
                        'status' => 0,
                        'cart_count' => count($carts),
                        'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.outOfStockCart')->render(),
                        'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
                    );
                }
                $quantity = $cart->quantity + $request['quantity'];
            }
            CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity, $base_price);

            $carts = Cart::where('user_id', auth()->user()->id)->count();
            return array(
                'status' => 1,
                'cart_count' => $carts,
                'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.addedToCartProvider', compact('product', 'cart'))->render(),
                'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
            );
        } else {
            $product = Product::find($request->id);
        }

        $carts = array();
        if ($check_auction_in_cart && $product->auction_product == 0) {
            return array(
                'status' => 0,
                'cart_count' => count($carts),
                'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.removeAuctionProductFromCart')->render(),
                'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
            );
        }

        $quantity = $request['quantity'];

        if ($quantity < $product->min_qty) {
            return array(
                'status' => 0,
                'cart_count' => count($carts),
                'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.minQtyNotSatisfied', ['min_qty' => $product->min_qty])->render(),
                'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
            );
        }

        //check the color enabled or disabled for the product
        $str = CartUtility::create_cart_variant($product, $request->all());
        $product_stock = $product->stocks->where('variant', $str)->first();
        if ($product_stock->qty <= 0) {
            return array(
                'status' => 0,
                'cart_count' => 0,
                'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.outOfStockCart')->render(),
                'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
            );
        }

        $cart = Cart::firstOrNew([
            'variation' => $str,
            'user_id' => auth()->user()->id,
            'product_id' => $request['id']
        ]);

        if ($cart->exists && $product->digital == 0) {
            if ($product->auction_product == 1 && ($cart->product_id == $product->id)) {
                return array(
                    'status' => 0,
                    'cart_count' => count($carts),
                    'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.auctionProductAlredayAddedCart')->render(),
                    'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
                );
            }
            if ($product_stock->qty < $cart->quantity + $request['quantity']) {
                return array(
                    'status' => 0,
                    'cart_count' => count($carts),
                    'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.outOfStockCart')->render(),
                    'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
                );
            }
            $quantity = $cart->quantity + $request['quantity'];
        }

        $price = CartUtility::get_price($product, $product_stock, $request->quantity);
        $tax = CartUtility::tax_calculation($product, $price);

        CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity);

        $carts = Cart::where('user_id', auth()->user()->id)->get();
        return array(
            'status' => 1,
            'cart_count' => count($carts),
            'modal_view' => view('frontend.' . get_setting('homepage_select') . '.partials.addedToCart', compact('product', 'cart'))->render(),
            'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
        );
    }

    //removes from Cart
    public function removeFromCart(IdRequest $request)
    {
        $providerProducts = [];
        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            if ($request->session()->get('temp_user_id')) {
                Cart::where('temp_user_id', $request->session()->get('temp_user_id'))
                    ->update(
                        [
                            'user_id' => $user_id,
                            'temp_user_id' => null
                        ]
                    );

                Session::forget('temp_user_id');
            }
            $carts = Cart::where('user_id', $user_id)->get();
            $providersProductsId =  Cart::where('user_id', $user_id)
                ->whereNotNull('provider_id')
                ->get(['id', 'provider_id', 'product_id'])
                ->groupBy('provider_id')
                ->map(fn($group) => $group->pluck('product_id', 'id'))
                ->toArray() ?? [];
            $user_id = Auth::user()->id;
            $cart = Cart::where('user_id', $user_id)->find($request->id);
            if ($cart) {
                $cart->delete();
            }
            $carts = Cart::where('user_id', $user_id)->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $cart = Cart::where('temp_user_id', $temp_user_id)->find($request->id);
            if ($cart) {
                $cart->delete();
            }
            $carts = Cart::where('temp_user_id', $temp_user_id)->get();
        }

        if (count($providersProductsId) > 0) {
            foreach ($providersProductsId as $key => $providerProductsId) {
                $provider = Provider::find($key);
                if ($provider) {
                    $providerProducts += $provider->service()->productsDetails($providerProductsId);
                }
            }
        }
        return array(
            'cart_count' => count($carts),
            'cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart_details', compact('carts' , 'providerProducts'))->render(),
            'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
        );
    }

    //updated the quantity for a cart item
    public function updateQuantity(UpdateQuantityRequest $request)
    {
        $providerProducts = [];
        $trendyol_price = 0;
        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $cartItem = Cart::where('user_id', $user_id)->findOrFail($request->id);
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $cartItem = Cart::where('temp_user_id', $temp_user_id)->findOrFail($request->id);
        }

        if ($cartItem['id'] == $request->id && $cartItem['digital'] == 0) {

            if ($cartItem['trendyol'] == 0) {
                $product = Product::find($cartItem['product_id']);
                $product_stock = $product->stocks->where('variant', $cartItem['variation'])->first();
                $quantity = $product_stock->qty;
                $price = $product_stock->price;
                $trendyol_price = $cartItem['trendyol_price'];

                //discount calculation
                $discount_applicable = false;

                if ($product->discount_start_date == null) {
                    $discount_applicable = true;
                } elseif (
                    strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                    strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
                ) {
                    $discount_applicable = true;
                }

                if ($discount_applicable) {
                    if ($product->discount_type == 'percent') {
                        $price -= ($price * $product->discount) / 100;
                    } elseif ($product->discount_type == 'amount') {
                        $price -= $product->discount;
                    }
                }

                if ($quantity >= $request->quantity) {
                    if ($request->quantity >= $product->min_qty) {
                        $cartItem['quantity'] = $request->quantity;
                    }
                }

                if ($product->wholesale_product) {
                    $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $request->quantity)->where('max_qty', '>=', $request->quantity)->first();
                    if ($wholesalePrice) {
                        $price = $wholesalePrice->price;
                    }
                }
            } elseif ($cartItem['trendyol'] == 1) {
                $accaessToken = trendyol_account_login();
                $product = trendyol_product_details($accaessToken, $cartItem['product_id'], $cartItem['urunNo']);
                $product_stock = $product['stock'];
                $quantity = $product_stock;
                $price = $product['new_price'];
                $trendyol_price = $product['base_price'];
                if ($quantity >= $request->quantity) {
                    if ($request->quantity >= $product['min_qty']) {
                        $cartItem['quantity'] = $request->quantity;
                    }
                }
            } elseif ($cartItem['provider_id'] != null) {
                $provider = Provider::findOrFail($cartItem['provider_id']);
                $product = $provider->Service()->productDetails($cartItem['product_id']);
                $product_stock = $product['stock'];
                $quantity = $product_stock;
                $price = $product['new_price'];
                $trendyol_price = $product['base_price'];
                if ($quantity >= $request->quantity && $product['digital'] != 1) {
                    if ($request->quantity >= $product['min_qty']) {
                        $cartItem['quantity'] = $request->quantity;
                    }
                }
            }

            $cartItem['price'] = $price;
            $cartItem['trendyol_price'] = $trendyol_price;
            $cartItem->save();
        }

        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $carts = Cart::where('user_id', $user_id)->get();
            $providersProductsId =  Cart::where('user_id', $user_id)
                ->whereNotNull('provider_id')
                ->get(['id', 'provider_id', 'product_id'])
                ->groupBy('provider_id')
                ->map(fn($group) => $group->pluck('product_id', 'id'))
                ->toArray() ?? [];
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = Cart::where('temp_user_id', $temp_user_id)->get();
            $providersProductsId = ($temp_user_id != null) ? Cart::where('user_id', $temp_user_id)
                ->whereNotNull('provider_id')
                ->get(['id', 'provider_id', 'product_id'])
                ->groupBy('provider_id')
                ->map(fn($group) => $group->pluck('product_id', 'id'))
                ->toArray() : [];
        }

        if (count($providersProductsId) > 0) {
            foreach ($providersProductsId as $key => $providerProductsId) {
                $provider = Provider::find($key);
                if ($provider) {
                    $providerProducts += $provider->service()->productsDetails($providerProductsId);
                }
            }
        }
        return array(
            'cart_count' => count($carts),
            'cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart_details', compact('carts', 'providerProducts'))->render(),
            'nav_cart_view' => view('frontend.' . get_setting('homepage_select') . '.partials.cart')->render(),
        );
    }
}
