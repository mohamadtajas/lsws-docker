<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Search\DeleteResaultRequest;
use App\Http\Requests\Search\GetListRequest;
use App\Models\Search;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Shop;
use App\Models\SearchResult;

class SearchSuggestionController extends Controller
{
    public function getList(GetListRequest $request)
    {
        $query_key = $request->query_key;
        $type = $request->type;

        $search_query  = Search::select('id', 'query', 'count');
        if ($query_key != "") {
            $search_query->where('query', 'like', "%{$query_key}%");
        }
        $searches = $search_query->orderBy('count', 'desc')->limit(10)->get();

        if ($type == "product") {
            $product_query = Product::query();
            if ($query_key != "") {
                $product_query->where(function ($query) use ($query_key) {
                    foreach (explode(' ', trim($query_key)) as $word) {
                        $query->where('name', 'like', '%' . $word . '%')->orWhere('tags', 'like', '%' . $word . '%')->orWhereHas('product_translations', function ($query) use ($word) {
                            $query->where('name', 'like', '%' . $word . '%');
                        });
                    }
                });
            }

            $products = filter_products($product_query)->limit(3)->get();
        }

        if ($type == "brands") {
            $brand_query = Brand::query();
            if ($query_key != "") {
                $brand_query->where('name', 'like', "%$query_key%");
            }

            $brands = $brand_query->limit(3)->get();
        }

        if ($type == "sellers") {
            $shop_query = Shop::query();
            if ($query_key != "") {
                $shop_query->where('name', 'like', "%$query_key%");
            }

            $shops = $shop_query->limit(3)->get();
        }



        $items = [];

        if ($query_key == "" && !empty(auth()->guard('sanctum')->user())) {
            $searchResults = SearchResult::where('user_id', auth()->guard('sanctum')->user()->id)->orderBy('id', 'desc')->get()->take(7);
            if (count($searchResults) > 0) {
                foreach ($searchResults as  $searchResult) {
                    $item = [];
                    $item['id'] = $searchResult->id;
                    $item['query'] = $searchResult->keyword;
                    $item['count'] = Search::where('query', $searchResult->keyword)->first()->count ?? 0;
                    $item['type'] = "recent";
                    $item['type_string'] = "recent";

                    $items[] = $item;
                }
            }
        }

        //shop push
        if ($type == "sellers" &&  !empty($shops)) {
            foreach ($shops as  $shop) {
                $item = [];
                $item['id'] = $shop->id;
                $item['query'] = $shop->name;
                $item['count'] = Search::where('query', $shop->name)->first()->count ?? 0;
                $item['type'] = "shop";
                $item['type_string'] = "Shop";

                $items[] = $item;
            }
        }

        //brand push
        if ($type == "brands" && !empty($brands)) {
            foreach ($brands as  $brand) {
                $item = [];
                $item['id'] = $brand->id;
                $item['query'] = $brand->name;
                $item['count'] = Search::where('query', $brand->name)->first()->count ?? 0;
                $item['type'] = "brand";
                $item['type_string'] = "Brand";

                $items[] = $item;
            }
        }

        //product push
        if ($type == "product" &&  !empty($products)) {
            foreach ($products as  $product) {
                $item = [];
                $item['id'] = $product->id;
                $item['query'] = $product->name;
                $item['count'] = Search::where('query', $product->name)->first()->count ?? 0;
                $item['type'] = "product";
                $item['type_string'] = "Product";

                $items[] = $item;
            }
        }

        //search push
        if (!empty($searches)) {
            foreach ($searches as  $search) {
                $item = [];
                $item['id'] = $search->id;
                $item['query'] = $search->query;
                $item['count'] = intval($search->count);
                $item['type'] = "search";
                $item['type_string'] = "Search";

                $items[] = $item;
            }
        }

        if ($query_key != "" && sizeof($items) < 5) {
            $trendyol_products = trendyol_products($query_key, 1)['products'];
            $i = 0;
            foreach ($trendyol_products as $product) {
                if ($i < 5) {
                    $item = [];
                    $item['id'] = $product['id'];
                    $item['query'] = $product['name'];
                    $item['count'] =  Search::where('query', $product['name'])->first()->count ?? 0;
                    $item['type'] = "product";
                    $item['type_string'] = "product";

                    $items[] = $item;
                    $i++;
                }
            }
        }

        return $items; // should return a valid json of search list;
    }

    public function delete_search_result(DeleteResaultRequest $request)
    {
        $id = $request->id;
        if (!empty(auth()->guard('sanctum')->user())) {
            $user_id = auth()->guard('sanctum')->user()->id;
            $searchResult = SearchResult::where('user_id', $user_id)->find($id);
            if ($searchResult) {
                $searchResult->delete();
                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false]);
            }
        }
    }
}
