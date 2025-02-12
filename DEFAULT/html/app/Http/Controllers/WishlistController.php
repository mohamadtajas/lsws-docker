<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wishlist\IdRequest;
use App\Http\Requests\Wishlist\StoreRequest;
use App\Models\Provider;
use Auth;
use App\Models\Wishlist;

class WishlistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = [];
        $providers = [];
        $verified_sellers = verified_sellers_id();
        $wishlists = Wishlist::where('user_id', Auth::user()->id)
            ->where(function ($query) use ($verified_sellers) {
                $query->where('trendyol', 1)
                    ->orWhereNotNull('provider_id')
                    ->orWhereIn('product_id', function ($query) use ($verified_sellers) {
                        $query->select('id')
                            ->from('products')
                            ->where('approved', '1')->where('published', 1)
                            ->when(!addon_is_activated('wholesale'), function ($q1) {
                                $q1->where('wholesale_product', 0);
                            })
                            ->when(!addon_is_activated('auction'), function ($q2) {
                                $q2->where('auction_product', 0);
                            })
                            ->when(get_setting('vendor_system_activation') == 0, function ($q3) {
                                $q3->where('added_by', 'admin');
                            })
                            ->when(get_setting('vendor_system_activation') == 1, function ($q4) use ($verified_sellers) {
                                $q4->where(function ($p1) use ($verified_sellers) {
                                    $p1->where('added_by', 'admin')->orWhere(function ($p2) use ($verified_sellers) {
                                        $p2->whereIn('user_id', $verified_sellers);
                                    });
                                });
                            });
                    });
            })
            ->paginate(5);
        if ($wishlists->count() > 0) {
            foreach ($wishlists as $item) {
                if (isset($item['provider_id'])) {
                    $provider = Provider::find($item['provider_id']);
                    if ($provider) {
                        $providers[$item['provider_id']][$item['id']] = $item['product_id'];
                    }
                } elseif ($item['trendyol'] == 1) {
                    $accessToken = trendyol_search_account_login();
                    $products[$item['id']] = trendyol_product_details(
                        $accessToken,
                        $item['product_id'],
                        $item['urunNo'],
                        1,
                    );
                } else {
                    $products[$item['id']] = get_single_product($item['product_id']);
                }
            }
            if (count($providers) > 0) {
                foreach ($providers as $provider => $providerProducts) {
                    $provider = Provider::find($provider);
                    if ($provider) {
                        $products += $provider->service()->productsDetails($providerProducts);
                    }
                }
            }
        }
        return view('frontend.user.view_wishlist', compact('products', 'wishlists'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        if (Auth::check()) {
            $wishlist = Wishlist::where('user_id', Auth::user()->id)->where('product_id', $request->id)->first();
            if ($wishlist == null) {
                $wishlist = new Wishlist;
                $wishlist->user_id = Auth::user()->id;
                $wishlist->product_id = $request->id;
                $wishlist->urunNo = $request->urunNo ?? null;
                $wishlist->trendyol = $request->trendyol ?? 0;
                $wishlist->provider_id = $request->provider ?? null;
                $wishlist->save();
            }
            return view('frontend.' . get_setting('homepage_select') . '.partials.wishlist');
        }
        return 0;
    }

    public function remove(IdRequest $request)
    {
        $wishlist = Wishlist::findOrFail($request->id);
        if ($wishlist != null) {
            if (Wishlist::destroy($request->id)) {
                return view('frontend.' . get_setting('homepage_select') . '.partials.wishlist');
            }
        }
    }
}
