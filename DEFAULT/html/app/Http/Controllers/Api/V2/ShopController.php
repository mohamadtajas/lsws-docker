<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Shop\IndexRequest;
use App\Http\Resources\V2\ProductCollection;
use App\Http\Resources\V2\ProductMiniCollection;
use App\Http\Resources\V2\ShopCollection;
use App\Http\Resources\V2\ShopDetailsCollection;
use App\Models\Product;
use App\Models\Shop;
use App\Utility\SearchUtility;
use Cache;

class ShopController extends Controller
{
    public function index(IndexRequest $request)
    {
        $shop_query = Shop::query();

        if ($request->name != null && $request->name != "") {
            $shop_query->where("name", 'like', "%{$request->name}%");
            SearchUtility::store($request->name);
        }

        return new ShopCollection($shop_query->whereIn('user_id', verified_sellers_id())->paginate(10));
    }

    public function info(string $id)
    {
        return new ShopDetailsCollection(Shop::where('id', $id)->first());
    }

    public function shopOfUser(string $id)
    {
        return new ShopCollection(Shop::where('user_id', $id)->get());
    }

    public function allProducts(string $id)
    {
        $shop = Shop::findOrFail($id);
        return new ProductCollection(Product::where('user_id', $shop->user_id)->where('published',1)->latest()->paginate(10));
    }

    public function topSellingProducts(string $id)
    {
        $shop = Shop::findOrFail($id);

        return Cache::remember("app.top_selling_products-$id", 86400, function () use ($shop){
            return new ProductMiniCollection(Product::where('user_id', $shop->user_id)->where('published',1)->orderBy('num_of_sale', 'desc')->limit(10)->get());
        });
    }

    public function featuredProducts(string $id)
    {
        $shop = Shop::findOrFail($id);

        return Cache::remember("app.featured_products-$id", 86400, function () use ($shop){
            return new ProductMiniCollection(Product::where(['user_id' => $shop->user_id, 'seller_featured' => 1])->where('published',1)->latest()->limit(10)->get());
        });
    }

    public function newProducts(string $id)
    {
        $shop = Shop::findOrFail($id);

        return Cache::remember("app.new_products-$id", 86400, function () use ($shop){
            return new ProductMiniCollection(Product::where('user_id', $shop->user_id)->where('published',1)->orderBy('created_at', 'desc')->limit(10)->get());
        });
    }
}
