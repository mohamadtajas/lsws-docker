<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Wishlist\IdRequest;
use App\Http\Requests\Wishlist\StoreRequest;
use App\Http\Resources\V2\WishlistCollection;
use App\Models\Wishlist;
use App\Models\Product;
use App\Models\Provider;

class WishlistController extends Controller
{
    public function index()
    {
        $providers = [];
        $providers_products = [];
        $product_ids = Wishlist::where('user_id', auth()->user()->id)->pluck("product_id")->toArray();
        $existing_product_ids = Product::whereIn('id', $product_ids)->pluck("id")->toArray();

        $query = Wishlist::query();
        $query->where('user_id', auth()->user()->id)
            ->where(function ($query) use ($existing_product_ids) {
                $query->whereIn("product_id", $existing_product_ids)
                    ->orWhere('trendyol', '1')
                    ->orWhereNotNull('provider_id');
            });

            $providers = Wishlist::whereNotNull('provider_id')
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
        return new WishlistCollection($query->paginate(6) , $providers_products);
    }

    public function store(StoreRequest $request)
    {
        Wishlist::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'product_id' => $request->product_id,
                'urunNo' =>  $request->urunNo ?? null,
                'trendyol' => $request->trendyol ?? 0,
                'provider_id' => $request->provider ?? null
            ]
        );
        return response()->json(['message' => translate('Product is successfully added to your wishlist')], 201);
    }

    public function destroy(string $id)
    {
        try {
            Wishlist::destroy($id);
            return response()->json(['result' => true, 'message' => translate('Product is successfully removed from your wishlist')], 200);
        } catch (\Exception $e) {
            return response()->json(['result' => false, 'message' => $e->getMessage()], 200);
        }
    }

    public function add(StoreRequest $request)
    {
        $urunNo = $request->urunNo ?? null;
        $trendyol = $request->trendyol ?? 0;
        $provider = $request->provider ?? null;
        $product = Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id, 'urunNo' =>  $urunNo, 'trendyol' => $trendyol])->count();
        if ($product > 0) {
            return response()->json([
                'message' => translate('Product present in wishlist'),
                'is_in_wishlist' => true,
                'product_id' => (int)$request->product_id,
                'wishlist_id' => (int)Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id, 'urunNo' =>  $urunNo, 'trendyol' => $trendyol])->first()->id
            ], 200);
        } else {
            Wishlist::create(
                ['user_id' => auth()->user()->id, 'product_id' => $request->product_id, 'urunNo' =>  $urunNo, 'trendyol' => $trendyol, 'provider_id' => $provider]
            );

            return response()->json([
                'message' => translate('Product added to wishlist'),
                'is_in_wishlist' => true,
                'product_id' => (int)$request->product_id,
                'wishlist_id' => (int)Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id, 'urunNo' =>  $urunNo, 'trendyol' => $trendyol])->first()->id
            ], 200);
        }
    }

    public function remove(IdRequest $request)
    {
        $product = Wishlist::where(['product_id' => $request->product_id, 'user_id' =>  auth()->user()->id])->count();
        if ($product == 0) {
            return response()->json([
                'message' => translate('Product is not in wishlist'),
                'is_in_wishlist' => false,
                'product_id' => (int)$request->product_id,
                'wishlist_id' => 0
            ], 200);
        } else {
            Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id])->delete();

            return response()->json([
                'message' => translate('Product is removed from wishlist'),
                'is_in_wishlist' => false,
                'product_id' => (int)$request->product_id,
                'wishlist_id' => 0
            ], 200);
        }
    }

    public function isProductInWishlist(IdRequest $request)
    {
        $product = Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id])->count();
        if ($product > 0)
            return response()->json([
                'message' => translate('Product present in wishlist'),
                'is_in_wishlist' => true,
                'product_id' => (int)$request->product_id,
                'wishlist_id' => (int)Wishlist::where(['product_id' => $request->product_id, 'user_id' => auth()->user()->id])->first()->id
            ], 200);

        return response()->json([
            'message' => translate('Product is not present in wishlist'),
            'is_in_wishlist' => false,
            'product_id' => (int)$request->product_id,
            'wishlist_id' => 0
        ], 200);
    }
}
