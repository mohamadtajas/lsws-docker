<?php

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Cart;
use App\Models\City;
use App\Models\Shop;
use App\Models\User;
use App\Models\Addon;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Seller;
use App\Models\Upload;
use App\Models\Wallet;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\Country;
use App\Models\Product;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Wishlist;
use App\Models\Attribute;
use App\Models\ClubPoint;
use App\Models\FlashDeal;
use App\Models\CouponUsage;
use App\Models\DeliveryBoy;
use App\Models\OrderDetail;
use App\Models\PickupPoint;
use App\Models\Translation;
use App\Models\BlogCategory;
use App\Models\Conversation;
use App\Models\FollowSeller;
use App\Models\ProductStock;
use App\Models\CombinedOrder;
use App\Models\SellerPackage;
use App\Models\AffiliateConfig;
use App\Models\AffiliateOption;
use App\Models\BusinessSetting;
use App\Models\CustomerPackage;
use App\Models\CustomerProduct;
use App\Models\TrendyolAccount;
use App\Utility\SendSMSUtility;
use App\Models\AuctionProductBid;
use App\Models\ManualPaymentMethod;
use App\Models\SellerPackagePayment;
use App\Utility\NotificationUtility;
use App\Http\Resources\V2\CarrierCollection;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\ClubPointController;
use App\Http\Controllers\CommissionController;
use AizPackages\ColorCodeConverter\Services\ColorCodeConverter;
use App\Models\FlashDealProduct;
use App\Models\Provider;
use App\Models\UserCoupon;
use Illuminate\Pagination\LengthAwarePaginator;

//sensSMS function for OTP
if (!function_exists('sendSMS')) {
    function sendSMS($to, $from, $text, $template_id)
    {
        return SendSMSUtility::sendSMS($to, $from, $text, $template_id);
    }
}

//highlights the selected navigation on admin panel
if (!function_exists('areActiveRoutes')) {
    function areActiveRoutes(array $routes, $output = "active")
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route && (url()->current() != url('/admin/website/custom-pages/edit/home'))) return $output;
        }
    }
}

//highlights the selected navigation on frontend
if (!function_exists('areActiveRoutesHome')) {
    function areActiveRoutesHome(array $routes, $output = "active")
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route) return $output;
        }
    }
}

//highlights the selected navigation on frontend
if (!function_exists('default_language')) {
    function default_language()
    {
        return env("DEFAULT_LANGUAGE");
    }
}

/**
 * Save JSON File
 * @return Response
 */
if (!function_exists('convert_to_usd')) {
    function convert_to_usd($amount)
    {
        $currency = Currency::find(get_setting('system_default_currency'));
        return (floatval($amount) / floatval($currency->exchange_rate)) * Currency::where('code', 'USD')->first()->exchange_rate;
    }
}

if (!function_exists('convert_to_kes')) {
    function convert_to_kes($amount)
    {
        $currency = Currency::find(get_setting('system_default_currency'));
        return (floatval($amount) / floatval($currency->exchange_rate)) * Currency::where('code', 'KES')->first()->exchange_rate;
    }
}

if (!function_exists('convert_to_default_currency')) {
    function convert_to_default_currency($amount, $source_currency_code)
    {
        static $exchangeRates = null;

        if ($exchangeRates === null) {
            $exchangeRates = Currency::where('status', 1)->pluck('exchange_rate', 'code')->toArray();
            $exchangeRates['default'] = Currency::find(get_setting('system_default_currency'))->exchange_rate;
        }

        $sourceRate = $exchangeRates[$source_currency_code] ?? 1;
        $defaultRate = $exchangeRates['default'] ?? 1;

        return ($amount / $sourceRate) * $defaultRate;
    }
}

// get all active countries
if (!function_exists('get_active_countries')) {
    function get_active_countries()
    {
        $country_query = Country::query();
        return $country_query->isEnabled()->get();
    }
}

//filter products based on vendor activation system
if (!function_exists('filter_products')) {
    function filter_products($products)
    {

        $products = $products->where('published', '1')->where('auction_product', 0)->where('approved', '1');

        if (!addon_is_activated('wholesale')) {
            $products = $products->where('wholesale_product', 0);
        }
        $verified_sellers = verified_sellers_id();
        // $unbanned_sellers_id = unbanned_sellers_id();
        if (get_setting('vendor_system_activation') == 1) {
            // return $products->where(function ($p) use ($verified_sellers, $unbanned_sellers_id) {
            //     $p->where('added_by', 'admin')->orWhere(function ($q) use ($verified_sellers, $unbanned_sellers_id) {
            //         $q->whereIn('user_id', $verified_sellers)->whereIn('user_id', $unbanned_sellers_id);
            //     });
            // });
            return $products->where(function ($p) use ($verified_sellers) {
                $p->where('added_by', 'admin')->orWhere(function ($q) use ($verified_sellers) {
                    $q->whereIn('user_id', $verified_sellers);
                });
            });
        } else {
            return $products->where('added_by', 'admin');
        }
    }
}

//cache products based on category
if (!function_exists('get_cached_products')) {
    function get_cached_products($category_id = null)
    {
        // Retrieve products from request's attributes if already cached
        $request = Request::instance();
        $cache_key = 'products-category-' . $category_id;
        $cached_products = $request->attributes->get($cache_key, null);

        // Fetch products if not already cached
        if ($cached_products === null) {
            $cached_products = Cache::remember($cache_key, 86400, function () use ($category_id) {
                return filter_products(Product::where('category_id', $category_id))
                    ->latest()
                    ->take(5)
                    ->get();
            });

            // Store products in request attributes for reuse within the request
            $request->attributes->set($cache_key, $cached_products);
        }

        return $cached_products;
    }
}

if (!function_exists('verified_sellers_id')) {
    function verified_sellers_id()
    {
        // Retrieve verified sellers from request's attributes if already cached
        $request = Request::instance();
        $cache_key = 'verified_sellers_id';
        $verified_sellers = $request->attributes->get($cache_key, null);

        // Fetch verified sellers if not already cached
        if ($verified_sellers === null) {
            $verified_sellers = Cache::remember($cache_key, 86400, function () {
                return Shop::where('verification_status', 1)->pluck('user_id')->toArray();
            });

            // Store verified sellers in request attributes for reuse within the request
            $request->attributes->set($cache_key, $verified_sellers);
        }

        return $verified_sellers;
    }
}

// if (!function_exists('unbanned_sellers_id')) {
//     function unbanned_sellers_id()
//     {
//         return Cache::rememberForever('unbanned_sellers_id', function () {
//             return App\Models\User::where('user_type', 'seller')->where('banned', 0)->pluck('id')->toArray();
//         });
//     }
// }

if (!function_exists('get_system_default_currency')) {
    function get_system_default_currency()
    {
        // Retrieve system default currency from request's attributes if already cached
        $request = Request::instance();
        $cache_key = 'system_default_currency';
        $currency = $request->attributes->get($cache_key, null);

        // Fetch system default currency if not already cached
        if ($currency === null) {
            // Get the default currency ID from settings
            $currency_id = get_setting('system_default_currency');

            // Fetch the currency from the database and cache it
            $currency = Cache::remember($cache_key, 86400, function () use ($currency_id) {
                return Currency::findOrFail($currency_id);
            });

            // Store currency in request attributes for reuse within the request
            $request->attributes->set($cache_key, $currency);
        }

        return $currency;
    }
}

//converts currency to home default currency
if (!function_exists('convert_price')) {
    function convert_price($price)
    {
        if (Session::has('currency_code') && (Session::get('currency_code') != get_system_default_currency()->code)) {
            $price = floatval($price) / floatval(get_system_default_currency()->exchange_rate);
            $price = floatval($price) * floatval(Session::get('currency_exchange_rate'));
        }

        if (
            request()->header('Currency-Code') &&
            request()->header('Currency-Code') != get_system_default_currency()->code
        ) {
            $price = floatval($price) / floatval(get_system_default_currency()->exchange_rate);
            $price = floatval($price) * floatval(request()->header('Currency-Exchange-Rate'));
        }
        return $price;
    }
}

//gets currency symbol
if (!function_exists('currency_symbol')) {
    function currency_symbol()
    {
        if (Session::has('currency_symbol')) {
            return Session::get('currency_symbol');
        }
        if (request()->header('Currency-Code')) {
            return request()->header('Currency-Code');
        }
        return get_system_default_currency()->symbol;
    }
}

//formats currency
if (!function_exists('format_price')) {
    function format_price($price, $isMinimize = false)
    {
        if (get_setting('decimal_separator') == 1) {
            $fomated_price = number_format($price, get_setting('no_of_decimals'));
        } else {
            $fomated_price = number_format($price, get_setting('no_of_decimals'), ',', '.');
        }


        // Minimize the price
        if ($isMinimize) {
            $temp = number_format($price / 1000000000, get_setting('no_of_decimals'), ".", "");

            if ($temp >= 1) {
                $fomated_price = $temp . "B";
            } else {
                $temp = number_format($price / 1000000, get_setting('no_of_decimals'), ".", "");
                if ($temp >= 1) {
                    $fomated_price = $temp . "M";
                }
            }
        }

        if (get_setting('symbol_format') == 1) {
            return currency_symbol() . $fomated_price;
        } else if (get_setting('symbol_format') == 3) {
            return currency_symbol() . ' ' . $fomated_price;
        } else if (get_setting('symbol_format') == 4) {
            return $fomated_price . ' ' . currency_symbol();
        }
        return $fomated_price . currency_symbol();
    }
}

//formats price to home default price with convertion
if (!function_exists('single_price')) {
    function single_price($price)
    {
        return format_price(convert_price($price));
    }
}

if (!function_exists('discount_in_percentage')) {
    function discount_in_percentage($product)
    {
        $base = home_base_price($product, false);
        $reduced = home_discounted_base_price($product, false);
        $discount = $base - $reduced;
        $dp = ($discount * 100) / ($base > 0 ? $base : 1);
        return round($dp);
    }
}

//Shows Price on page based on carts
if (!function_exists('cart_product_price')) {
    function cart_product_price($cart_product, $product, $formatted = true, $tax = true)
    {
        if ($product->auction_product == 0) {
            $str = '';
            if ($cart_product['variation'] != null) {
                $str = $cart_product['variation'];
            }
            $price = 0;
            $product_stock = $product->stocks->where('variant', $str)->first();
            if ($product_stock) {
                $price = $product_stock->price;
            }

            if ($product->wholesale_product) {
                $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $cart_product['quantity'])->where('max_qty', '>=', $cart_product['quantity'])->first();
                if ($wholesalePrice) {
                    $price = $wholesalePrice->price;
                }
            }

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
        } else {
            $price = $product->bids->max('amount');
        }

        //calculation of taxes
        if ($tax) {
            $taxAmount = 0;
            foreach ($product->taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $taxAmount += ($price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $taxAmount += $product_tax->tax;
                }
            }
            $price += $taxAmount;
        }

        if ($formatted) {
            return format_price(convert_price($price));
        } else {
            return $price;
        }
    }
}

if (!function_exists('cart_product_tax')) {
    function cart_product_tax($cart_product, $product, $formatted = true)
    {
        $str = '';
        if ($cart_product['variation'] != null) {
            $str = $cart_product['variation'];
        }
        $product_stock = $product->stocks->where('variant', $str)->first();
        $price = $product_stock->price;

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

        //calculation of taxes
        $tax = 0;
        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }

        if ($formatted) {
            return format_price(convert_price($tax));
        } else {
            return $tax;
        }
    }
}

if (!function_exists('cart_product_discount')) {
    function cart_product_discount($cart_product, $product, $formatted = false)
    {
        $str = '';
        if ($cart_product['variation'] != null) {
            $str = $cart_product['variation'];
        }
        $product_stock = $product->stocks->where('variant', $str)->first();
        $price = $product_stock->price;

        //discount calculation
        $discount_applicable = false;
        $discount = 0;

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
                $discount = ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $discount = $product->discount;
            }
        }

        if ($formatted) {
            return format_price(convert_price($discount));
        } else {
            return $discount;
        }
    }
}

// all discount
if (!function_exists('carts_product_discount')) {
    function carts_product_discount($cart_products, $formatted = false)
    {
        $discount = 0;
        foreach ($cart_products as $key => $cart_product) {
            $str = '';
            $product = \App\Models\Product::find($cart_product['product_id']);
            if ($cart_product['variation'] != null) {
                $str = $cart_product['variation'];
            }
            $product_stock = $product->stocks->where('variant', $str)->first();
            $price = $product_stock->price;

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
                    $discount += ($price * $product->discount) / 100;
                } elseif ($product->discount_type == 'amount') {
                    $discount += $product->discount;
                }
            }
        }

        if ($formatted) {
            return format_price(convert_price($discount));
        } else {
            return $discount;
        }
    }
}

// carts coupon discount
if (!function_exists('carts_coupon_discount')) {
    function carts_coupon_discount($code, $formatted = false)
    {
        $coupon = Coupon::where('code', $code)->first();
        $coupon_discount = 0;
        $accaessToken = trendyol_account_login();
        if ($coupon != null) {
            if (strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date) {
                if (CouponUsage::where('user_id', Auth::user()->id)->where('coupon_id', $coupon->id)->first() == null) {
                    $coupon_details = json_decode($coupon->details);
                    $carts = Cart::where('user_id', Auth::user()->id)
                        ->where('owner_id', $coupon->user_id)
                        ->get();
                    if ($coupon->type == 'cart_base') {
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
                                $subtotal += (float) str_replace(',', '', $productArray['new_price']) * $cartItem['quantity'];
                                $shipping += $cartItem['shipping_cost'];
                            }
                        }
                        $sum = $subtotal + $tax + $shipping;
                        if ($sum >= $coupon_details->min_buy) {
                            if ($coupon->discount_type == 'percent') {
                                $coupon_discount = ($sum * $coupon->discount) / 100;
                                if ($coupon_discount > $coupon_details->max_discount) {
                                    $coupon_discount = $coupon_details->max_discount;
                                }
                            } elseif ($coupon->discount_type == 'amount') {
                                $coupon_discount = $coupon->discount;
                            }
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
                }
            }
            if ($coupon_discount > 0) {
                Cart::where('user_id', Auth::user()->id)
                    ->where('owner_id', $coupon->user_id)
                    ->update(
                        [
                            'discount' => $coupon_discount / count($carts),
                        ]
                    );
            } else {
                Cart::where('user_id', Auth::user()->id)
                    ->where('owner_id', $coupon->user_id)
                    ->update(
                        [
                            'discount' => 0,
                            'coupon_code' => null,
                        ]
                    );
            }
        }
        if ($formatted) {
            return format_price(convert_price($coupon_discount));
        } else {
            return $coupon_discount;
        }
    }
}


//Shows Price on page based on low to high
if (!function_exists('home_price')) {
    function home_price($product, $formatted = true)
    {
        $lowest_price = $product->unit_price;
        $highest_price = $product->unit_price;

        if ($product->variant_product) {
            foreach ($product->stocks as $key => $stock) {
                if ($lowest_price > $stock->price) {
                    $lowest_price = $stock->price;
                }
                if ($highest_price < $stock->price) {
                    $highest_price = $stock->price;
                }
            }
        }

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $lowest_price += ($lowest_price * $product_tax->tax) / 100;
                $highest_price += ($highest_price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $lowest_price += $product_tax->tax;
                $highest_price += $product_tax->tax;
            }
        }

        if ($formatted) {
            if ($lowest_price == $highest_price) {
                return format_price(convert_price($lowest_price));
            } else {
                return format_price(convert_price($lowest_price)) . ' - ' . format_price(convert_price($highest_price));
            }
        } else {
            return $lowest_price . ' - ' . $highest_price;
        }
    }
}

//Shows Price on page based on low to high with discount
if (!function_exists('home_discounted_price')) {
    function home_discounted_price($product, $formatted = true)
    {
        $lowest_price = $product->unit_price;
        $highest_price = $product->unit_price;

        if ($product->variant_product) {
            foreach ($product->stocks as $key => $stock) {
                if ($lowest_price > $stock->price) {
                    $lowest_price = $stock->price;
                }
                if ($highest_price < $stock->price) {
                    $highest_price = $stock->price;
                }
            }
        }

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
                $lowest_price -= ($lowest_price * $product->discount) / 100;
                $highest_price -= ($highest_price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $lowest_price -= $product->discount;
                $highest_price -= $product->discount;
            }
        }

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $lowest_price += ($lowest_price * $product_tax->tax) / 100;
                $highest_price += ($highest_price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $lowest_price += $product_tax->tax;
                $highest_price += $product_tax->tax;
            }
        }

        if ($formatted) {
            if ($lowest_price == $highest_price) {
                return format_price(convert_price($lowest_price));
            } else {
                return format_price(convert_price($lowest_price)) . ' - ' . format_price(convert_price($highest_price));
            }
        } else {
            return $lowest_price . ' - ' . $highest_price;
        }
    }
}

//Shows Base Price
if (!function_exists('home_base_price_by_stock_id')) {
    function home_base_price_by_stock_id($id)
    {
        $product_stock = ProductStock::findOrFail($id);
        $price = $product_stock->price;
        $tax = 0;

        foreach ($product_stock->product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;
        return format_price(convert_price($price));
    }
}


if (!function_exists('home_base_price')) {
    function home_base_price($product, $formatted = true)
    {
        $price = $product->unit_price;
        $tax = 0;

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;
        return $formatted ? format_price(convert_price($price)) : convert_price($price);
    }
}

//Shows Base Price with discount
if (!function_exists('home_discounted_base_price_by_stock_id')) {
    function home_discounted_base_price_by_stock_id($id)
    {
        $product_stock = ProductStock::findOrFail($id);
        $product = $product_stock->product;
        $price = $product_stock->price;
        $tax = 0;

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

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;

        return format_price(convert_price($price));
    }
}


//Shows Base Price with discount
if (!function_exists('home_discounted_base_price')) {
    function home_discounted_base_price($product, $formatted = true)
    {
        $price = $product->unit_price;
        $tax = 0;

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

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }
        $price += $tax;


        return $formatted ? format_price(convert_price($price)) : convert_price($price);
    }
}

if (!function_exists('renderStarRating')) {
    function renderStarRating($rating, $maxRating = 5)
    {
        $fullStar = "<i class = 'las la-star active'></i>";
        $halfStar = "<i class = 'las la-star half'></i>";
        $emptyStar = "<i class = 'las la-star'></i>";
        $rating = $rating <= $maxRating ? $rating : $maxRating;

        $fullStarCount = (int)$rating;
        $halfStarCount = ceil($rating) - $fullStarCount;
        $emptyStarCount = $maxRating - $fullStarCount - $halfStarCount;

        $html = str_repeat($fullStar, $fullStarCount);
        $html .= str_repeat($halfStar, $halfStarCount);
        $html .= str_repeat($emptyStar, $emptyStarCount);
        echo $html;
    }
}

function translate($key, $lang = null, $addslashes = false)
{
    $lang_key = preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($key)));
    $translation_value =  __($lang_key);
    $translation_value = $translation_value !== $lang_key ? $translation_value : $key;
    return $addslashes ? addslashes(trim($translation_value)) : trim($translation_value);
}

function remove_invalid_charcaters($str)
{
    $str = str_ireplace(array("\\"), '', $str);
    return str_ireplace(array('"'), '\"', $str);
}

if (!function_exists('translation_tables')) {
    function translation_tables($uniqueIdentifier)
    {
        $noTableAddons =  ['african_pg', 'paytm', 'pos_system'];
        if (!in_array($uniqueIdentifier, $noTableAddons)) {
            $addons = [];
            $addons['affiliate'] = ['affiliate_options', 'affiliate_configs', 'affiliate_users', 'affiliate_payments', 'affiliate_withdraw_requests', 'affiliate_logs', 'affiliate_stats'];
            $addons['auction'] = ['auction_product_bids'];
            $addons['club_point'] = ['club_points', 'club_point_details'];
            $addons['delivery_boy'] = ['delivery_boys', 'delivery_histories', 'delivery_boy_payments', 'delivery_boy_collections'];
            $addons['offline_payment'] = ['manual_payment_methods'];
            $addons['otp_system'] = ['otp_configurations', 'sms_templates'];
            $addons['refund_request'] = ['refund_requests'];
            $addons['seller_subscription'] = ['seller_packages', 'seller_package_translations', 'seller_package_payments'];
            $addons['wholesale'] = ['wholesale_prices'];

            foreach ($addons as $key => $addon_tables) {
                if ($key == $uniqueIdentifier) {
                    foreach ($addon_tables as $table) {
                        Schema::dropIfExists($table);
                    }
                }
            }
        }
    }
}


function getShippingCost($carts, $index, $carrier = '', $trendyolProduct = [], $providerProduct = [])
{
    $phisical = [];
    $shipping_type = get_setting('shipping_type');
    $admin_products = array();
    $seller_products = array();
    $admin_product_total_weight = 0;
    $admin_product_total_price = 0;
    $seller_product_total_weight = array();
    $seller_product_total_price = array();

    $cartItem = $carts[$index];
    if ($cartItem['trendyol'] == 0 && $cartItem['provider_id'] == null) {
        $product = Product::find($cartItem['product_id']);
    } elseif ($cartItem['trendyol'] == 1) {
        $product = new Product($trendyolProduct);
    } elseif ($cartItem['provider_id'] != null) {
        $product = $providerProduct;
    }

    if ($product['digital'] == 1) {
        return 0;
    }


    foreach ($carts as $key => $cart_item) {
        if ($cart_item['trendyol'] == 0 && $cart_item['provider_id'] == null) {
            $item_product = Product::find($cart_item['product_id']);
        } elseif ($cart_item['trendyol'] == 1) {
            $item_product = new Product($trendyolProduct);
        } elseif ($cart_item['provider_id'] != null) {
            $provider = Provider::find($cart_item['provider_id']);
            $provider_product = $provider->service()->productDetails($cart_item['product_id']);
            $item_product = $provider_product;
        }

        if ($item_product['digital'] == 0) {
            $phisical[] = $item_product;
        }

        if ($item_product['added_by'] == 'admin') {
            array_push($admin_products, $cart_item['product_id']);

            // For carrier wise shipping
            if ($shipping_type == 'carrier_wise_shipping') {
                $admin_product_total_weight += ($item_product['weight'] * $cart_item['quantity']);
                if ($cart_item['trendyol'] == 0 && $cartItem['provider_id'] == null) {
                    $admin_product_total_price += (cart_product_price($cart_item, $item_product, false, false) * $cart_item['quantity']);
                } elseif ($cart_item['trendyol'] == 1) {
                    $admin_product_total_price += $trendyolProduct['new_price'];
                } elseif ($cartItem['provider_id'] != null) {
                    $admin_product_total_price += $providerProduct['new_price'];
                }
            }
        } else {
            $product_ids = array();
            $weight = 0;
            $price = 0;
            if (isset($seller_products[$item_product['user_id']])) {
                $product_ids = $seller_products[$item_product['user_id']];

                // For carrier wise shipping
                if ($shipping_type == 'carrier_wise_shipping') {
                    $weight += $seller_product_total_weight[$item_product['user_id']];
                    $price += $seller_product_total_price[$item_product['user_id']];
                }
            }

            array_push($product_ids, $cart_item['product_id']);
            $seller_products[$item_product['user_id']] = $product_ids;

            // For carrier wise shipping
            if ($shipping_type == 'carrier_wise_shipping') {
                $weight += ($item_product['weight'] * $cart_item['quantity']);
                $seller_product_total_weight[$item_product['user_id']] = $weight;

                $price += (cart_product_price($cart_item, $item_product, false, false) * $cart_item['quantity']);
                $seller_product_total_price[$item_product['user_id']] = $price;
            }
        }
    }
    if ($shipping_type == 'flat_rate') {
        return count($phisical) > 0 ? get_setting('flat_rate_shipping_cost') / count($phisical) : 0;
    } elseif ($shipping_type == 'seller_wise_shipping') {
        if ($product['added_by'] == 'admin') {
            return get_setting('shipping_cost_admin') / count($admin_products);
        } else {
            return Shop::where('user_id', $product['user_id'])->first()->shipping_cost / count($seller_products[$product['user_id']]);
        }
    } elseif ($shipping_type == 'area_wise_shipping') {
        $shipping_info = Address::where('id', $carts[0]['address_id'])->first();
        $city = City::where('id', $shipping_info->city_id)->first();
        if ($city != null) {
            if ($product['added_by'] == 'admin') {
                return $city->cost / count($admin_products);
            } else {
                return $city->cost / count($seller_products[$product['user_id']]);
            }
        }
        return 0;
    } elseif ($shipping_type == 'carrier_wise_shipping') { // carrier wise shipping
        $user_zone = Address::where('id', $carts[0]['address_id'])->first()->country->zone_id;
        if ($carrier == null || $user_zone == 0) {
            return 0;
        }

        $carrier = Carrier::find($carrier);
        if ($carrier->carrier_ranges->first()) {
            $carrier_billing_type   = $carrier->carrier_ranges->first()->billing_type;
            if ($product['added_by'] == 'admin') {
                $itemsWeightOrPrice = $carrier_billing_type == 'weight_based' ? $admin_product_total_weight : $admin_product_total_price;
            } else {
                $itemsWeightOrPrice = $carrier_billing_type == 'weight_based' ? $seller_product_total_weight[$product['user_id']] : $seller_product_total_price[$product['user_id']];
            }
        }

        foreach ($carrier->carrier_ranges as $carrier_range) {
            if ($itemsWeightOrPrice >= $carrier_range->delimiter1 && $itemsWeightOrPrice < $carrier_range->delimiter2) {
                $carrier_price = $carrier_range->carrier_range_prices->where('zone_id', $user_zone)->first()->price;
                return $product['added_by'] == 'admin' ? ($carrier_price / count($admin_products)) : ($carrier_price / count($seller_products[$product['user_id']]));
            }
        }
        return 0;
    } else {
        if ($product['is_quantity_multiplied'] && ($shipping_type == 'product_wise_shipping')) {
            return  $product['shipping_cost'] * $cartItem['quantity'];
        }
        return $product['shipping_cost'];
    }
}


//return carrier wise shipping cost against seller
if (!function_exists('carrier_base_price')) {
    function carrier_base_price($carts, $carrier_id, $owner_id)
    {
        $shipping = 0;
        foreach ($carts as $key => $cartItem) {
            if ($cartItem->owner_id == $owner_id) {
                $shipping_cost = getShippingCost($carts, $key, $carrier_id);
                $shipping += $shipping_cost;
            }
        }
        return $shipping;
    }
}

//return seller wise carrier list
if (!function_exists('seller_base_carrier_list')) {
    function seller_base_carrier_list($owner_id)
    {
        $carrier_list = array();
        $carts = Cart::where('user_id', auth()->user()->id)->get();
        if (count($carts) > 0) {
            $zone = $carts[0]['address'] ? Country::where('id', $carts[0]['address']['country_id'])->first()->zone_id : null;
            $carrier_query = Carrier::query();
            $carrier_query->whereIn('id', function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                    ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
            $carrier_list = $carrier_query->active()->get();
        }
        return (new CarrierCollection($carrier_list))->extra($owner_id);
    }
}

function timezones()
{
    return array(
        '(GMT-12:00) International Date Line West' => 'Pacific/Kwajalein',
        '(GMT-11:00) Midway Island' => 'Pacific/Midway',
        '(GMT-11:00) Samoa' => 'Pacific/Apia',
        '(GMT-10:00) Hawaii' => 'Pacific/Honolulu',
        '(GMT-09:00) Alaska' => 'America/Anchorage',
        '(GMT-08:00) Pacific Time (US & Canada)' => 'America/Los_Angeles',
        '(GMT-08:00) Tijuana' => 'America/Tijuana',
        '(GMT-07:00) Arizona' => 'America/Phoenix',
        '(GMT-07:00) Mountain Time (US & Canada)' => 'America/Denver',
        '(GMT-07:00) Chihuahua' => 'America/Chihuahua',
        '(GMT-07:00) La Paz' => 'America/Chihuahua',
        '(GMT-07:00) Mazatlan' => 'America/Mazatlan',
        '(GMT-06:00) Central Time (US & Canada)' => 'America/Chicago',
        '(GMT-06:00) Central America' => 'America/Managua',
        '(GMT-06:00) Guadalajara' => 'America/Mexico_City',
        '(GMT-06:00) Mexico City' => 'America/Mexico_City',
        '(GMT-06:00) Monterrey' => 'America/Monterrey',
        '(GMT-06:00) Saskatchewan' => 'America/Regina',
        '(GMT-05:00) Eastern Time (US & Canada)' => 'America/New_York',
        '(GMT-05:00) Indiana (East)' => 'America/Indiana/Indianapolis',
        '(GMT-05:00) Bogota' => 'America/Bogota',
        '(GMT-05:00) Lima' => 'America/Lima',
        '(GMT-05:00) Quito' => 'America/Bogota',
        '(GMT-04:00) Atlantic Time (Canada)' => 'America/Halifax',
        '(GMT-04:00) Caracas' => 'America/Caracas',
        '(GMT-04:00) La Paz' => 'America/La_Paz',
        '(GMT-04:00) Santiago' => 'America/Santiago',
        '(GMT-03:30) Newfoundland' => 'America/St_Johns',
        '(GMT-03:00) Brasilia' => 'America/Sao_Paulo',
        '(GMT-03:00) Buenos Aires' => 'America/Argentina/Buenos_Aires',
        '(GMT-03:00) Georgetown' => 'America/Argentina/Buenos_Aires',
        '(GMT-03:00) Greenland' => 'America/Godthab',
        '(GMT-02:00) Mid-Atlantic' => 'America/Noronha',
        '(GMT-01:00) Azores' => 'Atlantic/Azores',
        '(GMT-01:00) Cape Verde Is.' => 'Atlantic/Cape_Verde',
        '(GMT) Casablanca' => 'Africa/Casablanca',
        '(GMT) Dublin' => 'Europe/London',
        '(GMT) Edinburgh' => 'Europe/London',
        '(GMT) Lisbon' => 'Europe/Lisbon',
        '(GMT) London' => 'Europe/London',
        '(GMT) UTC' => 'UTC',
        '(GMT) Monrovia' => 'Africa/Monrovia',
        '(GMT+01:00) Amsterdam' => 'Europe/Amsterdam',
        '(GMT+01:00) Belgrade' => 'Europe/Belgrade',
        '(GMT+01:00) Berlin' => 'Europe/Berlin',
        '(GMT+01:00) Bern' => 'Europe/Berlin',
        '(GMT+01:00) Bratislava' => 'Europe/Bratislava',
        '(GMT+01:00) Brussels' => 'Europe/Brussels',
        '(GMT+01:00) Budapest' => 'Europe/Budapest',
        '(GMT+01:00) Copenhagen' => 'Europe/Copenhagen',
        '(GMT+01:00) Ljubljana' => 'Europe/Ljubljana',
        '(GMT+01:00) Madrid' => 'Europe/Madrid',
        '(GMT+01:00) Paris' => 'Europe/Paris',
        '(GMT+01:00) Prague' => 'Europe/Prague',
        '(GMT+01:00) Rome' => 'Europe/Rome',
        '(GMT+01:00) Sarajevo' => 'Europe/Sarajevo',
        '(GMT+01:00) Skopje' => 'Europe/Skopje',
        '(GMT+01:00) Stockholm' => 'Europe/Stockholm',
        '(GMT+01:00) Vienna' => 'Europe/Vienna',
        '(GMT+01:00) Warsaw' => 'Europe/Warsaw',
        '(GMT+01:00) West Central Africa' => 'Africa/Lagos',
        '(GMT+01:00) Zagreb' => 'Europe/Zagreb',
        '(GMT+02:00) Athens' => 'Europe/Athens',
        '(GMT+02:00) Bucharest' => 'Europe/Bucharest',
        '(GMT+02:00) Cairo' => 'Africa/Cairo',
        '(GMT+02:00) Harare' => 'Africa/Harare',
        '(GMT+02:00) Helsinki' => 'Europe/Helsinki',
        '(GMT+02:00) Istanbul' => 'Europe/Istanbul',
        '(GMT+02:00) Jerusalem' => 'Asia/Jerusalem',
        '(GMT+02:00) Kyev' => 'Europe/Kiev',
        '(GMT+02:00) Minsk' => 'Europe/Minsk',
        '(GMT+02:00) Pretoria' => 'Africa/Johannesburg',
        '(GMT+02:00) Riga' => 'Europe/Riga',
        '(GMT+02:00) Sofia' => 'Europe/Sofia',
        '(GMT+02:00) Tallinn' => 'Europe/Tallinn',
        '(GMT+02:00) Vilnius' => 'Europe/Vilnius',
        '(GMT+03:00) Baghdad' => 'Asia/Baghdad',
        '(GMT+03:00) Kuwait' => 'Asia/Kuwait',
        '(GMT+03:00) Moscow' => 'Europe/Moscow',
        '(GMT+03:00) Nairobi' => 'Africa/Nairobi',
        '(GMT+03:00) Riyadh' => 'Asia/Riyadh',
        '(GMT+03:00) St. Petersburg' => 'Europe/Moscow',
        '(GMT+03:00) Volgograd' => 'Europe/Volgograd',
        '(GMT+03:30) Tehran' => 'Asia/Tehran',
        '(GMT+04:00) Abu Dhabi' => 'Asia/Muscat',
        '(GMT+04:00) Baku' => 'Asia/Baku',
        '(GMT+04:00) Muscat' => 'Asia/Muscat',
        '(GMT+04:00) Tbilisi' => 'Asia/Tbilisi',
        '(GMT+04:00) Yerevan' => 'Asia/Yerevan',
        '(GMT+04:30) Kabul' => 'Asia/Kabul',
        '(GMT+05:00) Ekaterinburg' => 'Asia/Yekaterinburg',
        '(GMT+05:00) Islamabad' => 'Asia/Karachi',
        '(GMT+05:00) Karachi' => 'Asia/Karachi',
        '(GMT+05:00) Tashkent' => 'Asia/Tashkent',
        '(GMT+05:30) Chennai' => 'Asia/Kolkata',
        '(GMT+05:30) Kolkata' => 'Asia/Kolkata',
        '(GMT+05:30) Mumbai' => 'Asia/Kolkata',
        '(GMT+05:30) New Delhi' => 'Asia/Kolkata',
        '(GMT+05:45) Kathmandu' => 'Asia/Kathmandu',
        '(GMT+06:00) Almaty' => 'Asia/Almaty',
        '(GMT+06:00) Astana' => 'Asia/Dhaka',
        '(GMT+06:00) Dhaka' => 'Asia/Dhaka',
        '(GMT+06:00) Novosibirsk' => 'Asia/Novosibirsk',
        '(GMT+06:00) Sri Jayawardenepura' => 'Asia/Colombo',
        '(GMT+06:30) Rangoon' => 'Asia/Rangoon',
        '(GMT+07:00) Bangkok' => 'Asia/Bangkok',
        '(GMT+07:00) Hanoi' => 'Asia/Bangkok',
        '(GMT+07:00) Jakarta' => 'Asia/Jakarta',
        '(GMT+07:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
        '(GMT+08:00) Beijing' => 'Asia/Hong_Kong',
        '(GMT+08:00) Chongqing' => 'Asia/Chongqing',
        '(GMT+08:00) Hong Kong' => 'Asia/Hong_Kong',
        '(GMT+08:00) Irkutsk' => 'Asia/Irkutsk',
        '(GMT+08:00) Kuala Lumpur' => 'Asia/Kuala_Lumpur',
        '(GMT+08:00) Perth' => 'Australia/Perth',
        '(GMT+08:00) Singapore' => 'Asia/Singapore',
        '(GMT+08:00) Taipei' => 'Asia/Taipei',
        '(GMT+08:00) Ulaan Bataar' => 'Asia/Irkutsk',
        '(GMT+08:00) Urumqi' => 'Asia/Urumqi',
        '(GMT+09:00) Osaka' => 'Asia/Tokyo',
        '(GMT+09:00) Sapporo' => 'Asia/Tokyo',
        '(GMT+09:00) Seoul' => 'Asia/Seoul',
        '(GMT+09:00) Tokyo' => 'Asia/Tokyo',
        '(GMT+09:00) Yakutsk' => 'Asia/Yakutsk',
        '(GMT+09:30) Adelaide' => 'Australia/Adelaide',
        '(GMT+09:30) Darwin' => 'Australia/Darwin',
        '(GMT+10:00) Brisbane' => 'Australia/Brisbane',
        '(GMT+10:00) Canberra' => 'Australia/Sydney',
        '(GMT+10:00) Guam' => 'Pacific/Guam',
        '(GMT+10:00) Hobart' => 'Australia/Hobart',
        '(GMT+10:00) Melbourne' => 'Australia/Melbourne',
        '(GMT+10:00) Port Moresby' => 'Pacific/Port_Moresby',
        '(GMT+10:00) Sydney' => 'Australia/Sydney',
        '(GMT+10:00) Vladivostok' => 'Asia/Vladivostok',
        '(GMT+11:00) Magadan' => 'Asia/Magadan',
        '(GMT+11:00) New Caledonia' => 'Asia/Magadan',
        '(GMT+11:00) Solomon Is.' => 'Asia/Magadan',
        '(GMT+12:00) Auckland' => 'Pacific/Auckland',
        '(GMT+12:00) Fiji' => 'Pacific/Fiji',
        '(GMT+12:00) Kamchatka' => 'Asia/Kamchatka',
        '(GMT+12:00) Marshall Is.' => 'Pacific/Fiji',
        '(GMT+12:00) Wellington' => 'Pacific/Auckland',
        '(GMT+13:00) Nuku\'alofa' => 'Pacific/Tongatapu'
    );
}

if (!function_exists('app_timezone')) {
    function app_timezone()
    {
        return config('app.timezone');
    }
}

//return file uploaded via uploader
if (!function_exists('uploaded_asset')) {
    function uploaded_asset($id)
    {
        if ($id == 0 || $id == null) {
            // Return a placeholder if the id is not 0 or null
            return static_asset('assets/img/placeholder.webp');
        }

        // Retrieve cached uploads if available
        $request = Request::instance();
        $uploaded_assets = $request->attributes->get('uploaded_assets', []);

        // Fetch and cache all uploads if not already cached
        if (empty($uploaded_assets)) {
            $uploaded_assets = Cache::remember('uploaded_assets', 2592000, function () {
                return Upload::all()->keyBy('id')->map(function ($asset) {
                    return $asset->external_link == null ? my_asset($asset->file_name) : $asset->external_link;
                })->toArray();
            });

            // Store the cached uploads in the request attributes
            $request->attributes->set('uploaded_assets', $uploaded_assets);
        }

        // If the asset is not found in the cached array
        if (!isset($uploaded_assets[$id])) {
            // Try to fetch the missing asset from the database
            $asset = Upload::find($id);

            if ($asset) {
                // Add the new asset to the cache
                $uploaded_assets[$id] = $asset->external_link == null ? my_asset($asset->file_name) : $asset->external_link;

                // Update the cache with the newly fetched asset
                Cache::put('uploaded_assets', $uploaded_assets, 2592000);

                // Store the updated cached uploads in the request attributes
                $request->attributes->set('uploaded_assets', $uploaded_assets);
            } else {
                // Return a placeholder if the asset is not found in the database
                return static_asset('assets/img/placeholder.webp');
            }
        }

        // Return the asset for the given ID
        return $uploaded_assets[$id];
    }
}




if (!function_exists('my_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function my_asset($path, $secure = null)
    {
        if (config('filesystems.default') != 'local' && $path != null && $path != '') {
            return Storage::disk(config('filesystems.default'))->url($path);
        }

        return app('url')->asset('public/' . $path, $secure);
    }
}

if (!function_exists('static_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function static_asset($path, $secure = null)
    {
        return app('url')->asset('public/' . $path, $secure);
    }
}


// if (!function_exists('isHttps')) {
//     function isHttps()
//     {
//         return !empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS']);
//     }
// }

if (!function_exists('getBaseURL')) {
    function getBaseURL()
    {
        $root = '//' . $_SERVER['HTTP_HOST'];
        $root .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

        return $root;
    }
}


if (!function_exists('getFileBaseURL')) {
    function getFileBaseURL()
    {
        if (env('FILESYSTEM_DRIVER') != 'local') {
            return env(Str::upper(env('FILESYSTEM_DRIVER')) . '_URL') . '/';
        }

        return getBaseURL() . 'public/';
    }
}


if (!function_exists('isUnique')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function isUnique($email)
    {
        $user = \App\Models\User::where('email', $email)->first();

        if ($user == null) {
            return '1'; // $user = null means we did not get any match with the email provided by the user inside the database
        } else {
            return '0';
        }
    }
}

if (!function_exists('get_setting')) {
    function get_setting($key, $default = null, $lang = false)
    {
        // Retrieve settings from request's attributes if already cached
        $request = Request::instance();
        $settings = $request->attributes->get('business_settings', null);

        // Fetch settings if not already cached
        if ($settings === null) {
            $settings = Cache::remember('business_settings', 86400, function () {
                return BusinessSetting::all();
            });

            // Store settings in request attributes for reuse within the request
            $request->attributes->set('business_settings', $settings);
        }

        // Find the setting based on type and optional language
        $setting = $settings->where('type', $key);

        if ($lang) {
            $setting = $setting->where('lang', $lang)->first();
            $setting = !$setting ? $settings->where('type', $key)->first() : $setting;
        } else {
            $setting = $setting->first();
        }

        return $setting === null ? $default : $setting->value;
    }
}

function hex2rgba($color, $opacity = false)
{
    return (new ColorCodeConverter())->convertHexToRgba($color, $opacity);
}

if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        if (Auth::check() && (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('isSeller')) {
    function isSeller()
    {
        if (Auth::check() && Auth::user()->user_type == 'seller') {
            return true;
        }
        return false;
    }
}

if (!function_exists('isCustomer')) {
    function isCustomer()
    {
        if (Auth::check() && Auth::user()->user_type == 'customer') {
            return true;
        }
        return false;
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// duplicates m$ excel's ceiling function
if (!function_exists('ceiling')) {
    function ceiling($number, $significance = 1)
    {
        return (is_numeric($number) && is_numeric($significance)) ? (ceil($number / $significance) * $significance) : false;
    }
}

//for api
if (!function_exists('get_images_path')) {
    function get_images_path($given_ids, $with_trashed = false)
    {
        $paths = [];
        foreach (explode(',', $given_ids) as $id) {
            $paths[] = uploaded_asset($id);
        }

        return $paths;
    }
}

//for api
if (!function_exists('checkout_done')) {
    function checkout_done($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::find($combined_order_id);

        foreach ($combined_order->orders as $key => $order) {
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            try {
                NotificationUtility::sendOrderPlacedNotification($order);
                calculateCommissionAffilationClubPoint($order);
            } catch (\Exception $e) {
            }
        }
    }
}

// get user total ordered products
if (!function_exists('get_user_total_ordered_products')) {
    function get_user_total_ordered_products()
    {
        $orders_query = Order::query();
        $orders       = $orders_query->where('user_id', Auth::user()->id)->get();
        $total        = 0;
        foreach ($orders as $order) {
            $total += count($order->orderDetails);
        }
        return $total;
    }
}

//for api
if (!function_exists('wallet_payment_done')) {
    function wallet_payment_done($user_id, $amount, $payment_method, $payment_details)
    {
        $user = \App\Models\User::find($user_id);
        $user->balance = $user->balance + $amount;
        $user->save();

        $wallet = new Wallet;
        $wallet->user_id = $user->id;
        $wallet->amount = $amount;
        $wallet->payment_method = $payment_method;
        $wallet->payment_details = $payment_details;
        $wallet->save();
    }
}

if (!function_exists('purchase_payment_done')) {
    function purchase_payment_done($user_id, $package_id)
    {
        $user = User::findOrFail($user_id);
        $user->customer_package_id = $package_id;
        $customer_package = CustomerPackage::findOrFail($package_id);
        $user->remaining_uploads += $customer_package->product_upload;
        $user->save();

        return 'success';
    }
}

if (!function_exists('seller_purchase_payment_done')) {
    function seller_purchase_payment_done($user_id, $seller_package_id, $amount, $payment_method, $payment_details)
    {
        $seller = Shop::where('user_id', $user_id)->first();
        $seller->seller_package_id = $seller_package_id;
        $seller_package = SellerPackage::findOrFail($seller_package_id);
        $seller->product_upload_limit = $seller_package->product_upload_limit;
        $seller->package_invalid_at = date('Y-m-d', strtotime($seller->package_invalid_at . ' +' . $seller_package->duration . 'days'));
        $seller->save();

        $seller_package = new SellerPackagePayment();
        $seller_package->user_id = $user_id;
        $seller_package->seller_package_id = $seller_package_id;
        $seller_package->payment_method = $payment_method;
        $seller_package->payment_details = $payment_details;
        $seller_package->approval = 1;
        $seller_package->offline_payment = 2;
        $seller_package->save();
    }
}

if (!function_exists('customer_purchase_payment_done')) {
    function customer_purchase_payment_done($user_id, $customer_package_id)
    {
        $user = User::findOrFail($user_id);
        $user->customer_package_id = $customer_package_id;
        $customer_package = CustomerPackage::findOrFail($customer_package_id);
        $user->remaining_uploads += $customer_package->product_upload;
        $user->save();
    }
}

if (!function_exists('product_restock')) {
    function product_restock($orderDetail)
    {
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

//Commission Calculation
if (!function_exists('calculateCommissionAffilationClubPoint')) {
    function calculateCommissionAffilationClubPoint($order)
    {
        (new CommissionController)->calculateCommission($order);

        if (addon_is_activated('affiliate_system')) {
            (new AffiliateController)->processAffiliatePoints($order);
        }

        if (addon_is_activated('club_point')) {
            if ($order->user != null) {
                (new ClubPointController)->processClubPoints($order);
            }
        }

        $order->commission_calculated = 1;
        $order->save();
    }
}

// Addon Activation Check
if (!function_exists('addon_is_activated')) {
    function addon_is_activated($identifier, $default = null)
    {
        // Retrieve addons from request's attributes if already cached
        $request = Request::instance();
        $addons = $request->attributes->get('addons', null);

        // Fetch addons if not already cached
        if ($addons === null) {
            $addons = Cache::remember('addons', 86400, function () {
                return Addon::all();
            });

            // Store addons in request attributes for reuse within the request
            $request->attributes->set('addons', $addons);
        }

        // Check if the addon is activated
        $activation = $addons->where('unique_identifier', $identifier)->where('activated', 1)->first();
        return $activation !== null;
    }
}


// Addon Activation Check
if (!function_exists('seller_package_validity_check')) {
    function seller_package_validity_check($user_id = null)
    {
        $user = $user_id == null ? \App\Models\User::find(Auth::user()->id) : \App\Models\User::find($user_id);
        $shop = $user->shop;
        $package_validation = false;
        if (
            $shop->product_upload_limit > $shop->user->products()->count()
            && $shop->package_invalid_at != null
            && Carbon::now()->diffInDays(Carbon::parse($shop->package_invalid_at), false) >= 0
        ) {
            $package_validation = true;
        }

        return $package_validation;
        // Ture = Seller package is valid and seller has the product upload limit
        // False = Seller package is invalid or seller product upload limit exists.
    }
}

// Get URL params
if (!function_exists('get_url_params')) {
    function get_url_params($url, $key)
    {
        $query_str = parse_url($url, PHP_URL_QUERY);
        parse_str($query_str, $query_params);

        return $query_params[$key] ?? '';
    }
}

// get Admin
if (!function_exists('get_admin')) {
    function get_admin()
    {
        $admin_query = User::query();
        return $admin_query->where('user_type', 'admin')->first();
    }
}

// Get slider images
if (!function_exists('get_slider_images')) {
    function get_slider_images($ids)
    {
        $slider_query = Upload::query();
        $sliders = $slider_query->whereIn('id', $ids)->get();
        return $sliders;
    }
}

if (!function_exists('get_featured_flash_deal')) {
    function get_featured_flash_deal()
    {
        $flash_deal_query = FlashDeal::query();
        $featured_flash_deal = $flash_deal_query->isActiveAndFeatured()
            ->where('start_date', '<=', strtotime(date('Y-m-d H:i:s')))
            ->where('end_date', '>=', strtotime(date('Y-m-d H:i:s')))
            ->first();

        return $featured_flash_deal;
    }
}

if (!function_exists('get_flash_deal_products')) {
    function get_flash_deal_products($flash_deal_id)
    {
        $flash_deal_product_query = FlashDealProduct::query();
        $flash_deal_product_query->where('flash_deal_id', $flash_deal_id);
        $flash_deal_products = $flash_deal_product_query->with('product')->limit(10)->get();

        return $flash_deal_products;
    }
}

if (!function_exists('get_active_flash_deals')) {
    function get_active_flash_deals()
    {
        $activated_flash_deal_query = FlashDeal::query();
        $activated_flash_deal_query = $activated_flash_deal_query->where("status", 1);

        return $activated_flash_deal_query->get();
    }
}

if (!function_exists('get_active_taxes')) {
    function get_active_taxes()
    {
        $activated_tax_query = Tax::query();
        $activated_tax_query = $activated_tax_query->where("tax_status", 1);

        return $activated_tax_query->get();
    }
}

if (!function_exists('get_system_language')) {
    function get_system_language()
    {
        $language_query = Language::query();

        $locale = 'en';
        if (Session::has('locale')) {
            $locale = Session::get('locale', Config::get('app.locale'));
        }

        $language_query->where('code',  $locale);

        return $language_query->first();
    }
}

if (!function_exists('get_all_active_language')) {
    function get_all_active_language()
    {
        $language_query = Language::query();
        $language_query->where('status', 1);

        return $language_query->get();
    }
}

// get Session langauge
if (!function_exists('get_session_language')) {
    function get_session_language()
    {
        $language_query = Language::query();
        return $language_query->where('code', Session::get('locale', Config::get('app.locale')))->first();
    }
}

if (!function_exists('get_system_currency')) {
    function get_system_currency()
    {
        $currency_query = Currency::query();
        if (Session::has('currency_code')) {
            $currency_query->where('code', Session::get('currency_code'));
        } else {
            $currency_query = $currency_query->where('id', get_setting('system_default_currency'));
        }

        return $currency_query->first();
    }
}

if (!function_exists('get_all_active_currency')) {
    function get_all_active_currency()
    {
        $currency_query = Currency::query();
        $currency_query->where('status', 1);

        return $currency_query->get();
    }
}

if (!function_exists('get_single_product')) {
    function get_single_product($product_id)
    {
        $product_query = Product::query()->with('thumbnail');
        return $product_query->find($product_id);
    }
}

if (!function_exists('get_single_product_trendyol')) {
    function get_single_product_trendyol($product_id, $urunNo = null)
    {
        $accessToken = trendyol_search_account_login();
        $productArray = trendyol_cart_product_details($accessToken, $product_id, $urunNo);
        $product = new Product($productArray);
        return $product;
    }
}

// get multiple Products
if (!function_exists('get_multiple_products')) {
    function get_multiple_products($product_ids)
    {
        $products_query = Product::query();
        return $products_query->whereIn('id', $product_ids)->get();
    }
}

// get count of products
if (!function_exists('get_products_count')) {
    function get_products_count($user_id = null)
    {
        $products_query = Product::query();
        if ($user_id) {
            $products_query = $products_query->where('user_id', $user_id);
        }
        return $products_query->isApprovedPublished()->count();
    }
}

// get minimum unit price of products
if (!function_exists('get_product_min_unit_price')) {
    function get_product_min_unit_price($user_id = null)
    {
        $product_query = Product::query();
        if ($user_id) {
            $product_query = $product_query->where('user_id', $user_id);
        }
        return $product_query->isApprovedPublished()->min('unit_price');
    }
}

// get maximum unit price of products
if (!function_exists('get_product_max_unit_price')) {
    function get_product_max_unit_price($user_id = null)
    {
        $product_query = Product::query();
        if ($user_id) {
            $product_query = $product_query->where('user_id', $user_id);
        }
        return $product_query->isApprovedPublished()->max('unit_price');
    }
}

if (!function_exists('get_featured_products')) {
    function get_featured_products()
    {
        // Retrieve featured products from request's attributes if already cached
        $request = Request::instance();
        $cache_key = 'featured_products';
        $featured_products = $request->attributes->get($cache_key, null);

        // Fetch featured products if not already cached
        if ($featured_products === null) {
            $featured_products = Cache::remember($cache_key, 3600, function () {
                $product_query = Product::query();
                return filter_products($product_query->where('featured', '1'))
                    ->latest()
                    ->limit(12)
                    ->get();
            });

            // Store featured products in request attributes for reuse within the request
            $request->attributes->set($cache_key, $featured_products);
        }

        return $featured_products;
    }
}

if (!function_exists('get_best_selling_products')) {
    function get_best_selling_products($limit, $user_id = null)
    {
        $product_query = Product::query();
        if ($user_id) {
            $product_query = $product_query->where('user_id', $user_id);
        }
        return filter_products($product_query->orderBy('num_of_sale', 'desc'))->limit($limit)->get();
    }
}

// Get Seller Products
if (!function_exists('get_all_sellers')) {
    function get_all_sellers()
    {
        $seller_query = Seller::query();
        return $seller_query->get();
    }
}

// Get Seller Products
if (!function_exists('get_seller_products')) {
    function get_seller_products($user_id)
    {
        $product_query = Product::query();
        return $product_query->where('user_id', $user_id)->isApprovedPublished()->orderBy('created_at', 'desc')->limit(15)->get();
    }
}

// Get Seller Best Selling Products
if (!function_exists('get_shop_best_selling_products')) {
    function get_shop_best_selling_products($user_id)
    {
        $product_query = Product::query();
        return $product_query->where('user_id', $user_id)->isApprovedPublished()->orderBy('num_of_sale', 'desc')->paginate(24);
    }
}

// Get all auction Products
if (!function_exists('get_all_auction_products')) {
    function get_auction_products($limit = null, $paginate = null)
    {
        $product_query = Product::query();
        $products = $product_query->latest()->where('published', 1)->where('auction_product', 1);
        if (get_setting('seller_auction_product') == 0) {
            $products = $products->where('added_by', 'admin');
        }
        $products = $products->where('auction_start_date', '<=', strtotime("now"))->where('auction_end_date', '>=', strtotime("now"));

        if ($limit) {
            $products = $products->limit($limit);
        } elseif ($paginate) {
            return $products->paginate($paginate);
        }
        return $products->get();
    }
}

//Get similiar classified products
if (!function_exists('get_similiar_classified_products')) {
    function get_similiar_classified_products($category_id = '', $product_id = '', $limit = '')
    {
        $classified_product_query = CustomerProduct::query();
        if ($category_id) {
            $classified_product_query->where('category_id', $category_id);
        }
        if ($product_id) {
            $classified_product_query->where('id', '!=', $product_id);
        }
        $classified_product_query->isActiveAndApproval();
        if ($limit) {
            $classified_product_query->take($limit);
        }

        return $classified_product_query->get();
    }
}

//Get home page classified products
if (!function_exists('get_home_page_classified_products')) {
    function get_home_page_classified_products($limit = '')
    {
        $classified_product_query = CustomerProduct::query()->with('user', 'thumbnail');
        $classified_product_query->isActiveAndApproval();
        if ($limit) {
            $classified_product_query->take($limit);
        }

        return $classified_product_query->get();
    }
}

// Get related product
if (!function_exists('get_related_products')) {
    function get_related_products($product)
    {
        $product_query = Product::query();
        return filter_products($product_query->where('id', '!=', $product->id)->where('category_id', $product->category_id))->limit(10)->get();
    }
}

// Get all brands
if (!function_exists('get_all_brands')) {
    function get_all_brands()
    {
        $brand_query = Brand::query();
        return  $brand_query->get();
    }
}

// Get single brands
if (!function_exists('get_brands')) {
    function get_brands($brand_ids)
    {
        $brand_query = Brand::query();
        $brand_query->with('brandLogo');
        $brands = $brand_query->whereIn('id', $brand_ids)->get();
        return $brands;
    }
}

// Get single brands
if (!function_exists('get_single_brand')) {
    function get_single_brand($brand_id)
    {
        $brand_query = Brand::query();
        return $brand_query->find($brand_id);
    }
}

// Get Brands by products
if (!function_exists('get_brands_by_products')) {
    function get_brands_by_products($usrt_id)
    {
        $product_query = Product::query();
        $brand_ids =  $product_query->where('user_id', $usrt_id)->isApprovedPublished()->whereNotNull('brand_id')->pluck('brand_id')->toArray();

        $brand_query = Brand::query();
        return $brand_query->whereIn('id', $brand_ids)->get();
    }
}

// Get category
if (!function_exists('get_category')) {
    function get_category($category_ids)
    {
        $category_query = Category::query();
        $category_query->with('coverImage');

        $category_query->whereIn('id', $category_ids);

        $categories = $category_query->get();
        return $categories;
    }
}

// Get single category
if (!function_exists('get_single_category')) {
    function get_single_category($category_id)
    {
        $category_query = Category::query()->with('coverImage');
        return $category_query->find($category_id);
    }
}

// Get categories by level zero
if (!function_exists('get_level_zero_categories')) {
    function get_level_zero_categories()
    {
        $categories_query = Category::query()->with(['coverImage', 'catIcon']);
        return $categories_query->where('level', 0)->orderBy('order_level', 'desc')->get();
    }
}

// Get categories by products
if (!function_exists('get_categories_by_products')) {
    function get_categories_by_products($user_id)
    {
        $product_query = Product::query();
        $category_ids = $product_query->where('user_id', $user_id)->isApprovedPublished()->pluck('category_id')->toArray();

        $category_query = Category::query();
        return $category_query->whereIn('id', $category_ids)->get();
    }
}

// Get single Color name
if (!function_exists('get_single_color_name')) {
    function get_single_color_name($color)
    {
        $color_query = Color::query();
        return isset($color_query->where('code', $color)->first()->name) ? $color_query->where('code', $color)->first()->name : null;
    }
}

// Get single Attribute
if (!function_exists('get_single_attribute_name')) {
    function get_single_attribute_name($attribute)
    {
        $attribute_query = Attribute::query();
        return $attribute_query->find($attribute)->getTranslation('name');
    }
}

// Get user cart
if (!function_exists('get_user_cart')) {
    function get_user_cart()
    {
        $cart = [];
        if (auth()->user() != null) {
            $cart = Cart::where('user_id', Auth::user()->id)->get();
        } else {
            $temp_user_id = Session()->get('temp_user_id');
            if ($temp_user_id) {
                $cart = Cart::where('temp_user_id', $temp_user_id)->get();
            }
        }
        return $cart;
    }
}

// Get user Wishlist
if (!function_exists('get_user_wishlist')) {
    function get_user_wishlist()
    {
        $wishlist_query = Wishlist::query();
        return $wishlist_query->where('user_id', Auth::user()->id)->get();
    }
}

//Get best seller
if (!function_exists('get_best_sellers')) {
    function get_best_sellers($limit = '')
    {
        // Construct a unique cache key based on the limit
        $cache_key = 'best_sellers' . ($limit ? "-{$limit}" : '');

        // Retrieve best sellers from request's attributes if already cached
        $request = Request::instance();
        $best_sellers = $request->attributes->get($cache_key, null);

        // Fetch best sellers if not already cached
        if ($best_sellers === null) {
            $best_sellers = Cache::remember($cache_key, 86400, function () use ($limit) {
                return Shop::where('verification_status', 1)
                    ->orderBy('num_of_sale', 'desc')
                    ->take($limit)
                    ->get();
            });

            // Store best sellers in request attributes for reuse within the request
            $request->attributes->set($cache_key, $best_sellers);
        }

        return $best_sellers;
    }
}

//Get users followed sellers
if (!function_exists('get_followed_sellers')) {
    function get_followed_sellers()
    {
        $followed_seller_query = FollowSeller::query();
        return $followed_seller_query->where('user_id', Auth::user()->id)->pluck('shop_id')->toArray();
    }
}

// Get Order Details
if (!function_exists('get_order_details')) {
    function get_order_details($order_id)
    {
        $order_detail_query = OrderDetail::query();
        return  $order_detail_query->find($order_id);
    }
}

// Get Order Details by review
if (!function_exists('get_order_details_by_review')) {
    function get_order_details_by_review($review)
    {
        $order_detail_query = OrderDetail::query();
        return $order_detail_query->with(['order' => function ($q) use ($review) {
            $q->where('user_id', $review->user_id);
        }])->where('product_id', $review->product_id)->where('delivery_status', 'delivered')->first();
    }
}


// Get user total expenditure
if (!function_exists('get_user_total_expenditure')) {
    function get_user_total_expenditure()
    {
        $user = Auth::user();
        $user_expenditure_query = $user->orders
            ->flatMap(function ($order) {
                return $order->orderDetails
                    ->where('payment_status', 'paid')
                    ->where('delivery_status', '!=', 'cancelled');
            })
            ->sum('price');

        return  $user_expenditure_query;
    }
}

// Get count by delivery viewed
if (!function_exists('get_count_by_delivery_viewed')) {
    function get_count_by_delivery_viewed()
    {
        $order_query = Order::query();
        return  $order_query->where('user_id', Auth::user()->id)->where('delivery_viewed', 0)->get()->count();
    }
}

// Get delivery boy info
if (!function_exists('get_delivery_boy_info')) {
    function get_delivery_boy_info()
    {
        $delivery_boy_info_query = DeliveryBoy::query();
        return  $delivery_boy_info_query->where('user_id', Auth::user()->id)->first();
    }
}

// Get count by completed delivery
if (!function_exists('get_delivery_boy_total_completed_delivery')) {
    function get_delivery_boy_total_completed_delivery()
    {
        $delivery_boy_delivery_query = Order::query();
        return  $delivery_boy_delivery_query->where('assign_delivery_boy', Auth::user()->id)
            ->where('delivery_status', 'delivered')
            ->count();
    }
}

// Get count by pending delivery
if (!function_exists('get_delivery_boy_total_pending_delivery')) {
    function get_delivery_boy_total_pending_delivery()
    {
        $delivery_boy_delivery_query = Order::query();
        return  $delivery_boy_delivery_query->where('assign_delivery_boy', Auth::user()->id)
            ->where('delivery_status', '!=', 'delivered')
            ->where('delivery_status', '!=', 'cancelled')
            ->where('cancel_request', '0')
            ->count();
    }
}

// Get count by cancelled delivery
if (!function_exists('get_delivery_boy_total_cancelled_delivery')) {
    function get_delivery_boy_total_cancelled_delivery()
    {
        $delivery_boy_delivery_query = Order::query();
        return  $delivery_boy_delivery_query->where('assign_delivery_boy', Auth::user()->id)
            ->where('delivery_status', 'cancelled')
            ->count();
    }
}

// Get count by payment status viewed
if (!function_exists('get_order_info')) {
    function get_order_info($order_id = null)
    {
        $order_query = Order::query();
        return  $order_query->where('id', $order_id)->first();
    }
}

// Get count by payment status viewed
if (!function_exists('get_user_order_by_id')) {
    function get_user_order_by_id($order_id = null)
    {
        $order_query = Order::query();
        return  $order_query->where('id', $order_id)->where('user_id', Auth::user()->id)->first();
    }
}

// Get Auction Product Bid Info
if (!function_exists('get_auction_product_bid_info')) {
    function get_auction_product_bid_info($bid_id = null)
    {
        $product_bid_info_query = AuctionProductBid::query();
        return  $product_bid_info_query->where('id', $bid_id)->first();
    }
}

// Get count by payment status viewed
if (!function_exists('get_count_by_payment_status_viewed')) {
    function get_count_by_payment_status_viewed()
    {
        $order_query = Order::query();
        return  $order_query->where('user_id', Auth::user()->id)->where('payment_status_viewed', 0)->get()->count();
    }
}

// Get Uploaded file
if (!function_exists('get_single_uploaded_file')) {
    function get_single_uploaded_file($file_id)
    {
        $file_query = Upload::query();
        return $file_query->find($file_id);
    }
}

// Get single customer package file
if (!function_exists('get_single_customer_package')) {
    function get_single_customer_package($package_id)
    {
        $customer_package_query = CustomerPackage::query();
        return $customer_package_query->find($package_id);
    }
}

// Get single Seller package file
if (!function_exists('get_single_seller_package')) {
    function get_single_seller_package($package_id)
    {
        $seller_package_query = SellerPackage::query();
        return $seller_package_query->find($package_id);
    }
}

// Get user last wallet recharge
if (!function_exists('get_user_last_wallet_recharge')) {
    function get_user_last_wallet_recharge()
    {
        $recharge_query = Wallet::query();
        return $recharge_query->where('user_id', Auth::user()->id)->orderBy('id', 'desc')->first();
    }
}

// Get user total Club point
if (!function_exists('get_user_total_club_point')) {
    function get_user_total_club_point()
    {
        $club_point_query = ClubPoint::query();
        return $club_point_query->where('user_id', Auth::user()->id)->where('convert_status', 0)->sum('points');
    }
}

// Get all manual payment methods
if (!function_exists('get_all_manual_payment_methods')) {
    function get_all_manual_payment_methods()
    {
        $manual_payment_methods_query = ManualPaymentMethod::query();
        return $manual_payment_methods_query->get();
    }
}

// Get all blog category
if (!function_exists('get_all_blog_categories')) {
    function get_all_blog_categories()
    {
        $blog_category_query = BlogCategory::query();
        return  $blog_category_query->get();
    }
}

// Get all Pickup Points
if (!function_exists('get_all_pickup_points')) {
    function get_all_pickup_points()
    {
        $pickup_points_query = PickupPoint::query();
        return  $pickup_points_query->isActive()->get();
    }
}

// get Shop by user id
if (!function_exists('get_shop_by_user_id')) {
    function get_shop_by_user_id($user_id)
    {
        $shop_query = Shop::query();
        return $shop_query->where('user_id', $user_id)->first();
    }
}

// get Coupons
if (!function_exists('get_coupons')) {
    function get_coupons($user_id = null, $paginate = null)
    {
        $coupon_query = Coupon::query();
        $coupon_query = $coupon_query->where('start_date', '<=', strtotime(date('d-m-Y')))->where('end_date', '>=', strtotime(date('d-m-Y')));
        if ($user_id) {
            $coupon_query = $coupon_query->where('user_id', $user_id);
        }
        if ($paginate) {
            return $coupon_query->paginate($paginate);
        }
        return $coupon_query->get();
    }
}

// get non-viewed Conversations
if (!function_exists('get_non_viewed_conversations')) {
    function get_non_viewed_conversations()
    {
        $Conversation_query = Conversation::query();
        return $Conversation_query->where('sender_id', Auth::user()->id)->where('sender_viewed', 0)->get();
    }
}

// get affliate option status
if (!function_exists('get_affliate_option_status')) {
    function get_affliate_option_status($status = false)
    {
        if (
            AffiliateOption::where('type', 'product_sharing')->first()->status ||
            AffiliateOption::where('type', 'category_wise_affiliate')->first()->status
        ) {
            $status = true;
        }
        return $status;
    }
}

// get affliate option purchase status
if (!function_exists('get_affliate_purchase_option_status')) {
    function get_affliate_purchase_option_status($status = false)
    {
        if (AffiliateOption::where('type', 'user_registration_first_purchase')->first()->status) {
            $status = true;
        }
        return $status;
    }
}

// get affliate config
if (!function_exists('get_Affiliate_onfig_value')) {
    function get_Affiliate_onfig_value()
    {
        return AffiliateConfig::where('type', 'verification_form')->first()->value;
    }
}

// Welcome Coupon add for user
if (!function_exists('offerUserWelcomeCoupon')) {
    function offerUserWelcomeCoupon()
    {
        $coupon = Coupon::where('type', 'welcome_base')->where('status', 1)->first();
        if ($coupon) {

            $couponDetails = json_decode($coupon->details);

            $user_coupon                = new UserCoupon();
            $user_coupon->user_id       = auth()->user()->id;
            $user_coupon->coupon_id     = $coupon->id;
            $user_coupon->coupon_code   = $coupon->code;
            $user_coupon->min_buy       = $couponDetails->min_buy;
            $user_coupon->validation_days = $couponDetails->validation_days;
            $user_coupon->discount      = $coupon->discount;
            $user_coupon->discount_type = $coupon->discount_type;
            $user_coupon->expiry_date   = strtotime(date('d-m-Y H:i:s') . ' +' . $couponDetails->validation_days . 'days');
            $user_coupon->save();
        }
    }
}

// get User Welcome Coupon
if (!function_exists('ifUserHasWelcomeCouponAndNotUsed')) {
    function ifUserHasWelcomeCouponAndNotUsed()
    {
        $user = auth()->user();
        $userCoupon = $user->userCoupon;
        if ($userCoupon) {
            $userWelcomeCoupon = $userCoupon->where('expiry_date', '>=', strtotime(date('d-m-Y H:i:s')))->first();
            if ($userWelcomeCoupon) {
                $couponUse = $userWelcomeCoupon->coupon->couponUsages->where('user_id', $user->id)->first();
                if (!$couponUse) {
                    return $userWelcomeCoupon;
                }
            }
        }

        return false;
    }
}

// get dev mail
if (!function_exists('get_dev_mail')) {
    function get_dev_mail()
    {
        $dev_mail = (chr(100) . chr(101) . chr(118) . chr(101) . chr(108) . chr(111) . chr(112) . chr(101) . chr(114) . chr(46)
            . chr(97) . chr(99) . chr(116) . chr(105) . chr(118) . chr(101) . chr(105) . chr(116) . chr(122) . chr(111)
            . chr(110) . chr(101) . chr(64) . chr(103) . chr(109) . chr(97) . chr(105) . chr(108) . chr(46) . chr(99) . chr(111) . chr(109));
        return $dev_mail;
    }
}


// Get Thumbnail Image
if (!function_exists('get_image')) {
    function get_image($image)
    {
        $image_url = static_asset('assets/img/placeholder.webp');
        if ($image != null) {
            $image_url = $image->external_link == null ? my_asset($image->file_name) : $image->external_link;
        }
        return $image_url;
    }
}

// Get POS user cart
if (!function_exists('get_pos_user_cart')) {
    function get_pos_user_cart($sessionUserID = null, $sessionTemUserId = null)
    {
        $cart               = [];
        $authUser           = auth()->user();
        $owner_id           = $authUser->type == 'admin' ? User::where('user_type', 'admin')->first()->id : $authUser->id;

        if ($sessionUserID == null) {
            $sessionUserID = Session::has('pos.user_id') ? Session::get('pos.user_id') : null;
        }
        if ($sessionTemUserId == null) {
            $sessionTemUserId = Session::has('pos.temp_user_id') ? Session::get('pos.temp_user_id') : null;
        }

        $cart = Cart::where('owner_id', $owner_id)->where('user_id', $sessionUserID)->where('temp_user_id', $sessionTemUserId)->get();
        return $cart;
    }
}


//Trendyol


//Trendyol Login primary account
if (!function_exists('trendyol_search_account_login')) {
    function trendyol_search_account_login()
    {
        $cacheExpireTime = Cache::get('TRENDYOL_SEARCH_EXPIRE_TIME');
        $cacheAccessToken = Cache::get('TRENDYOL_SEARCH_ACCESS_TOKEN');
        if (empty($cacheExpireTime) || date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($cacheExpireTime . '+ ' . env('TRENDYOL_SESSION_LIVE') . ' hour'))) {
            $accessToken = trendyol_login(env('TRENDYOL_SEARCH_USERNAME'), env('TRENDYOL_SEARCH_PASSWORD'));
            $expireTime =  date('Y-m-d H:i:s');

            Cache::put('TRENDYOL_SEARCH_ACCESS_TOKEN', encryptStringTrendyol($accessToken, env('TRENDYOL_SEARCH_USERNAME')), now()->addHours(env('TRENDYOL_SESSION_LIVE')));
            Cache::put('TRENDYOL_SEARCH_EXPIRE_TIME', $expireTime, now()->addHours(env('TRENDYOL_SESSION_LIVE')));

            return $accessToken;
        } else {
            return decryptStringTrendyol($cacheAccessToken, env('TRENDYOL_SEARCH_USERNAME'));
        }
    }
}


//Trendyol register account
if (!function_exists('trendyol_register')) {
    function trendyol_register($name, $surname, $username, $pass)
    {
        $accountInfo =  json_encode(array(
            'name' => $name,
            'surname' =>  $surname,   //decrept the pass
            'username' => $username,
            'pass'    => $pass
        ));
        $registerURL = env('TRENDYOL_HOST') . env('TRENDYOL_REGISTER_URL');
        $ch = curl_init($registerURL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $accountInfo);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($accountInfo)
            )
        );
        $result = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($result)) {
            if ($result->id != null) {
                return true;
            }
        }
        return false;
    }
}

//Trendyol Login account
if (!function_exists('trendyol_login')) {
    function trendyol_login($username, $password)
    {
        $session = null;
        $loginInfo =  json_encode(array(
            'username' =>  $username,
            'pass' =>  $password   //decrept the pass
        ));
        $loginURL = env('TRENDYOL_HOST') . env('TRENDYOL_LOGIN_URL');
        $ch = curl_init($loginURL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $loginInfo);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($loginInfo)
            )
        );
        $result = json_decode(curl_exec($ch));

        curl_close($ch);
        if (!empty($result)) {
            $session = $result->accessToken;
        }
        return $session;
    }
}

//Trendyol Login by auth
if (!function_exists('trendyol_account_login')) {
    function trendyol_account_login()
    {
        if (Auth::check() && Auth::user()->user_type == 'customer') {
            $session = null;
            $sessionTime =  date('Y-m-d H:i:s');
            $userId = Auth::user()->id;
            $trendyolAccount = TrendyolAccount::where('user_id', $userId)->first();
            if ($trendyolAccount) {
                if (date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($trendyolAccount->session_time . '+ ' . env('TRENDYOL_SESSION_LIVE') . ' hour'))) {
                    $session = trendyol_login($trendyolAccount->user_name, decryptStringTrendyol($trendyolAccount->password, $trendyolAccount->user_name));
                    if ($session != null) {
                        $trendyolAccount->update([
                            'session'      => encryptStringTrendyol($session, $trendyolAccount->user_name),
                            'session_time' => $sessionTime
                        ]);
                    } else {
                        $username = str_replace(" ", "_", substr(Auth::user()->email, 0, 10)) . '_' . generateRandomPassword(5) . '_stp_pazar_' .  date('Ymdhis');
                        $password = generateRandomPassword(15);
                        $register = trendyol_register($username, 'stp-pazar', $username, $password);

                        if (!empty($register)) {
                            if ($register) {
                                $session = trendyol_login($username, $password);
                                $sessionTime =  date('Y-m-d H:i:s');
                                $trendyolAccount->update([
                                    'user_id'      => Auth::user()->id,
                                    'user_email'   => Auth::user()->email,
                                    'first_name'   => Auth::user()->name,
                                    'last_name'    => 'stp-pazar',
                                    'user_name'    => $username,
                                    'password'     => encryptStringTrendyol($password, $username),
                                    'session'      => encryptStringTrendyol($session, $username),
                                    'session_time' => $sessionTime
                                ]);
                            }
                        }
                    }
                } else {
                    $session =  decryptStringTrendyol($trendyolAccount->session, $trendyolAccount->user_name);
                }
            } else {
                $username = str_replace(" ", "_", substr(Auth::user()->email, 0, 10)) . '_' . generateRandomPassword(5) . '_stp_pazar_' .  date('Ymdhis');
                $password = generateRandomPassword(15);
                $register = trendyol_register($username, 'stp-pazar', $username, $password);

                if (!empty($register)) {
                    if ($register) {
                        $session = trendyol_login($username, $password);
                        $sessionTime =  date('Y-m-d H:i:s');
                        TrendyolAccount::create([
                            'user_id'      => Auth::user()->id,
                            'user_email'   => Auth::user()->email,
                            'first_name'   => Auth::user()->name,
                            'last_name'    => 'stp-pazar',
                            'user_name'    => $username,
                            'password'     => encryptStringTrendyol($password, $username),
                            'session'      => encryptStringTrendyol($session, $username),
                            'session_time' => $sessionTime
                        ]);
                    }
                }
            }
        } else {
            $session = trendyol_search_account_login();
        }

        return $session;
    }
}



//Trendyol get all products by query
if (!function_exists('trendyol_products')) {
    function trendyol_products($query, $pageIndex = 1, $sortBy = null, $filters = [])
    {
        $query = strtolower($query);
        if (str_contains($query, 'suriye') || str_contains($query, 'syria') || str_contains($query, 'سوريا') || str_contains($query, 'سوري')) {
            $query =  'شحاطة';
        }
        if (empty($query)) {
            return [
                'products' => [],
                'filters' =>  [],
                'total' =>  0,
                'colors' => [],
                'categories' => [],
                'brands' => [],
            ];
        }
        $trendyolProducts = [];
        $trendyolFilters = [];
        $colors = [];
        $categories = [];
        $brands = [];
        $is_sexual_content = false;
        $currency = currency_symbol();
        $trendyol_currency = env('TRENDYOL_CURRENCY');
        if (empty($sortBy) || $sortBy == null) {
            $sortBy = 'MOST_RECENT';
        }
        $searchURL = env('TRENDYOL_HOST') . env('TRENDYOL_SEARCH_URL');
        $cdnUrl = env('TRENDYOL_CDN');
        $accessToken = trendyol_search_account_login();
        if (isset($filters['fyt']) && $filters['fyt'] != '') {
            $string = explode('-', $filters['fyt']);
            $max_price = end($string);
            $min_price = reset($string);
        } else {
            $max_price = 0;
            $min_price = 0;
        }
        $queryString = http_build_query(array_merge(
            array(
                'query' => translate_to_tr($query),
                'pageIndex' => $pageIndex,
                'srl'   => $sortBy
            ),
            $filters
        ));
        $ch = curl_init($searchURL . '?' . $queryString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response)) {
            if ($response->success) {
                $products = $response->urunler;
                $total = $response->toplamSayi;
                foreach ($products as $product) {
                    $is_sexual_content = false;
                    $is_sexual_content = strpos($product->resimler[0] ?? '', 'legal-requirement-card-new-white.png') ? true : false;
                    $is_adblue_product = false;
                    $is_adblue_product = (stripos($product->ad, 'adblue') !== false || stripos($product->ad, 'ad blue') !== false) ? true : false;
                    $is_mua_product = false;
                    $is_mua_product = (stripos($product->marka->name, 'mua') !== false || stripos($product->marka->name, 'make up academy') !== false) ? true : false;
                    //$discount = round(100 - ($product->fiyat->indirimliFiyat * 100) / $product->fiyat->orjinalFiyat, 0);
                    $trendyolCategory = trendyol_category($product->kategoriID);
                    $trendyol_tax_percent = $trendyolCategory['percent_value'];
                    $trendyol_tax_flex = $trendyolCategory['flat_value'];
                    $trendyol_discount_percent = $trendyolCategory['percent_discount'];
                    $trendyol_discount_flex = $trendyolCategory['flat_discount'];
                    $trendyolActive = $trendyolCategory['active'];
                    $filters = $response->filtreler->filtrelemeSecenekleri;
                    $orjinalFiyat = $product->fiyat->orjinalFiyat + ($product->fiyat->orjinalFiyat * $trendyol_tax_percent) + $trendyol_tax_flex;
                    $unit_price = single_price(convert_to_default_currency($orjinalFiyat, $trendyol_currency));
                    if (date('Y-m-d') == env('WHITE_FRIDAY')) {
                        $newFiyat = $product->fiyat->orjinalFiyat;
                    } else {
                        $newFiyat = $orjinalFiyat - ($orjinalFiyat * $trendyol_discount_percent) - $trendyol_discount_flex;
                    }
                    $new_price = single_price(convert_to_default_currency($newFiyat, $trendyol_currency));
                    $discount = round(100 - ($newFiyat * 100) / $orjinalFiyat, 0);
                    if ((($max_price == 0  && $min_price == 0) ||
                            ($product->fiyat->orjinalFiyat >= $min_price && $product->fiyat->orjinalFiyat <= $max_price))
                        && $trendyolActive == 1 && !$is_sexual_content
                        && !$is_adblue_product && !$is_mua_product && $product->marka->id != 2387981 && $product->marka->id != 1813264 && $product->marka->id != 2581850
                        && $product->saticiID != 1090916 && $product->saticiID != 1059299 && $product->marka->id != 43) {
                        array_push($trendyolProducts, [
                            'id' => $product->id,
                            'name' => $product->ad,
                            'brandName' => $product->marka->name,
                            'urunNo' => $product->urunNo,
                            'slug' => strtolower(str_replace(' ', '-', $product->ad)),
                            'auction_product' => 0,
                            'thumbnail' => (function ($image) use ($cdnUrl) {
                                if (strpos($image, $cdnUrl . $cdnUrl) !== false) {
                                    $image = str_replace($cdnUrl . $cdnUrl, $cdnUrl, $image);
                                }
                                if (strpos($image, $cdnUrl) === 0) {
                                    return $image;
                                }
                                return $cdnUrl . $image;
                            })($product->resimler[0] ?? ''),
                            'resimAlt' => $product->resimAlt,
                            'unit_price' => $unit_price,
                            'new_price'  => $new_price,
                            'base_unit_price' => $product->fiyat->orjinalFiyat,
                            'base_new_price' => $product->fiyat->orjinalFiyat,
                            'discount_price' =>  $discount,
                            'currency'       => $currency,
                            'wholesale_product' => '',
                            'auction_start_date' => '',
                            'auction_end_date' => '',
                        ]);
                    }
                }
                foreach ($filters as $filter) {
                    if (isset($filter->tip)) {
                        if ($filter->tip == 'WebColor') {
                            array_push($colors, $filter);
                        } elseif ($filter->tip == 'WebBrand') {
                            array_push($brands, $filter);
                        } elseif ($filter->tip == 'WebCategory') {
                            array_push($categories, $filter);
                        } elseif ($filter->tip != 'Price') {
                            array_push($trendyolFilters, $filter);
                        }
                    }
                }
            }
        }
        $data = [
            'products' => $trendyolProducts,
            'filters' =>  $trendyolFilters,
            'total' => $total ?? 0,
            'colors' => $colors,
            'categories' => $categories,
            'brands' => $brands
        ];
        return $data;
    }
}

if (!function_exists('trendyol_suggestions_products')) {
    function trendyol_suggestions_products($query = null)
    {
        $query = strtolower($query);
        $trendyolProducts = [];
        $is_sexual_content = false;
        $currency = currency_symbol();
        $trendyol_currency = env('TRENDYOL_CURRENCY');
        $searchURL = env('TRENDYOL_HOST') . env('TRENDYOL_SEARCH_URL');
        $cdnUrl = env('TRENDYOL_CDN');
        $accessToken = trendyol_search_account_login();
        $queryString = http_build_query(array_merge(
            array(
                'query' => translate_to_tr($query),
                'pageIndex' => 1
            )
        ));
        $ch = curl_init($searchURL . '?' . $queryString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response)) {
            if ($response->success) {
                $products = $response->urunler;
                foreach ($products as $product) {
                    $is_sexual_content = false;
                    $is_sexual_content = strpos($product->resimler[0] ?? '', 'legal-requirement-card-new-white.png') ? true : false;
                    $is_adblue_product = false;
                    $is_adblue_product = (stripos($product->ad, 'adblue') !== false || stripos($product->ad, 'ad blue') !== false) ? true : false;
                    $is_mua_product = false;
                    $is_mua_product = (stripos($product->marka->name, 'mua') !== false || stripos($product->marka->name, 'make up academy') !== false) ? true : false;
                    //$discount = round(100 - ($product->fiyat->indirimliFiyat * 100) / $product->fiyat->orjinalFiyat, 0);
                    $trendyolCategory = trendyol_category($product->kategoriID);
                    $trendyol_tax_percent = $trendyolCategory['percent_value'];
                    $trendyol_tax_flex = $trendyolCategory['flat_value'];
                    $trendyol_discount_percent = $trendyolCategory['percent_discount'];
                    $trendyol_discount_flex = $trendyolCategory['flat_discount'];
                    $trendyolActive = $trendyolCategory['active'];
                    $orjinalFiyat = $product->fiyat->orjinalFiyat + ($product->fiyat->orjinalFiyat * $trendyol_tax_percent) + $trendyol_tax_flex;
                    $unit_price = single_price(convert_to_default_currency($orjinalFiyat, $trendyol_currency));
                    if (date('Y-m-d') == env('WHITE_FRIDAY')) {
                        $newFiyat = $product->fiyat->orjinalFiyat;
                    } else {
                        $newFiyat = $orjinalFiyat - ($orjinalFiyat * $trendyol_discount_percent) - $trendyol_discount_flex;
                    }
                    $new_price = single_price(convert_to_default_currency($newFiyat, $trendyol_currency));
                    $discount = round(100 - ($newFiyat * 100) / $orjinalFiyat, 0);
                    if ($trendyolActive == 1 && !$is_sexual_content  && !$is_adblue_product && !$is_mua_product && $product->marka->id != 2387981 && $product->marka->id != 1813264 && $product->marka->id != 2581850 && $product->saticiID != 1090916 && $product->saticiID != 1059299 && $product->marka->id != 43) {
                        array_push($trendyolProducts, [
                            'id' => $product->id,
                            'name' => $product->ad,
                            'brandName' => $product->marka->name,
                            'urunNo' => $product->urunNo,
                            'slug' => strtolower(str_replace(' ', '-', $product->ad)),
                            'auction_product' => 0,
                            'thumbnail' => (function ($image) use ($cdnUrl) {
                                if (strpos($image, $cdnUrl . $cdnUrl) !== false) {
                                    $image = str_replace($cdnUrl . $cdnUrl, $cdnUrl, $image);
                                }
                                if (strpos($image, $cdnUrl) === 0) {
                                    return $image;
                                }
                                return $cdnUrl . $image;
                            })($product->resimler[0] ?? ''),
                            'resimAlt' => $product->resimAlt,
                            'unit_price' => $unit_price,
                            'new_price'  => $new_price,
                            'base_unit_price' => $product->fiyat->orjinalFiyat,
                            'base_new_price' => $product->fiyat->orjinalFiyat,
                            'discount_price' =>  $discount,
                            'currency'       => $currency,
                            'wholesale_product' => '',
                            'auction_start_date' => '',
                            'auction_end_date' => '',
                        ]);
                    }
                }
            }
        }
        return $trendyolProducts;
    }
}

//Trendyol get all products by category
if (!function_exists('trendyol_category_products')) {
    function trendyol_category_products($category, $pageIndex = 1, $sortBy = null, $filters = [], $query = null)
    {
        $trendyolProducts = [];
        $trendyolFilters = [];
        $colors = [];
        $brands = [];
        if (empty($sortBy) || $sortBy == null) {
            $sortBy = 'MOST_RECENT';
        }
        $currency = currency_symbol();
        $trendyol_currency = env('TRENDYOL_CURRENCY');
        $searchURL = env('TRENDYOL_HOST') . env('TRENDYOL_SEARCH_URL');
        $cdnUrl = env('TRENDYOL_CDN');
        $accessToken = trendyol_search_account_login();
        if (isset($filters['fyt']) && $filters['fyt'] != '') {
            $string = explode('-', $filters['fyt']);
            $max_price = end($string);
            $min_price = reset($string);
        } else {
            $max_price = 0;
            $min_price = 0;
        }
        if ($query != null) {
            $queryString = http_build_query(
                array_merge(
                    array(
                        'query' => translate_to_tr($query),
                        'ktg' => $category,
                        'pageIndex' => $pageIndex,
                        'srl' => $sortBy
                    ),
                    $filters
                )
            );
        } else {
            $queryString = http_build_query(
                array_merge(
                    array(
                        'ktg' => $category,
                        'pageIndex' => $pageIndex,
                        'srl' => $sortBy
                    ),
                    $filters
                )
            );
        }
        $ch = curl_init($searchURL . '?' . $queryString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response)) {
            if ($response->success) {
                $products = $response->urunler;
                $total = $response->toplamSayi;
                foreach ($products as $product) {
                    $is_sexual_content = false;
                    $is_sexual_content = strpos($product->resimler[0] ?? '', 'legal-requirement-card-new-white.png') ? true : false;
                    $is_adblue_product = false;
                    $is_adblue_product = (stripos($product->ad, 'adblue') !== false || stripos($product->ad, 'ad blue') !== false) ? true : false;
                    $is_mua_product = false;
                    $is_mua_product = (stripos($product->marka->name, 'mua') !== false || stripos($product->marka->name, 'make up academy') !== false) ? true : false;
                    $trendyolCategory = trendyol_category($product->kategoriID);
                    $trendyol_tax_percent = $trendyolCategory['percent_value'];
                    $trendyol_tax_flex = $trendyolCategory['flat_value'];
                    $trendyolActive = $trendyolCategory['active'];
                    $trendyol_discount_percent = $trendyolCategory['percent_discount'];
                    $trendyol_discount_flex = $trendyolCategory['flat_discount'];
                    $filters = $response->filtreler->filtrelemeSecenekleri;
                    $orjinalFiyat = $product->fiyat->orjinalFiyat + ($product->fiyat->orjinalFiyat * $trendyol_tax_percent) + $trendyol_tax_flex;
                    $unit_price = single_price(convert_to_default_currency($orjinalFiyat, $trendyol_currency));
                    if (date('Y-m-d') == env('WHITE_FRIDAY')) {
                        $newFiyat = $product->fiyat->orjinalFiyat;
                    } else {
                        $newFiyat = $orjinalFiyat - ($orjinalFiyat * $trendyol_discount_percent) - $trendyol_discount_flex;
                    }
                    $new_price = single_price(convert_to_default_currency($newFiyat, $trendyol_currency));
                    $discount = round(100 - ($newFiyat * 100) / $orjinalFiyat, 0);
                    if ((($max_price == 0  && $min_price == 0) || ($product->fiyat->orjinalFiyat >= $min_price && $product->fiyat->orjinalFiyat <= $max_price)) && $trendyolActive == 1 && $product->marka->id != 2387981 && $product->marka->id != 1813264 && $product->marka->id != 2581850 && !$is_sexual_content && !$is_adblue_product && !$is_mua_product&& $product->saticiID != 1090916 && $product->saticiID != 1059299 && $product->marka->id != 43) {
                        array_push($trendyolProducts, [
                            'id' => $product->id,
                            'name' => $product->ad,
                            'urunNo' => $product->urunNo,
                            'brandName' => $product->marka->name,
                            'slug' => strtolower(str_replace(' ', '-', $product->ad)),
                            'auction_product' => 0,
                            'thumbnail' => (function ($image) use ($cdnUrl) {
                                if (strpos($image, $cdnUrl . $cdnUrl) !== false) {
                                    $image = str_replace($cdnUrl . $cdnUrl, $cdnUrl, $image);
                                }
                                if (strpos($image, $cdnUrl) === 0) {
                                    return $image;
                                }
                                return $cdnUrl . $image;
                            })($product->resimler[0] ?? ''),
                            'resimAlt' => $product->resimAlt,
                            'unit_price' => $unit_price,
                            'new_price'  => $new_price,
                            'currency'       => $currency,
                            'discount_price' =>  $discount,
                            'wholesale_product' => '',
                            'auction_start_date' => '',
                            'auction_end_date' => '',
                        ]);
                    }
                }
                foreach ($filters as $filter) {
                    if (isset($filter->tip)) {
                        if ($filter->tip == 'WebColor') {
                            array_push($colors, $filter);
                        } elseif ($filter->tip == 'WebBrand') {
                            array_push($brands, $filter);
                        } elseif ($filter->tip != 'WebCategory' && $filter->tip != 'Price') {
                            array_push($trendyolFilters, $filter);
                        }
                    }
                }
            }
        }
        $data = [
            'products' => $trendyolProducts,
            'filters' =>  $trendyolFilters,
            'total' => $total ?? 0,
            'colors' => $colors,
            'brands' => $brands
        ];
        return $data;
    }
}

//Trendyol get all products by brand
if (!function_exists('trendyol_brand_products')) {
    function trendyol_brand_products($brand, $pageIndex = 1, $sortBy = null, $filters = [], $query = null)
    {
        $trendyolProducts = [];
        $trendyolFilters = [];
        $colors = [];
        $categories = [];
        if (empty($sortBy) || $sortBy == null) {
            $sortBy = 'MOST_RECENT';
        }
        $currency = currency_symbol();
        $trendyol_currency = env('TRENDYOL_CURRENCY');
        $searchURL = env('TRENDYOL_HOST') . env('TRENDYOL_SEARCH_URL');
        $cdnUrl = env('TRENDYOL_CDN');
        $accessToken = trendyol_search_account_login();
        if (isset($filters['fyt']) && $filters['fyt'] != '') {
            $string = explode('-', $filters['fyt']);
            $max_price = end($string);
            $min_price = reset($string);
        } else {
            $max_price = 0;
            $min_price = 0;
        }
        if ($query != null) {
            $queryString = http_build_query(
                array_merge(
                    array(
                        'query' => translate_to_tr($query),
                        'mrk' => $brand,
                        'pageIndex' => $pageIndex,
                        'srl' => $sortBy
                    ),
                    $filters
                )
            );
        } else {
            $queryString = http_build_query(
                array_merge(
                    array(
                        'mrk' => $brand,
                        'pageIndex' => $pageIndex,
                        'srl' => $sortBy
                    ),
                    $filters
                )
            );
        }
        $ch = curl_init($searchURL . '?' . $queryString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response)) {
            if ($response->success) {
                $products = $response->urunler;
                $total = $response->toplamSayi;
                foreach ($products as $product) {
                    $is_sexual_content = false;
                    $is_sexual_content = strpos($product->resimler[0] ?? '', 'legal-requirement-card-new-white.png') ? true : false;
                    $is_adblue_product = false;
                    $is_adblue_product = (stripos($product->ad, 'adblue') !== false || stripos($product->ad, 'ad blue') !== false) ? true : false;
                    $is_mua_product = false;
                    $is_mua_product = (stripos($product->marka->name, 'mua') !== false || stripos($product->marka->name, 'make up academy') !== false) ? true : false;
                    $trendyolCategory = trendyol_category($product->kategoriID);
                    $trendyol_tax_percent = $trendyolCategory['percent_value'];
                    $trendyol_tax_flex = $trendyolCategory['flat_value'];
                    $trendyolActive = $trendyolCategory['active'];
                    $trendyol_discount_percent = $trendyolCategory['percent_discount'];
                    $trendyol_discount_flex = $trendyolCategory['flat_discount'];
                    $filters = $response->filtreler->filtrelemeSecenekleri;
                    $orjinalFiyat = $product->fiyat->orjinalFiyat + ($product->fiyat->orjinalFiyat * $trendyol_tax_percent) + $trendyol_tax_flex;
                    $unit_price = single_price(convert_to_default_currency($orjinalFiyat, $trendyol_currency));
                    if (date('Y-m-d') == env('WHITE_FRIDAY')) {
                        $newFiyat = $product->fiyat->orjinalFiyat;
                    } else {
                        $newFiyat = $orjinalFiyat - ($orjinalFiyat * $trendyol_discount_percent) - $trendyol_discount_flex;
                    }
                    $new_price = single_price(convert_to_default_currency($newFiyat, $trendyol_currency));
                    $discount = round(100 - ($newFiyat * 100) / $orjinalFiyat, 0);
                    if ((($max_price == 0  && $min_price == 0) || ($product->fiyat->orjinalFiyat >= $min_price && $product->fiyat->orjinalFiyat <= $max_price)) && $trendyolActive == 1 && $product->marka->id != 2387981 && $product->marka->id != 1813264 && $product->marka->id != 2581850 && !$is_sexual_content && !$is_adblue_product && !$is_mua_product && $product->saticiID != 1090916 && $product->saticiID != 1059299 && $product->marka->id != 43) {
                        array_push($trendyolProducts, [
                            'id' => $product->id,
                            'name' => $product->ad,
                            'urunNo' => $product->urunNo,
                            'brandName' => $product->marka->name,
                            'slug' => strtolower(str_replace(' ', '-', $product->ad)),
                            'auction_product' => 0,
                            'thumbnail' => (function ($image) use ($cdnUrl) {
                                if (strpos($image, $cdnUrl . $cdnUrl) !== false) {
                                    $image = str_replace($cdnUrl . $cdnUrl, $cdnUrl, $image);
                                }
                                if (strpos($image, $cdnUrl) === 0) {
                                    return $image;
                                }
                                return $cdnUrl . $image;
                            })($product->resimler[0] ?? ''),
                            'resimAlt' => $product->resimAlt,
                            'unit_price' => $unit_price,
                            'new_price'  => $new_price,
                            'currency'       => $currency,
                            'discount_price' =>  $discount,
                            'wholesale_product' => '',
                            'auction_start_date' => '',
                            'auction_end_date' => '',
                        ]);
                    }
                }
                foreach ($filters as $filter) {
                    if (isset($filter->tip)) {
                        if ($filter->tip == 'WebColor') {
                            array_push($colors, $filter);
                        } elseif ($filter->tip == 'WebCategory') {
                            array_push($categories, $filter);
                        } elseif ($filter->tip != 'WebBrand' && $filter->tip != 'Price') {
                            array_push($trendyolFilters, $filter);
                        }
                    }
                }
            }
        }
        $data = [
            'products' => $trendyolProducts,
            'filters' =>  $trendyolFilters,
            'total' => $total ?? 0,
            'colors' => $colors,
            'categories' => $categories
        ];
        return $data;
    }
}

if (!function_exists('trendyol_seller_products')) {
    function trendyol_seller_products($seller_id, $api_key, $api_secret, $page = 0)
    {
        $products = collect([]);
        $url = str_replace('{supplier_id}', $seller_id, env('TRENDYOL_GET_PRODUCT_BY_SELLER_API'));
        $page > 0 ? --$page : 0;
        $ch = curl_init($url . '?page=' . $page);
        $username = $api_key;
        $password = $api_secret;
        $credentials = base64_encode("$username:$password");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic ' . $credentials
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (isset($response->content)) {
            foreach ($response->content as $product) {
                $products->push([
                    'name' => $product->title,
                    'photos' => collect($product->images)->pluck('url')->toArray(),
                    'category_id' => 0,
                    'unit_price' => $product->salePrice,
                    'description' => $product->description,
                    'current_stock' => $product->quantity,
                    'unit' => $product->stockUnitType,
                    'currency' =>  env('TRENDYOL_CURRENCY')
                ]);
            }
            $products = $products->map(function ($item) {
                return new Product($item);
            });
            $perPage = $response->size;
            $total = $response->totalElements;

            $products = new LengthAwarePaginator(
                $products,
                $total,
                $perPage,
                $page + 1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }
        return $products;
    }
}

//Trendyol product details old
if (!function_exists('trendyol_product_details_old')) {
    function trendyol_product_details_old($accessToken, $id, $urunNo = null, $imgCount = 5)
    {
        $product = [];
        $attributes = [];
        $allVariant = [];
        $brandLink = '';
        $categoryLink = '';
        if ($urunNo == null) {
            $prductURL = env('TRENDYOL_HOST') . env('TRENDYOL_GET_PRODUCT_DETAILS') . $id;
        } else {
            $prductURL = env('TRENDYOL_HOST') . env('TRENDYOL_GET_PRODUCT_DETAILS') . $id . '?urunNo=' . $urunNo;
        }
        $ch = curl_init($prductURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response)) {
            if ($response->success) {
                if ($response->varyantlar[0]->attributeId != 0) {
                    foreach ($response->tumVaryantlar as $variant) {
                        if ($variant->stoktaVar) {
                            $allVariant[$variant->deger] = $variant->urunNo;
                        }
                    }
                }
                if ($response->varyantlar[0]->stok > 0) {
                    $stock = $response->varyantlar[0]->stok;
                } else {
                    if ($response->stoktaVar) {
                        $stock = 10;
                    } else {
                        $stock = 0;
                    }
                }
                $trendyolCategory = trendyol_category($response->kategori->kategoriID);
                $trendyol_tax_percent = $trendyolCategory['percent_value'];
                $trendyol_tax_flex = $trendyolCategory['flat_value'];
                $trendyol_discount_percent = $trendyolCategory['percent_discount'];
                $trendyol_discount_flex = $trendyolCategory['flat_discount'];
                $trendyolActive = $trendyolCategory['active'];
                $unit_price = $response->varyantlar[0]->fiyat->orjinalFiyat->value + ($response->varyantlar[0]->fiyat->orjinalFiyat->value * $trendyol_tax_percent) + $trendyol_tax_flex;
                if (date('Y-m-d') == env('WHITE_FRIDAY')) {
                    $new_price = $response->varyantlar[0]->fiyat->orjinalFiyat->value;
                } else {
                    $new_price = $unit_price - ($unit_price * $trendyol_discount_percent) - $trendyol_discount_flex;
                }
                $discount = ($new_price > 0) ? round(100 - ($new_price * 100) / $unit_price, 0) : 0;
                $brand = Brand::where('trendyol_id', $response->marka->id)->first();
                if ($brand) {
                    $brandLink = route('products.brand', $brand->slug);
                }
                $category = Category::where('trendyol_id', $response->kategori->kategoriID)->first();
                if ($category) {
                    $categoryLink = route('products.category', $category->slug);
                }
                if ($trendyolActive == 1) {
                    $product = [
                        'id' => $response->id,
                        'name' => $response->ad,
                        'photos' => array_slice($response->resimler, 0, $imgCount),
                        'unit_price' => number_format($unit_price, 2, '.', ','),
                        'new_price'  => number_format($new_price, 2, '.', ','),
                        'base_price' =>  $response->varyantlar[0]->fiyat->indirimliFiyat->value,
                        'discount_price' =>  $discount,
                        'currency'       => currency_symbol(),
                        'varyantlar' => $response->varyantlar[0]->attributeValue,
                        'min_qty' => 1,
                        'auction_product' => 0,
                        'unit'  => 'unit',
                        'tax' => 0,
                        'stock' => $stock,
                        'digital' => 0,
                        'rating' =>  number_format($response->puanlama->ortalamaPuan, 2),
                        'contRating' => $response->puanlama->puanlamaSayisi,
                        'est_shipping_days' => 0,
                        'wholesale_product' => 0,
                        'kampanyaID' => $response->kampanya->id,
                        'listeID'    => $response->varyantlar[0]->listeId,
                        'shopId' =>  $response->satici->saticiID,
                        'shopName' =>  $response->satici->satici,
                        'brandId'  => $response->marka->id,
                        'brandName' => $response->marka->name,
                        'brandLink' => $brandLink,
                        'categoryId' => $response->kategori->kategoriID,
                        'categoryName' => $response->kategori->kategori,
                        'categoryLink' => $categoryLink,
                        'urunNo' => $response->varyantlar[0]->urunNo,
                        'added_by' => 'admin',
                        'user_id'  => get_admin()->id,
                        'urunGrupNo' => $response->urunGrupNo,
                        'gender'     => $response->cinsiyet->id,
                        'link'       => route('trendyol-product', ['id' =>  $response->id, 'urunNo' => $response->varyantlar[0]->urunNo]),
                        'descriptions' => [],
                        'attributes'   => $attributes,
                        'trendyol' => 1,
                        'choice_options' => [
                            [
                                'id'   => $response->varyantlar[0]->attributeId,
                                'name' => $response->varyantlar[0]->attributeType,
                                'values' => $allVariant
                            ]
                        ]
                    ];
                }
            }
        }
        return $product;
    }
}


if (!function_exists('trendyol_product_details')) {
    function trendyol_product_details($accessToken, $id, $urunNo = null, $imgCount = 5)
    {
        $product = [];
        $attributes = [];
        $allVariant = [];
        $brandLink = '';
        $categoryLink = '';
        $stock = 0;
        $is_sexual_content = false;
        if ($urunNo == null) {
            $prductURL = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_GET_PRODUCT_DETAILS') . $id;
        } else {
            $prductURL = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_GET_PRODUCT_DETAILS') . $id . '?itemNumber=' . $urunNo;
        }
        $ch = curl_init($prductURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response)) {
            if ($response->isSuccess) {
                $response = $response->result;
                if (!empty($response->variants)) {
                    if ($response->variants[0]->attributeId != 0) {
                        foreach ($response->allVariants as $variant) {
                            if ($variant->inStock) {
                                $allVariant[$variant->value] = $variant->itemNumber;
                            }
                        }
                    }
                    if ($response->variants[0]->stock != null) {
                        $stock = $response->variants[0]->stock;
                    } else {
                        if ($response->hasStock) {
                            $stock = 10;
                        } else {
                            $stock = 0;
                        }
                    }
                }
                $invoiceApplicable = $response->merchant->corporateInvoiceApplicable;
                $trendyolCategory = trendyol_category($response->originalCategory->id);
                $trendyol_tax_percent = $trendyolCategory['percent_value'];
                $trendyol_tax_flex = $trendyolCategory['flat_value'];
                $trendyol_discount_percent = $trendyolCategory['percent_discount'];
                $trendyol_discount_flex = $trendyolCategory['flat_discount'];
                $trendyolActive = $trendyolCategory['active'];
                $trendyol_currency = env('TRENDYOL_CURRENCY');
                $unit_price = $response->price->originalPrice->value + ($response->price->originalPrice->value * $trendyol_tax_percent) + $trendyol_tax_flex;
                if (date('Y-m-d') == env('WHITE_FRIDAY')) {
                    $new_price = $response->price->originalPrice->value;
                } else {
                    $new_price = $unit_price - ($unit_price * $trendyol_discount_percent) - $trendyol_discount_flex;
                }
                $discount = ($unit_price > 0) ? round(100 - ($new_price * 100) / $unit_price, 0) : 0;
                $images = $response->images;
                $cdnUrl = env('TRENDYOL_CDN');
                foreach ($response->attributes as $attribute) {
                    $attributes[$attribute->key->name] = $attribute->value->name;
                }
                $brand = Brand::where('trendyol_id', $response->brand->id)->first();
                if ($brand) {
                    $brandLink = route('products.brand', $brand->slug);
                }
                $category = Category::where('trendyol_id', $response->category->id)->first();
                if ($category) {
                    $categoryLink = route('products.category', $category->slug);
                }
                $is_sexual_content = false;
                $is_sexual_content = isset($images[0]) && strpos($images[0], 'legal-requirement-card-new-white.png') ? true : false;
                $is_adblue_product = false;
                $is_adblue_product = (stripos($response->name, 'adblue') !== false || stripos($response->name, 'ad blue') !== false) ? true : false;
                $is_mua_product = false;
                $is_mua_product = (stripos($response->brand->name, 'mua') !== false || stripos($response->brand->name, 'make up academy') !== false) ? true : false;
                if ($trendyolActive == 1 && !$is_sexual_content && !$is_adblue_product && !$is_mua_product && $response->brand->id != 2387981 && $response->brand->id != 1813264 && $response->brand->id != 2581850
                && $response->merchant->id != 1090916 && $response->merchant->id != 1059299 && $response->brand->id != 43) {
                    $product = [
                        'id' => $response->id,
                        'name' => $response->name,
                        'photos' => array_slice(array_map(function ($image) use ($cdnUrl) {
                            if (strpos($image, $cdnUrl) === 0) {
                                return $image;
                            }
                            return $cdnUrl . $image;
                        }, $images), 0, $imgCount),
                        'unit_price' => convert_to_default_currency(number_format($unit_price, 2, '.', ''), $trendyol_currency),
                        'new_price'  => convert_to_default_currency(number_format($new_price, 2, '.', ''), $trendyol_currency),
                        'base_price' =>  convert_to_default_currency($response->price->discountedPrice->value, $trendyol_currency),
                        'discount_price' =>  $discount,
                        'currency'       => currency_symbol(),
                        'varyantlar' => $response->variants[0]->attributeValue ?? null,
                        'min_qty' => $invoiceApplicable ? $response->merchant->moq ?? 1 : 0,
                        'auction_product' => 0,
                        'unit'  => 'unit',
                        'tax' => 0,
                        'stock' => $invoiceApplicable ? $stock : 0,
                        'digital' => 0,
                        'rating' =>  number_format($response->ratingScore->averageRating, 2),
                        'contRating' => $response->ratingScore->totalRatingCount,
                        'est_shipping_days' => 0,
                        'wholesale_product' => 0,
                        'kampanyaID' => $response->campaign->id ?? null,
                        'listeID'    => $response->listingId,
                        'shopId' =>  $response->merchant->id,
                        'shopName' =>  $response->merchant->name,
                        'shopOfficialName' =>  $response->merchant->officialName ?? '',
                        'shopEmail' =>  $response->merchant->registeredEmailAddress ?? '',
                        'shopTaxNumber' =>  $response->merchant->taxNumber ?? '',
                        'brandId'  => $response->brand->id,
                        'brandApiId' => $brand->id ?? null,
                        'brandName' => $response->brand->name,
                        'brandLink' => $brandLink,
                        'categoryId' => $response->category->id,
                        'categoryApiId' => $category->id ?? null,
                        'categoryName' => $response->category->name,
                        'categoryLink' => $categoryLink,
                        'originalCategory' => $response->originalCategory->id,
                        'urunNo' => $response->variants[0]->itemNumber ?? 0,
                        'added_by' => 'admin',
                        'user_id'  => get_admin()->id,
                        'urunGrupNo' => $response->productGroupId,
                        'gender'     => property_exists($response->gender, 'id') ? $response->gender->id : 0,
                        'link'       => route('trendyol-product', ['id' =>  $response->id, 'urunNo' => $response->variants[0]->itemNumber ?? 0]),
                        'descriptions' => $response->descriptions,
                        'attributes'   => $attributes,
                        'trendyol' => 1,
                        'choice_options' => [
                            [
                                'id'   => $response->variants[0]->attributeId ?? 0,
                                'name' => $response->variants[0]->attributeType ?? null,
                                'values' => $allVariant
                            ]
                        ]
                    ];
                }
            }
        }
        return $product;
    }
}

//Trendyol related product
if (!function_exists('trendyol_product_propreties')) {
    function trendyol_product_propreties($accessToken, $urunGrupNo)
    {
        $propreties = [];
        $propertyUrl = env('TRENDYOL_HOST') . env('TRENDYOL_PROPERTY_PRODUCT') . $urunGrupNo;
        $ch = curl_init($propertyUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response)) {
            if ($response->statusCode == 200 && count($response->sonuc->ozellikler) > 0) {
                foreach ($response->sonuc->ozellikler[0]->anaOzellikler as $property) {
                    $propreties[$property->icerik[0]->id] =  ['name' => $property->ad, 'img' => $property->icerik[0]->resimUrl];
                }
            }
        }
        return $propreties;
    }
}

//Trenduol for cart details
if (!function_exists('trendyol_cart_product_details')) {
    function trendyol_cart_product_details($accessToken, $id, $urunNo = null)
    {
        $product = [];
        $attributes = [];
        $allVariant = [];
        $stock = 0;
        $trendyol_currency = env('TRENDYOL_CURRENCY');
        if ($urunNo == null) {
            $prductURL = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_GET_PRODUCT_DETAILS') . $id;
        } else {
            $prductURL = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_GET_PRODUCT_DETAILS') . $id . '?itemNumber=' . $urunNo;
        }
        $ch = curl_init($prductURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response)) {
            if ($response->isSuccess) {
                $response = $response->result;
                if (!empty($response->variants)) {
                    if ($response->variants[0]->attributeId != 0) {
                        foreach ($response->allVariants as $variant) {
                            if ($variant->inStock) {
                                $allVariant[$variant->value] = trendyol_product_indirimliFiyat($accessToken, $response->id, $variant->itemNumber);
                            }
                        }
                    }
                    if ($response->variants[0]->stock != null) {
                        $stock = $response->variants[0]->stock;
                    } else {
                        if ($response->hasStock) {
                            $stock = 10;
                        } else {
                            $stock = 0;
                        }
                    }
                }
                $invoiceApplicable = $response->merchant->corporateInvoiceApplicable;
                $trendyolCategory = trendyol_category($response->originalCategory->id);
                $trendyol_tax_percent = $trendyolCategory['percent_value'];
                $trendyol_tax_flex = $trendyolCategory['flat_value'];
                $trendyol_discount_percent = $trendyolCategory['percent_discount'];
                $trendyol_discount_flex = $trendyolCategory['flat_discount'];
                $trendyolActive = $trendyolCategory['active'];
                if (date('Y-m-d') == env('WHITE_FRIDAY')) {
                    $unit_price = $response->price->originalPrice->value;
                } else {
                    $unit_price = $response->price->originalPrice->value + ($response->price->originalPrice->value * $trendyol_tax_percent) + $trendyol_tax_flex;
                    $unit_price = $unit_price - ($unit_price * $trendyol_discount_percent) - $trendyol_discount_flex;
                }
                $images = $response->images;
                $cdnUrl = env('TRENDYOL_CDN');
                foreach ($response->attributes as $attribute) {
                    $attributes[$attribute->key->name] = $attribute->value->name;
                }
                if ($trendyolActive == 1) {
                    $product = [
                        'id' => $response->id,
                        'name' => $response->name,
                        'photos' => array_slice(array_map(function ($image) use ($cdnUrl) {
                            if (strpos($image, $cdnUrl) === 0) {
                                return $image;
                            }
                            return $cdnUrl . $image;
                        }, $images), 0, 5),
                        'unit_price' => convert_to_default_currency($unit_price, $trendyol_currency),
                        'base_price' =>  convert_to_default_currency($response->price->discountedPrice->value, $trendyol_currency),
                        'min_qty' => $invoiceApplicable ? $response->merchant->moq ?? 1 : 0,
                        'auction_product' => 0,
                        'tax' => 0,
                        'stock' => $invoiceApplicable ? $stock : 0,
                        'digital' => 0,
                        'urunNo' => $response->variants[0]->itemNumber ?? 0,
                        'added_by' => 'admin',
                        'user_id'  => get_admin()->id,
                        'shopId' =>  $response->merchant->id,
                        'varyantlar' => $response->variants[0]->attributeValue ?? null,
                        'currency'       => currency_symbol(),
                        'trendyol' => 1,
                        'choice_options' => [
                            [
                                'id'   => $response->variants[0]->attributeId ?? 0,
                                'name' => $response->variants[0]->attributeType ?? null,
                                'values' => $allVariant
                            ]
                        ]
                    ];
                }
            }
        }

        return $product;
    }
}

//Trendyol Order Details
if (!function_exists('trendyol_order_details')) {
    function trendyol_order_details($orderId)
    {
        $result = [];
        $products = [];
        $accessToken = env('TRENDYOL_PUBLIC_TOKEN');
        $orderDetailsUrl = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_ORDER_DETAILS_URL') . $orderId;
        $trendyol_currency = env('TRENDYOL_CURRENCY');
        $ch = curl_init($orderDetailsUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response)) {
            if ($response->isSuccess) {
                foreach ($response->result->shipments as $productArray) {
                    foreach ($productArray->items[0]->products as $product) {
                        $unit_price = number_format($product->originalPrice, 2, '.', ',');
                        array_push($products, [
                            'productId'    => $product->contentId,
                            'urunNo'       => $product->variant->itemNumber,
                            'status'       => $productArray->items[0]->status,
                            'altStatus'    => $productArray->items[0]->undeliveredSubStatus ?? null,
                            'count'        => $product->quantity,
                            'name'         => $product->name,
                            'image'        => $product->imageUrl,
                            'unit_price'   => $unit_price,
                            'new_price'    => $unit_price,
                            'link'         => $productArray->items[0]->cargoInfo->trackingLink ?? '',
                            'minDate'      => isset($productArray->estimatedDeliveryStartDate) ? date("Y-m-d H:i:s", $productArray->estimatedDeliveryStartDate / 1000) : null,
                            'maxDate'      => isset($productArray->estimatedDeliveryEndDate) ? date("Y-m-d H:i:s", $productArray->estimatedDeliveryEndDate / 1000) : null,
                            'deliveryDate' => isset($productArray->cargoStartDate) ? date("Y-m-d H:i:s", $productArray->cargoStartDate / 1000) : null,
                            'kargoTakipNo' => $productArray->items[0]->cargoInfo->trackingNumber ?? '',
                        ]);
                    }
                }
                $result = [
                    'summary' => [
                        'orderNumber'   => $response->result->summary->orderNumber,
                        'address'       => $response->result->summary->address->address1,
                        'state'         => $response->result->summary->address->city,
                        'city'          => $response->result->summary->address->district,
                        'streetAddress' => $response->result->summary->address->neighborhood,
                        'addressType'   => $response->result->summary->deliveryAddressType,
                    ],
                    'products' => $products
                ];
            } else {
                $result = [];
                $products = [];
                $accessToken = env('TRENDYOL_PUBLIC_TOKEN_SECOND_ACCOUNT');
                $orderDetailsUrl = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_ORDER_DETAILS_URL') . $orderId;
                $ch = curl_init($orderDetailsUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer ' . $accessToken
                ));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = json_decode(curl_exec($ch));
                curl_close($ch);
                if (!empty($response)) {
                    if ($response->isSuccess) {
                        foreach ($response->result->shipments as $productArray) {
                            foreach ($productArray->items[0]->products as $product) {
                                $unit_price = number_format($product->originalPrice, 2, '.', ',');
                                array_push($products, [
                                    'productId'    => $product->contentId,
                                    'urunNo'       => $product->variant->itemNumber,
                                    'status'       => $productArray->items[0]->status,
                                    'altStatus'    => $productArray->items[0]->undeliveredSubStatus ?? null,
                                    'count'        => $product->quantity,
                                    'name'         => $product->name,
                                    'image'        => $product->imageUrl,
                                    'unit_price'   => $unit_price,
                                    'new_price'    => $unit_price,
                                    'link'         => $productArray->items[0]->cargoInfo->trackingLink,
                                    'minDate'      => date("Y-m-d H:i:s", $productArray->estimatedDeliveryStartDate / 1000),
                                    'maxDate'      => date("Y-m-d H:i:s", $productArray->estimatedDeliveryEndDate / 1000),
                                    'deliveryDate' => date("Y-m-d H:i:s", $productArray->cargoStartDate / 1000),
                                    'kargoTakipNo' => $productArray->items[0]->cargoInfo->trackingNumber
                                ]);
                            }
                        }
                        $result = [
                            'summary' => [
                                'orderNumber'   => $response->result->summary->orderNumber,
                                'address'       => $response->result->summary->address->address1,
                                'state'         => $response->result->summary->address->city,
                                'city'          => $response->result->summary->address->district,
                                'streetAddress' => $response->result->summary->address->neighborhood,
                                'addressType'   => $response->result->summary->deliveryAddressType,
                            ],
                            'products' => $products
                        ];
                    }
                }
                return $result;
            }
        }
        return $result;
    }
}

//Trendyol get price
if (!function_exists('trendyol_product_indirimliFiyat')) {
    function trendyol_product_indirimliFiyat($accessToken, $id, $urunNo = null)
    {
        $trendyol_currency = env('TRENDYOL_CURRENCY');
        if ($urunNo == null) {
            $prductURL = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_GET_PRODUCT_DETAILS') . $id;
        } else {
            $prductURL = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_GET_PRODUCT_DETAILS') . $id . '?itemNumber=' . $urunNo;
        }
        $ch = curl_init($prductURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response)) {
            if ($response->isSuccess) {
                $response = $response->result;
                $trendyolCategory = trendyol_category($response->originalCategory->id);
                $trendyol_tax_percent = $trendyolCategory['percent_value'];
                $trendyol_tax_flex = $trendyolCategory['flat_value'];
                $trendyol_discount_percent = $trendyolCategory['percent_discount'];
                $trendyol_discount_flex = $trendyolCategory['flat_discount'];
                if (date('Y-m-d') == env('WHITE_FRIDAY')) {
                    $fiyat = convert_to_default_currency($response->price->originalPrice->value, $trendyol_currency);
                } else {
                    $fiyat = convert_to_default_currency($response->price->originalPrice->value, $trendyol_currency);
                    $fiyat = $fiyat + ($fiyat * $trendyol_tax_percent) + $trendyol_tax_flex;
                    $fiyat = $fiyat - ($fiyat * $trendyol_discount_percent) - $trendyol_discount_flex;
                }
            }
        }
        return $fiyat;
    }
}

//Trendyol Purchase
if (!function_exists('trendyol_purchase')) {
    function trendyol_purchase($accessToken, $products)
    {
        $result = [];
        $resultList = [];
        $trendyolOrder = json_encode(["productList" => $products]);
        $purchaseURL = env('TRENDYOL_HOST') . env('TRENDYOL_PURCHASE_URL');
        $ch = curl_init($purchaseURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $trendyolOrder);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if ($response !== null) {
            if (isset($response->resultList)) {
                foreach ($response->resultList as $resultListJson) {
                    array_push($resultList, [
                        'id' => $resultListJson->id,
                        'kampanyaID' => $resultListJson->kampanyaID,
                        'listeID' => $resultListJson->listeID,
                        'saticiID' => $resultListJson->saticiID,
                        'adet' => $resultListJson->adet,
                        'success' => $resultListJson->success
                    ]);
                }
            }
            $result = [
                'status'      => $response->statusDesc,
                'orderNumber' => $response->orderNumber,
                'orderDetail' => $resultList
            ];
        }
        return $result;
    }
}

//Trendyol add to cart
if (!function_exists('trendyol_add_to_cart')) {
    function trendyol_add_to_cart($accessToken, $contentId, $campaignId, $listingId, $merchantId, $quantity)
    {
        $trendyolUrl = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_ADD_TO_CART');
        $product = [
            'contentId' => $contentId,
            'campaignId' => $campaignId,
            'listingId' => $listingId,
            'merchantId' => $merchantId,
            'quantity' => $quantity
        ];
        $trendyolOrder = json_encode($product);
        $ch = curl_init($trendyolUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $trendyolOrder);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if ($response !== null) {
            if (isset($response->isSuccess) && $response->isSuccess) {
                return 'true';
            } else {
                return 'false';
            }
        } else {
            return 'false';
        }
    }
}

// Trendyol get cart items
if (!function_exists('trendyol_get_cart_items')) {
    function trendyol_get_cart_items($accessToken)
    {
        $itemIds = [];
        $trendyolUrl = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_CART');
        $ch = curl_init($trendyolUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if ($response !== null) {
            if (isset($response->isSuccess) && $response->isSuccess) {
                foreach ($response->result->items as $item) {
                    array_push($itemIds, $item->id);
                }
            }
        }
        return $itemIds;
    }
}

// Trendyol delete item from cart
if (!function_exists('trendyol_delete_item_from_cart')) {
    function trendyol_delete_item_from_cart($accessToken, $itemId)
    {
        $trendyolUrl = env('TRENDYOL_PUBLIC_HOST') . env('TRENDYOL_PUBLIC_DELETE_FROM_CART');
        $trendyolUrl = preg_replace('/\?/', $itemId . '?', $trendyolUrl);
        $ch = curl_init($trendyolUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpStatusCode == 204) {
            return 'true';
        } else {
            return 'false';
        }
    }
}

// Trendyol make cart empty
if (!function_exists('trendyol_empty_cart')) {
    function trendyol_empty_cart($accessToken)
    {
        $accessToken = env('TRENDYOL_PUBLIC_TOKEN');
        $items = trendyol_get_cart_items($accessToken);
        foreach ($items as $item) {
            $delete_item = trendyol_delete_item_from_cart($accessToken, $item);
            if ($delete_item == 'false') {
                return 'false';
            }
        }
        return 'true';
    }
}

//Stl Api register parcel
if (!function_exists('stl_shipment')) {
    function stl_shipment($data)
    {
        $data =  json_encode([
            'height' => 0,
            'width'  => 0,
            'length' => 0,
            'weight' => 0,
            'shipping_cost' => 0,
            'shipping_currency' => 'TRY',
            'receiver_name'  => $data['receiver_name'],
            'receiver_id_number' => $data['receiver_id_number'],
            'receiver_phone' => $data['receiver_phone'],
            'receiver_address' => $data['receiver_address'],
            'receiver_city' => $data['receiver_city'],
            'receiver_email' => $data['receiver_email'],
            'estimated_delivery_time' => date('m/d/Y', strtotime('+2 days')) . ' - ' . date('m/d/Y', strtotime('+10 days')),
            'delivery_type' => $data['delivery_type'],
            'parcel_price' => $data['parcel_price'],
            'parcel_currency_symbol' => get_system_default_currency()->code,
            'quantity' => $data['quantity'],
            'parcel_name' => $data['parcel_name'],
            'parcel_url' => $data['parcel_url'],
            'parcel_image' => $data['parcel_image'],
            'order_number' => $data['order_number'],
            'parcel_source_number' => $data['trendyol_order_number'],
            'note' => $data['note'],
            'product_id' => $data['product_id'],
            'urunNo' => $data['urunNo']
        ]);
        $url = env('API_STL_URL');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Secret-Key: ' . env('API_STL_SECRET_KEY'),
            'X-Access-Token: ' . env('API_STL_ACCESS_TOKEN')
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        return $response;
    }
}

//encrypt string
if (!function_exists('encryptStringTrendyol')) {
    function encryptStringTrendyol($string, $key)
    {
        $encrypted = '+';
        while (strstr($encrypted, '+') == true) {
            $method = 'aes-256-cbc';
            $ivLength = openssl_cipher_iv_length($method);
            $iv = openssl_random_pseudo_bytes($ivLength);
            $encrypted = openssl_encrypt($string, $method, $key, OPENSSL_RAW_DATA, $iv);
        }
        return base64_encode($iv . $encrypted);
    }
}

//decrypt string
if (!function_exists('decryptStringTrendyol')) {
    function decryptStringTrendyol($encryptedString, $key)
    {
        $method = 'aes-256-cbc';
        $encrypted = base64_decode($encryptedString);
        $ivLength = openssl_cipher_iv_length($method);
        $iv = substr($encrypted, 0, $ivLength);
        $decrypted = openssl_decrypt(substr($encrypted, $ivLength), $method, $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }
}

//generate password for trendyol
if (!function_exists('generateRandomPassword')) {
    function generateRandomPassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $password = '';

        $charLength = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, $charLength)];
        }

        return $password;
    }
}

if (!function_exists('translate_to_tr_old')) {
    function translate_to_tr_old($text)
    {

        if (App::getLocale() == 'sa') {
            $url = env('API_TRANSLATE_URL') . '/translate';
            $data = '{"text":["' . $text . '"],"target_lang":"TR" , "source_lang" : "AR"}';
            $token = env('API_TRANSLATE_TOKEN');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: ' . $token,
                'Content-Type: application/json'
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($ch));
            curl_close($ch);
            if (!empty($response) && isset($response->translations[0]->text)) {
                return $response->translations[0]->text;
            } else {
                return $text;
            }
        } else if (App::getLocale() == 'en') {
            $url = env('API_TRANSLATE_URL') . '/translate';
            $data = '{"text":["' . $text . '"],"target_lang":"TR" , "source_lang" : "EN"}';
            $token = env('API_TRANSLATE_TOKEN');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: ' . $token,
                'Content-Type: application/json'
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($ch));
            curl_close($ch);
            if (!empty($response) && isset($response->translations[0]->text)) {
                return $response->translations[0]->text;
            } else {
                return $text;
            }
        } else {
            return $text;
        }
    }
}

if (!function_exists('translate_to_tr')) {
    function translate_to_tr($text)
    {
        $url = env('API_TRANSLATE_URL');
        $data = '{
                    "model": "gpt-4o",
                        "messages": [
                        {
                            "role": "system",
                            "content": [
                            {
                                "type": "text",
                                "text": "You will be provided with sentences in Arabic, English, or Turkish. If the sentence is in Arabic or English, translate it into the Turkish language, ensuring the translation is contextually appropriate and of high quality. However, if the sentence is in English or Turkish, return the text as it is, without translation. Special attention must be given to technical terms, which may or may not be translated depending on the context."
                            }
                            ]
                        },
                        {
                            "role": "user",
                            "content": [
                            {
                                "type": "text",
                                "text": "' . $text . '"
                            }
                            ]
                            }
                        ],
                        "temperature": 0,
                        "max_tokens": 2048,
                        "top_p": 1,
                        "frequency_penalty": 0,
                        "presence_penalty": 0,
                        "response_format": {
                        "type": "text"
                        }
                }';
        $token = env('API_TRANSLATE_TOKEN');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if (!empty($response) && isset($response->choices[0]->message->content)) {
            return $response->choices[0]->message->content;
        } else {
            return $text;
        }
    }
}

if (!function_exists('test_chat_gpt_connection')) {
    function test_chat_gpt_connection()
    {
        $url = env('API_TRANSLATE_URL');
        $data = '{
                    "model": "gpt-4o",
                    "messages": [
                        {
                            "role": "system",
                            "content": "This is a connection test."
                        },
                        {
                            "role": "user",
                            "content": "Test connection"
                        }
                    ],
                    "temperature": 0,
                    "max_tokens": 1,
                    "top_p": 1,
                    "frequency_penalty": 0,
                    "presence_penalty": 0
                }';
        $token = env('API_TRANSLATE_TOKEN');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        if (isset($response->error)) {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('trendyol_category')) {
    function trendyol_category($category_id)
    {
        $request = Request::instance();
        $cacheKey = 'trendyol_categories';
        $trendyolCategories = $request->attributes->get($cacheKey);

        if (!$trendyolCategories) {
            $trendyolCategories = Cache::remember($cacheKey, 2592000, function () {
                $trendyolCategoryModel = new \App\Models\TrendyolCategory();
                return $trendyolCategoryModel->all()->keyBy('id');
            });

            $request->attributes->set($cacheKey, $trendyolCategories);
        }

        $category = $trendyolCategories[$category_id] ?? null;

        $trendyolCategory = [];
        if ($category) {
            $trendyolCategory['percent_value'] = $category->percent_tax;
            $trendyolCategory['flat_value'] = $category->flat_tax;
            $trendyolCategory['percent_discount'] = $category->percent_discount;
            $trendyolCategory['flat_discount'] = $category->flat_discount;
            $trendyolCategory['active'] = $category->active;
            $trendyolCategory['ar_name'] = $category->ar_name;
        } else {
            $trendyolCategory['percent_value'] = env('TRENDYOL_TAX_PERCENT');
            $trendyolCategory['flat_value'] = env('TRENDYOL_TAX_FLEX');
            $trendyolCategory['percent_discount'] = env('TRENDYOL_DISCOUNT_PERCENT');
            $trendyolCategory['flat_discount'] = env('TRENDYOL_DISCOUNT_FLEX');
            $trendyolCategory['active'] = 1;
            $trendyolCategory['ar_name'] = '';
        }

        return $trendyolCategory;
    }
}

if (!function_exists('generateSerialCode')) {
    function generateSerialCode($count = 6)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';

        $uniqueCode = '';

        for ($i = 0; $i < $count; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $uniqueCode .= $characters[$index];
        }

        return $uniqueCode;
    }
}

if (!function_exists('timezones')) {
    function timezones()
    {
        return array(
            '(GMT-12:00) International Date Line West' => 'Pacific/Kwajalein',
            '(GMT-11:00) Midway Island' => 'Pacific/Midway',
            '(GMT-11:00) Samoa' => 'Pacific/Apia',
            '(GMT-10:00) Hawaii' => 'Pacific/Honolulu',
            '(GMT-09:00) Alaska' => 'America/Anchorage',
            '(GMT-08:00) Pacific Time (US & Canada)' => 'America/Los_Angeles',
            '(GMT-08:00) Tijuana' => 'America/Tijuana',
            '(GMT-07:00) Arizona' => 'America/Phoenix',
            '(GMT-07:00) Mountain Time (US & Canada)' => 'America/Denver',
            '(GMT-07:00) Chihuahua' => 'America/Chihuahua',
            '(GMT-07:00) La Paz' => 'America/Chihuahua',
            '(GMT-07:00) Mazatlan' => 'America/Mazatlan',
            '(GMT-06:00) Central Time (US & Canada)' => 'America/Chicago',
            '(GMT-06:00) Central America' => 'America/Managua',
            '(GMT-06:00) Guadalajara' => 'America/Mexico_City',
            '(GMT-06:00) Mexico City' => 'America/Mexico_City',
            '(GMT-06:00) Monterrey' => 'America/Monterrey',
            '(GMT-06:00) Saskatchewan' => 'America/Regina',
            '(GMT-05:00) Eastern Time (US & Canada)' => 'America/New_York',
            '(GMT-05:00) Indiana (East)' => 'America/Indiana/Indianapolis',
            '(GMT-05:00) Bogota' => 'America/Bogota',
            '(GMT-05:00) Lima' => 'America/Lima',
            '(GMT-05:00) Quito' => 'America/Bogota',
            '(GMT-04:00) Atlantic Time (Canada)' => 'America/Halifax',
            '(GMT-04:00) Caracas' => 'America/Caracas',
            '(GMT-04:00) La Paz' => 'America/La_Paz',
            '(GMT-04:00) Santiago' => 'America/Santiago',
            '(GMT-03:30) Newfoundland' => 'America/St_Johns',
            '(GMT-03:00) Brasilia' => 'America/Sao_Paulo',
            '(GMT-03:00) Buenos Aires' => 'America/Argentina/Buenos_Aires',
            '(GMT-03:00) Georgetown' => 'America/Argentina/Buenos_Aires',
            '(GMT-03:00) Greenland' => 'America/Godthab',
            '(GMT-02:00) Mid-Atlantic' => 'America/Noronha',
            '(GMT-01:00) Azores' => 'Atlantic/Azores',
            '(GMT-01:00) Cape Verde Is.' => 'Atlantic/Cape_Verde',
            '(GMT) Casablanca' => 'Africa/Casablanca',
            '(GMT) Dublin' => 'Europe/London',
            '(GMT) Edinburgh' => 'Europe/London',
            '(GMT) Lisbon' => 'Europe/Lisbon',
            '(GMT) London' => 'Europe/London',
            '(GMT) UTC' => 'UTC',
            '(GMT) Monrovia' => 'Africa/Monrovia',
            '(GMT+01:00) Amsterdam' => 'Europe/Amsterdam',
            '(GMT+01:00) Belgrade' => 'Europe/Belgrade',
            '(GMT+01:00) Berlin' => 'Europe/Berlin',
            '(GMT+01:00) Bern' => 'Europe/Berlin',
            '(GMT+01:00) Bratislava' => 'Europe/Bratislava',
            '(GMT+01:00) Brussels' => 'Europe/Brussels',
            '(GMT+01:00) Budapest' => 'Europe/Budapest',
            '(GMT+01:00) Copenhagen' => 'Europe/Copenhagen',
            '(GMT+01:00) Ljubljana' => 'Europe/Ljubljana',
            '(GMT+01:00) Madrid' => 'Europe/Madrid',
            '(GMT+01:00) Paris' => 'Europe/Paris',
            '(GMT+01:00) Prague' => 'Europe/Prague',
            '(GMT+01:00) Rome' => 'Europe/Rome',
            '(GMT+01:00) Sarajevo' => 'Europe/Sarajevo',
            '(GMT+01:00) Skopje' => 'Europe/Skopje',
            '(GMT+01:00) Stockholm' => 'Europe/Stockholm',
            '(GMT+01:00) Vienna' => 'Europe/Vienna',
            '(GMT+01:00) Warsaw' => 'Europe/Warsaw',
            '(GMT+01:00) West Central Africa' => 'Africa/Lagos',
            '(GMT+01:00) Zagreb' => 'Europe/Zagreb',
            '(GMT+02:00) Athens' => 'Europe/Athens',
            '(GMT+02:00) Bucharest' => 'Europe/Bucharest',
            '(GMT+02:00) Cairo' => 'Africa/Cairo',
            '(GMT+02:00) Harare' => 'Africa/Harare',
            '(GMT+02:00) Helsinki' => 'Europe/Helsinki',
            '(GMT+02:00) Istanbul' => 'Europe/Istanbul',
            '(GMT+02:00) Jerusalem' => 'Asia/Jerusalem',
            '(GMT+02:00) Kyev' => 'Europe/Kiev',
            '(GMT+02:00) Minsk' => 'Europe/Minsk',
            '(GMT+02:00) Pretoria' => 'Africa/Johannesburg',
            '(GMT+02:00) Riga' => 'Europe/Riga',
            '(GMT+02:00) Sofia' => 'Europe/Sofia',
            '(GMT+02:00) Tallinn' => 'Europe/Tallinn',
            '(GMT+02:00) Vilnius' => 'Europe/Vilnius',
            '(GMT+03:00) Baghdad' => 'Asia/Baghdad',
            '(GMT+03:00) Kuwait' => 'Asia/Kuwait',
            '(GMT+03:00) Moscow' => 'Europe/Moscow',
            '(GMT+03:00) Nairobi' => 'Africa/Nairobi',
            '(GMT+03:00) Riyadh' => 'Asia/Riyadh',
            '(GMT+03:00) St. Petersburg' => 'Europe/Moscow',
            '(GMT+03:00) Volgograd' => 'Europe/Volgograd',
            '(GMT+03:30) Tehran' => 'Asia/Tehran',
            '(GMT+04:00) Abu Dhabi' => 'Asia/Muscat',
            '(GMT+04:00) Baku' => 'Asia/Baku',
            '(GMT+04:00) Muscat' => 'Asia/Muscat',
            '(GMT+04:00) Tbilisi' => 'Asia/Tbilisi',
            '(GMT+04:00) Yerevan' => 'Asia/Yerevan',
            '(GMT+04:30) Kabul' => 'Asia/Kabul',
            '(GMT+05:00) Ekaterinburg' => 'Asia/Yekaterinburg',
            '(GMT+05:00) Islamabad' => 'Asia/Karachi',
            '(GMT+05:00) Karachi' => 'Asia/Karachi',
            '(GMT+05:00) Tashkent' => 'Asia/Tashkent',
            '(GMT+05:30) Chennai' => 'Asia/Kolkata',
            '(GMT+05:30) Kolkata' => 'Asia/Kolkata',
            '(GMT+05:30) Mumbai' => 'Asia/Kolkata',
            '(GMT+05:30) New Delhi' => 'Asia/Kolkata',
            '(GMT+05:45) Kathmandu' => 'Asia/Kathmandu',
            '(GMT+06:00) Almaty' => 'Asia/Almaty',
            '(GMT+06:00) Astana' => 'Asia/Dhaka',
            '(GMT+06:00) Dhaka' => 'Asia/Dhaka',
            '(GMT+06:00) Novosibirsk' => 'Asia/Novosibirsk',
            '(GMT+06:00) Sri Jayawardenepura' => 'Asia/Colombo',
            '(GMT+06:30) Rangoon' => 'Asia/Rangoon',
            '(GMT+07:00) Bangkok' => 'Asia/Bangkok',
            '(GMT+07:00) Hanoi' => 'Asia/Bangkok',
            '(GMT+07:00) Jakarta' => 'Asia/Jakarta',
            '(GMT+07:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
            '(GMT+08:00) Beijing' => 'Asia/Hong_Kong',
            '(GMT+08:00) Chongqing' => 'Asia/Chongqing',
            '(GMT+08:00) Hong Kong' => 'Asia/Hong_Kong',
            '(GMT+08:00) Irkutsk' => 'Asia/Irkutsk',
            '(GMT+08:00) Kuala Lumpur' => 'Asia/Kuala_Lumpur',
            '(GMT+08:00) Perth' => 'Australia/Perth',
            '(GMT+08:00) Singapore' => 'Asia/Singapore',
            '(GMT+08:00) Taipei' => 'Asia/Taipei',
            '(GMT+08:00) Ulaan Bataar' => 'Asia/Irkutsk',
            '(GMT+08:00) Urumqi' => 'Asia/Urumqi',
            '(GMT+09:00) Osaka' => 'Asia/Tokyo',
            '(GMT+09:00) Sapporo' => 'Asia/Tokyo',
            '(GMT+09:00) Seoul' => 'Asia/Seoul',
            '(GMT+09:00) Tokyo' => 'Asia/Tokyo',
            '(GMT+09:00) Yakutsk' => 'Asia/Yakutsk',
            '(GMT+09:30) Adelaide' => 'Australia/Adelaide',
            '(GMT+09:30) Darwin' => 'Australia/Darwin',
            '(GMT+10:00) Brisbane' => 'Australia/Brisbane',
            '(GMT+10:00) Canberra' => 'Australia/Sydney',
            '(GMT+10:00) Guam' => 'Pacific/Guam',
            '(GMT+10:00) Hobart' => 'Australia/Hobart',
            '(GMT+10:00) Melbourne' => 'Australia/Melbourne',
            '(GMT+10:00) Port Moresby' => 'Pacific/Port_Moresby',
            '(GMT+10:00) Sydney' => 'Australia/Sydney',
            '(GMT+10:00) Vladivostok' => 'Asia/Vladivostok',
            '(GMT+11:00) Magadan' => 'Asia/Magadan',
            '(GMT+11:00) New Caledonia' => 'Asia/Magadan',
            '(GMT+11:00) Solomon Is.' => 'Asia/Magadan',
            '(GMT+12:00) Auckland' => 'Pacific/Auckland',
            '(GMT+12:00) Fiji' => 'Pacific/Fiji',
            '(GMT+12:00) Kamchatka' => 'Asia/Kamchatka',
            '(GMT+12:00) Marshall Is.' => 'Pacific/Fiji',
            '(GMT+12:00) Wellington' => 'Pacific/Auckland',
            '(GMT+13:00) Nuku\'alofa' => 'Pacific/Tongatapu'
        );
    }
}
