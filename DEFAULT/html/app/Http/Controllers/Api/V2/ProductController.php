<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Product\GetPriceRequest;
use App\Http\Requests\Product\NameRequest;
use App\Http\Requests\Product\SearchRequest;
use App\Http\Requests\Product\ShowRequest;
use Cache;
use App\Models\Shop;
use App\Models\Color;
use App\Models\Product;
use App\Models\FlashDeal;
use App\Models\SearchResult;
use App\Utility\SearchUtility;
use App\Utility\CategoryUtility;
use App\Http\Resources\V2\FlashDealCollection;
use App\Http\Resources\V2\ProductMiniCollection;
use App\Http\Resources\V2\ProductDetailCollection;
use App\Http\Resources\V2\ProviderProductMiniCollection;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\AttributeValue;
use App\Http\Resources\V2\Seller\BrandCollection;
use App\Models\Provider;
use App\Models\TrendyolCategory;
use App\Models\User;
use DB;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index()
    {
        return new ProductMiniCollection(Product::latest()->paginate(10));
    }

    public function show(ShowRequest $request, $id)
    {
        if (isset($request->trendyol) && $request->trendyol == 1) {
            return $this->productTrendyol($id, $request->urunNo);
        }
        return new ProductDetailCollection(Product::where('id', $id)->get());
    }

    public function provider(string $provider, string $id)
    {
        $provider = Provider::where('name', $provider)->first();
        $detailedProduct = $provider->Service()->productDetails($id);
        if (!empty($detailedProduct)) {
            $category = Category::find($detailedProduct['categoryId']);
            $category = $category ?: $provider->Service()->category($detailedProduct['categoryId']);
            $relatedProducts = $provider->Service()->categoryProducts($category->external_id);
            return response()->json([
                'success'    => true,
                'status'     => 200,
                'data'       => [$detailedProduct],
                'propreties' => [],
                'relatedProducts'    => $relatedProducts
            ]);
        } else {
            return response()->json([
                'success'    => false,
                'status'     => 404,
                'data'       => [],
                'propreties' => []
            ]);
        }
    }

    public function productTrendyol($id, $urunNo = null)
    {
        $propreties = [];
        $relatedProducts = [];
        $trendyolFilters = [];
        $accessToken = trendyol_search_account_login();
        $detailedProduct = trendyol_product_details($accessToken, $id, $urunNo, 10);
        $detailedProduct = array_combine(
            array_keys($detailedProduct),
            array_map(function ($value, $key) {
                if ($key === 'unit_price') {
                    return single_price($value);
                } elseif ($key === 'new_price') {
                    return number_format(convert_price($value), 2);
                } else {
                    return $value;
                }
            }, $detailedProduct, array_keys($detailedProduct))
        );
        if (!empty($detailedProduct)) {
            $propreties = trendyol_product_propreties($accessToken, $detailedProduct['urunGrupNo']);
            if ($detailedProduct['gender'] != 3 && $detailedProduct['gender'] != 0) {
                $trendyolFilters['cns'] = $detailedProduct['gender'];
            }
            $page = rand(1, 9);
            $relatedProducts = trendyol_category_products($detailedProduct['categoryId'], $page, null, $trendyolFilters)['products'];
            return response()->json([
                'success'    => true,
                'status'     => 200,
                'data'       => [$detailedProduct],
                'propreties' => $propreties,
                'relatedProducts'    => $relatedProducts
            ]);
        } else {
            return response()->json([
                'success'    => false,
                'status'     => 404,
                'data'       => [],
                'propreties' => []
            ]);
        }
    }


    public function getPrice(GetPriceRequest $request)
    {
        $product = Product::findOrFail($request->id);
        $str = '';
        $tax = 0;
        $quantity = 1;



        if ($request->has('quantity') && $request->quantity != null) {
            $quantity = $request->quantity;
        }

        if ($request->has('color') && $request->color != null) {
            $str = Color::where('code', '#' . $request->color)->first()->name;
        }

        $var_str = str_replace(',', '-', $request->variants);
        $var_str = str_replace(' ', '', $var_str);

        if ($var_str != "") {
            $temp_str = $str == "" ? $var_str : '-' . $var_str;
            $str .= $temp_str;
        }

        $product_stock = $product->stocks->where('variant', $str)->first();
        $price = $product_stock->price;


        if ($product->wholesale_product) {
            $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $quantity)->where('max_qty', '>=', $quantity)->first();
            if ($wholesalePrice) {
                $price = $wholesalePrice->price;
            }
        }

        $stock_qty = $product_stock->qty;
        $stock_txt = $product_stock->qty;
        $max_limit = $product_stock->qty;

        if ($stock_qty >= 1 && $product->min_qty <= $stock_qty) {
            $in_stock = 1;
        } else {
            $in_stock = 0;
        }

        //Product Stock Visibility
        if ($product->stock_visibility_state == 'text') {
            if ($stock_qty >= 1 && $product->min_qty < $stock_qty) {
                $stock_txt = translate('In Stock');
            } else {
                $stock_txt = translate('Out Of Stock');
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

        // taxes
        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }

        $price += $tax;

        return response()->json(

            [
                'result' => true,
                'data' => [
                    'price' => single_price($price * $quantity),
                    'stock' => $stock_qty,
                    'stock_txt' => $stock_txt,
                    'digital' => $product->digital,
                    'variant' => $str,
                    'variation' => $str,
                    'max_limit' => $max_limit,
                    'in_stock' => $in_stock,
                    'image' => $product_stock->image == null ? "" : uploaded_asset($product_stock->image)
                ]

            ]
        );
    }

    public function seller($id, NameRequest $request)
    {
        $shop = Shop::findOrFail($id);
        $products = Product::where('added_by', 'seller')->where('user_id', $shop->user_id);
        if ($request->name != "" || $request->name != null) {
            $products = $products->where('name', 'like', '%' . $request->name . '%');
        }
        $products->where('published', 1);
        return new ProductMiniCollection($products->latest()->paginate(10));
    }

    public function category($id, SearchRequest $request)
    {
        $category_ids = [];
        $brand_ids = [];
        $trendyolSort = null;
        $trendyolFilters = [];
        $trendyolFiltersArray = [];
        $allTrendyolFilters = [];
        $attributeValues = [];
        $categories = [];
        $brands = [];
        $selected_attribute_values = array();
        $colors = [];
        $selected_color = null;

        if ($request->categories != null && $request->categories != "") {
            foreach ($request->categories as $requestCategory) {
                array_push($category_ids, (int) $requestCategory);
            }
        }

        if ($request->brands != null && $request->brands != "") {
            foreach ($request->brands as $requestBrand) {
                array_push($brand_ids, (int) $requestBrand);
            }
        }

        $sort_by = $request->sort_key;
        $min = $request->min;
        $max = $request->max;
        $min_trendyol = $request->min;
        $max_trendyol = $request->max;


        $category = Category::find($id);
        $products = $category->products()->physical();

        if ($min != null && $min != "" && is_numeric($min)) {
            $products->where('unit_price', '>=', $min);
        }

        if ($max != null && $max != "" && is_numeric($max)) {
            $products->where('unit_price', '<=', $max);
        }

        $trendyol_tax_percent = (new TrendyolCategory())->avg('percent_tax');
        $trendyol_tax_flex = (new TrendyolCategory())->avg('flat_tax');
        if ($min != null && $min != "" && is_numeric($min) && $max != null && $max != "" && is_numeric($max)) {
            if ($min > 0 && $trendyol_tax_percent > 0) {
                if ($min / $trendyol_tax_percent - $trendyol_tax_flex > 0) {
                    $min_trendyol = $min / (1 +  $trendyol_tax_percent) - $trendyol_tax_flex;
                } else {
                    $min_trendyol = 0;
                }
            }

            if ($max > 0 && $trendyol_tax_percent > 0) {
                if ($max / $trendyol_tax_percent - $trendyol_tax_flex > 0) {
                    $max_trendyol = $max / (1 +  $trendyol_tax_percent) - $trendyol_tax_flex;
                } else {
                    $max_trendyol = 0;
                }
            }
            $trendyolFilters['fyt'] = round($min_trendyol, 0) . '-' . round($max_trendyol, 0);
        }

        switch ($sort_by) {
            case 'price_low_to_high':
                $products->orderBy('unit_price', 'asc');
                $trendyolSort = 'PRICE_BY_ASC';
                break;

            case 'price_high_to_low':
                $products->orderBy('unit_price', 'desc');
                $trendyolSort = 'PRICE_BY_DESC';
                break;

            case 'new_arrival':
                $products->orderBy('created_at', 'desc');
                $trendyolSort = 'MOST_RECENT';
                break;

            case 'popularity':
                $products->orderBy('num_of_sale', 'desc');
                $trendyolSort = 'MOST_FAVOURITE';
                break;

            case 'top_rated':
                $products->orderBy('rating', 'desc');
                $trendyolSort = 'MOST_RATED';
                break;

            default:
                $products->orderBy('created_at', 'desc');
                break;
        }
        if (isset($request->color) && $request->color != null) {
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
                $trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id] = isset($trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id]) ? $trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id] . ',' . $attValue->trendyol_id : $attValue->trendyol_id;
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

        if ($request->name != "" || $request->name != null) {
            $products = $products->where('name', 'like', '%' . $request->name . '%');
        }

        if (!empty($category_ids) && count($category_ids) > 0) {
            $trendyolCategoryFilters = Category::whereIn('id', $category_ids)->without('category_translations')->get();

            foreach ($trendyolCategoryFilters as $key => $trendyolCategoryFilter) {
                $trendyolFilters['ktg'] = isset($trendyolFilters['ktg']) ? $trendyolFilters['ktg'] . ',' . $trendyolCategoryFilter->trendyol_id : $trendyolCategoryFilter->trendyol_id;
            }
        }

        if (!empty($brand_ids) && count($brand_ids) > 0) {
            $trendyolBrandFilters = Brand::whereIn('id', $brand_ids)->get();

            foreach ($trendyolBrandFilters as $key => $trendyolBrandFilter) {
                $trendyolFilters['mrk'] = isset($trendyolFilters['mrk']) ? $trendyolFilters['mrk'] . ',' . $trendyolBrandFilter->trendyol_id : $trendyolBrandFilter->trendyol_id;
            }
        }

        if (!isset($trendyolFilters['cns']) && isset($category->trendyol_cns)) {
            $trendyolFilters['cns'] = $category->trendyol_cns;
        }
        $trendyol = trendyol_category_products($category->trendyol_id, $request->page ?? 1, $trendyolSort, $trendyolFilters, $request->name);
        $trendyolProducts = isset($trendyol['products']) ? $trendyol['products'] : [];

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

        if (isset($trendyol['brands']) && count($trendyol['brands']) > 0) {
            $trendyolBrands = [];
            foreach ($trendyol['brands'][0]->degerler as $attribute_value) {
                array_push($trendyolBrands, $attribute_value->id);
            }
            $brands = Brand::WhereIn('trendyol_id', $trendyolBrands)->orWhereIn('id', $products->pluck('brand_id')->toArray())->get();
        }

        if (isset($trendyol['filters']) && count($trendyol['filters']) > 0) {
            foreach ($trendyol['filters'] as $filter) {
                $attrebute = Attribute::firstOrCreate([
                    'name'            => $filter->baslik,
                    'trendyol_id'     => $filter->filterKey,
                    'trendyol_urlKey' => $filter->urlKey
                ]);
                $attrebute->name = $attrebute->getTranslation('name');
                array_push(
                    $allTrendyolFilters,
                    $attrebute
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
                        if (isset($attributeValues[$attrebute->id])) {
                            array_push($attributeValues[$attrebute->id], $attrttributeValue);
                        } else {
                            $attributeValues[$attrebute->id] = [$attrttributeValue];
                        }
                    }
                }
            }
        }
        return new ProductMiniCollection(filter_products($products)->paginate(24, ['*'], 'page', null, $trendyol['total']), $trendyolProducts, $allTrendyolFilters, $attributeValues, $colors, $categories, $brands, $category);
    }

    public function providerCategory(string $provider, string $id)
    {
        $provider = Provider::where('name', $provider)->first();
        $category = Category::find($id);
        $category = $category ?: $provider->Service()->category($id);
        $products = $provider->Service()->categoryProducts($category->external_id);
        return new ProviderProductMiniCollection($products, [], [], [],  [$category], [], []);
    }

    public function brand($id, SearchRequest $request)
    {
        $category_ids = [];
        $trendyolSort = null;
        $trendyolFilters = [];
        $trendyolFiltersArray = [];
        $allTrendyolFilters = [];
        $attributeValues = [];
        $categories = [];
        $brands = [];
        $selected_attribute_values = array();
        $colors = [];
        $selected_color = null;

        if ($request->categories != null && $request->categories != "") {
            foreach ($request->categories as $requestCategory) {
                array_push($category_ids, (int) $requestCategory);
            }
        }

        if ($request->brands != null && $request->brands != "") {
            foreach ($request->brands as $requestBrand) {
                array_push($brand_ids, (int) $requestBrand);
            }
        }

        $sort_by = $request->sort_key;
        $min = $request->min;
        $max = $request->max;
        $min_trendyol = $request->min;
        $max_trendyol = $request->max;

        $brand = Brand::where('id', $id)->first();
        $products = Product::where('brand_id', $brand->id)->physical();

        if ($min != null && $min != "" && is_numeric($min)) {
            $products->where('unit_price', '>=', $min);
        }

        if ($max != null && $max != "" && is_numeric($max)) {
            $products->where('unit_price', '<=', $max);
        }
        $trendyol_tax_percent = (new TrendyolCategory())->avg('percent_tax');
        $trendyol_tax_flex = (new TrendyolCategory())->avg('flat_tax');
        if ($min != null && $min != "" && is_numeric($min) && $max != null && $max != "" && is_numeric($max)) {
            if ($min > 0 && $trendyol_tax_percent > 0) {
                if ($min / $trendyol_tax_percent - $trendyol_tax_flex > 0) {
                    $min_trendyol = $min / (1 +  $trendyol_tax_percent) - $trendyol_tax_flex;
                } else {
                    $min_trendyol = 0;
                }
            }

            if ($max > 0 && $trendyol_tax_percent > 0) {
                if ($max / $trendyol_tax_percent - $trendyol_tax_flex > 0) {
                    $max_trendyol = $max / (1 +  $trendyol_tax_percent) - $trendyol_tax_flex;
                } else {
                    $max_trendyol = 0;
                }
            }
            $trendyolFilters['fyt'] = round($min_trendyol, 0) . '-' . round($max_trendyol, 0);
        }

        switch ($sort_by) {
            case 'price_low_to_high':
                $products->orderBy('unit_price', 'asc');
                $trendyolSort = 'PRICE_BY_ASC';
                break;

            case 'price_high_to_low':
                $products->orderBy('unit_price', 'desc');
                $trendyolSort = 'PRICE_BY_DESC';
                break;

            case 'new_arrival':
                $products->orderBy('created_at', 'desc');
                $trendyolSort = 'MOST_RECENT';
                break;

            case 'popularity':
                $products->orderBy('num_of_sale', 'desc');
                $trendyolSort = 'MOST_FAVOURITE';
                break;

            case 'top_rated':
                $products->orderBy('rating', 'desc');
                $trendyolSort = 'MOST_RATED';
                break;

            default:
                $products->orderBy('created_at', 'desc');
                break;
        }
        if (isset($request->color) && $request->color != null) {
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
                $trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id] = isset($trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id]) ? $trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id] . ',' . $attValue->trendyol_id : $attValue->trendyol_id;
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

        if ($request->name != "" || $request->name != null) {
            $products = $products->where('name', 'like', '%' . $request->name . '%');
        }

        if (!empty($category_ids) && count($category_ids) > 0) {
            $trendyolCategoryFilters = Category::whereIn('id', $category_ids)->without('category_translations')->get();

            foreach ($trendyolCategoryFilters as $key => $trendyolCategoryFilter) {
                $trendyolFilters['ktg'] = isset($trendyolFilters['ktg']) ? $trendyolFilters['ktg'] . ',' . $trendyolCategoryFilter->trendyol_id : $trendyolCategoryFilter->trendyol_id;
            }
        }

        $trendyol = trendyol_brand_products($brand->trendyol_id, $request->page ?? 1, $trendyolSort, $trendyolFilters, $request->name);
        $trendyolProducts = isset($trendyol['products']) ? $trendyol['products'] : [];

        if (isset($trendyol['categories']) && count($trendyol['categories']) > 0) {
            $trendyolCategories = [];
            foreach ($trendyol['categories'][0]->degerler as $attribute_value) {
                array_push($trendyolCategories, $attribute_value->id);
            }

            $categories = Category::whereIn('trendyol_id', $trendyolCategories)
                ->orWhereIn('id', $products->pluck('category_id')->toArray())
                ->select('id', 'name', 'parent_id')
                ->without('category_translations')
                ->orderBy('parent_id', 'desc')
                ->orderBy('order_level', 'desc');

            // Group categories by their parent_id
            $groupedCategories = $categories->get()->groupBy('parent_id');

            // Get the parent categories (parent_id != null)
            $parentCategories = Category::whereIn('id', $categories->pluck('parent_id'))->select('id', 'name')
                ->without('category_translations')
                ->orderBy('parent_id', 'desc')
                ->orderBy('order_level', 'desc')
                ->get();

            // Prepare the output
            $output = [];
            if ($parentCategories) {
                foreach ($parentCategories as $key => $parent) {
                    // Add parent category to output
                    $output[$key]['parent'] = $parent;

                    // Add children of the parent category (if any)
                    $output[$key]['children'] = $groupedCategories->get($parent->id) ?: [];
                }
            }

            $categories = $output;
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
                $attrebute = Attribute::firstOrCreate([
                    'name'            => $filter->baslik,
                    'trendyol_id'     => $filter->filterKey,
                    'trendyol_urlKey' => $filter->urlKey
                ]);
                $attrebute->name = $attrebute->getTranslation('name');
                array_push(
                    $allTrendyolFilters,
                    $attrebute
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
                        if (isset($attributeValues[$attrebute->id])) {
                            array_push($attributeValues[$attrebute->id], $attrttributeValue);
                        } else {
                            $attributeValues[$attrebute->id] = [$attrttributeValue];
                        }
                    }
                }
            }
        }

        return new ProductMiniCollection(filter_products($products)->paginate(24, ['*'], 'page', null, $trendyol['total']), $trendyolProducts, $allTrendyolFilters, $attributeValues, $colors, $categories, $brands, null, $brand);
    }

    public function getBrands()
    {
        $brands = Brand::all();

        return BrandCollection::collection($brands);
    }

    public function todaysDeal()
    {
        // return Cache::remember('app.todays_deal', 86400, function () {
        $products = Product::where('todays_deal', 1)->physical();
        return new ProductMiniCollection(filter_products($products)->limit(20)->latest()->get());
        // });
    }

    public function flashDeal()
    {
        return Cache::remember('app.flash_deals', 86400, function () {
            $flash_deals = FlashDeal::where('status', 1)->where('featured', 1)->where('start_date', '<=', strtotime(date('d-m-Y')))->where('end_date', '>=', strtotime(date('d-m-Y')))->get();
            return new FlashDealCollection($flash_deals);
        });
    }

    public function featured()
    {
        $products = Product::where('featured', 1)->physical();
        return new ProductMiniCollection(filter_products($products)->latest()->paginate(10));
    }

    public function inhouse()
    {
        $products = Product::where('added_by', 'admin');
        return new ProductMiniCollection(filter_products($products)->latest()->paginate(12));
    }

    public function digital()
    {
        $products = Product::digital();
        return new ProductMiniCollection(filter_products($products)->latest()->paginate(10));
    }

    public function bestSeller()
    {
        // return Cache::remember('app.best_selling_products', 86400, function () {
        $products = Product::orderBy('num_of_sale', 'desc')->physical();
        return new ProductMiniCollection(filter_products($products)->limit(20)->get());
        // });
    }

    public function related($id)
    {
        $product = Product::find($id);
        $products = Product::where('category_id', $product->category_id)->where('id', '!=', $id)->physical();
        return new ProductMiniCollection(filter_products($products)->limit(10)->get());
    }

    public function topFromSeller($id)
    {
        // return Cache::remember("app.top_from_this_seller_products-$id", 86400, function () use ($id) {
        $product = Product::find($id);
        $products = Product::where('user_id', $product->user_id)->orderBy('num_of_sale', 'desc')->physical();
        return new ProductMiniCollection(filter_products($products)->limit(10)->get());
        // });
    }


    public function search(SearchRequest $request)
    {
        $category_ids = [];
        $brand_ids = [];
        $trendyolSort = null;
        $trendyolFilters = [];
        $trendyolFiltersArray = [];
        $allTrendyolFilters = [];
        $attributeValues = [];
        $categories = [];
        $brands = [];
        $selected_attribute_values = array();
        $colors = [];
        $selected_color = null;

        if ($request->categories != null && $request->categories != "") {
            foreach ($request->categories as $requestCategory) {
                array_push($category_ids, (int) $requestCategory);
            }
        }

        if ($request->brands != null && $request->brands != "") {
            foreach ($request->brands as $requestBrand) {
                array_push($brand_ids, (int) $requestBrand);
            }
        }

        $sort_by = $request->sort_key;
        $name = $request->name;
        $min = $request->min;
        $max = $request->max;
        $min_trendyol = $request->min;
        $max_trendyol = $request->max;

        if (!empty(auth()->guard('sanctum')->user()) && $name != "") {
            $searchResult = SearchResult::where('user_id', auth()->guard('sanctum')->user()->id)->where('keyword', $name)->first();
            if (!$searchResult) {
                $searchResult = SearchResult::create([
                    'user_id' => auth()->guard('sanctum')->user()->id,
                    'keyword' => $name
                ]);
            }
        }

        $products = Product::query();

        $products->where('published', 1)->physical();

        if (!empty($brand_ids)) {
            $products->whereIn('brand_id', $brand_ids);
        }

        if (!empty($category_ids)) {
            $n_cid = [];
            foreach ($category_ids as $cid) {
                $n_cid = array_merge($n_cid, CategoryUtility::children_ids($cid));
            }

            if (!empty($n_cid)) {
                $category_ids = array_merge($category_ids, $n_cid);
            }

            $products->whereIn('category_id', $category_ids);
        }

        if ($name != null && $name != "") {
            // $products->where(function ($query) use ($name) {
            //     foreach (explode(' ', trim($name)) as $word) {
            //         $query->where('name', 'like', '%' . $word . '%')->orWhere('tags', 'like', '%' . $word . '%')->orWhereHas('product_translations', function ($query) use ($word) {
            //             $query->where('name', 'like', '%' . $word . '%');
            //         });
            //     }
            // });

            $products->where(function ($query) use ($name) {
                foreach (explode(' ', trim($name)) as $word) {
                    $query->whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$word . '*'])
                        ->orWhereRaw("MATCH(tags) AGAINST(? IN BOOLEAN MODE)", [$word . '*'])
                        ->orWhereHas('product_translations', function ($subQuery) use ($word) {
                            $subQuery->whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$word . '*']);
                        })
                        ->orWhereHas('stocks', function ($subQuery) use ($word) {
                            $subQuery->whereRaw("MATCH(sku) AGAINST(? IN BOOLEAN MODE)", [$word . '*']);
                        });
                }
            });


            SearchUtility::store($name);
            $case1 = $name . '%';
            $case2 = '%' . $name . '%';

            $products->orderByRaw("CASE
                WHEN name LIKE '$case1' THEN 1
                WHEN name LIKE '$case2' THEN 2
                ELSE 3
                END");
        }

        if ($min != null && $min != "" && is_numeric($min)) {
            $products->where('unit_price', '>=', $min);
        }

        if ($max != null && $max != "" && is_numeric($max)) {
            $products->where('unit_price', '<=', $max);
        }
        $trendyol_tax_percent = (new TrendyolCategory())->avg('percent_tax');
        $trendyol_tax_flex = (new TrendyolCategory())->avg('flat_tax');
        if ($min != null && $min != "" && is_numeric($min) && $max != null && $max != "" && is_numeric($max)) {
            if ($min > 0 && $trendyol_tax_percent > 0) {
                if ($min / $trendyol_tax_percent - $trendyol_tax_flex > 0) {
                    $min_trendyol = $min / (1 +  $trendyol_tax_percent) - $trendyol_tax_flex;
                } else {
                    $min_trendyol = 0;
                }
            }

            if ($max > 0 && $trendyol_tax_percent > 0) {
                if ($max / $trendyol_tax_percent - $trendyol_tax_flex > 0) {
                    $max_trendyol = $max / (1 +  $trendyol_tax_percent) - $trendyol_tax_flex;
                } else {
                    $max_trendyol = 0;
                }
            }
            $trendyolFilters['fyt'] = round($min_trendyol, 0) . '-' . round($max_trendyol, 0);
        }

        switch ($sort_by) {
            case 'price_low_to_high':
                $products->orderBy('unit_price', 'asc');
                $trendyolSort = 'PRICE_BY_ASC';
                break;

            case 'price_high_to_low':
                $products->orderBy('unit_price', 'desc');
                $trendyolSort = 'PRICE_BY_DESC';
                break;

            case 'new_arrival':
                $products->orderBy('created_at', 'desc');
                $trendyolSort = 'MOST_RECENT';
                break;

            case 'popularity':
                $products->orderBy('num_of_sale', 'desc');
                $trendyolSort = 'MOST_FAVOURITE';
                break;

            case 'top_rated':
                $products->orderBy('rating', 'desc');
                $trendyolSort = 'MOST_RATED';
                break;

            default:
                $products->orderBy('created_at', 'desc');
                break;
        }
        if (isset($request->color) && $request->color != null) {
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
                $trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id] = isset($trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id]) ? $trendyolFiltersArray[$attValue->attribute->trendyol_urlKey][$attValue->attribute->trendyol_id] . ',' . $attValue->trendyol_id : $attValue->trendyol_id;
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

        if (!empty($category_ids) && count($category_ids) > 0) {
            $trendyolCategoryFilters = Category::whereIn('id', $category_ids)->without('category_translations')->get();

            foreach ($trendyolCategoryFilters as $key => $trendyolCategoryFilter) {
                $trendyolFilters['ktg'] = isset($trendyolFilters['ktg']) ? $trendyolFilters['ktg'] . ',' . $trendyolCategoryFilter->trendyol_id : $trendyolCategoryFilter->trendyol_id;
            }
        }

        if (!empty($brand_ids) && count($brand_ids) > 0) {
            $trendyolBrandFilters = Brand::whereIn('id', $brand_ids)->get();

            foreach ($trendyolBrandFilters as $key => $trendyolBrandFilter) {
                $trendyolFilters['mrk'] = isset($trendyolFilters['mrk']) ? $trendyolFilters['mrk'] . ',' . $trendyolBrandFilter->trendyol_id : $trendyolBrandFilter->trendyol_id;
            }
        }

        $trendyol = trendyol_products($request->name, $request->page ?? 1, $trendyolSort, $trendyolFilters);
        $trendyolProducts = isset($trendyol['products']) ? $trendyol['products'] : [];

        if (isset($trendyol['categories']) && count($trendyol['categories']) > 0) {
            $trendyolCategories = [];
            foreach ($trendyol['categories'][0]->degerler as $attribute_value) {
                array_push($trendyolCategories, $attribute_value->id);
            }

            $categories = Category::whereIn('trendyol_id', $trendyolCategories)
                ->orWhereIn('id', $products->pluck('category_id')->toArray())
                ->select('id', 'name', 'parent_id')
                ->without('category_translations')
                ->orderBy('parent_id', 'desc')
                ->orderBy('order_level', 'desc');

            // Group categories by their parent_id
            $groupedCategories = $categories->get()->groupBy('parent_id');

            // Get the parent categories (parent_id != null)
            $parentCategories = Category::whereIn('id', $categories->pluck('parent_id'))->select('id', 'name')
                ->without('category_translations')
                ->orderBy('parent_id', 'desc')
                ->orderBy('order_level', 'desc')
                ->get();

            // Prepare the output
            $output = [];
            if ($parentCategories) {
                foreach ($parentCategories as $key => $parent) {
                    // Add parent category to output
                    $output[$key]['parent'] = $parent;

                    // Add children of the parent category (if any)
                    $output[$key]['children'] = $groupedCategories->get($parent->id) ?: [];
                }
            }

            $categories = $output;
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

        if (isset($trendyol['brands']) && count($trendyol['brands']) > 0) {
            $trendyolBrands = [];
            foreach ($trendyol['brands'][0]->degerler as $attribute_value) {
                array_push($trendyolBrands, $attribute_value->id);
            }
            $brands = Brand::WhereIn('trendyol_id', $trendyolBrands)->orWhereIn('id', $products->pluck('brand_id')->toArray())->get();
        }

        if (isset($trendyol['filters']) && count($trendyol['filters']) > 0) {
            foreach ($trendyol['filters'] as $filter) {
                $attrebute = Attribute::firstOrCreate([
                    'name'            => $filter->baslik,
                    'trendyol_id'     => $filter->filterKey,
                    'trendyol_urlKey' => $filter->urlKey
                ]);
                $attrebute->name  = $attrebute->getTranslation('name');
                array_push(
                    $allTrendyolFilters,
                    $attrebute
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
                        if (isset($attributeValues[$attrebute->id])) {
                            array_push($attributeValues[$attrebute->id], $attrttributeValue);
                        } else {
                            $attributeValues[$attrebute->id] = [$attrttributeValue];
                        }
                    }
                }
            }
        }
        return new ProductMiniCollection(filter_products($products)->paginate(24, ['*'], 'page', null, $trendyol['total']), $trendyolProducts, $allTrendyolFilters, $attributeValues, $colors, $categories, $brands);
    }

    public function suggestions()
    {
        $keyword = null;

        if (!empty(auth()->guard('sanctum')->user())) {
            $user = User::find(auth()->guard('sanctum')->user()->id);
            $searchSuggestions = auth()->guard('sanctum')->user()->searchResults()->latest()->take(10)->pluck('keyword')->toarray();
            if (count($searchSuggestions) > 0) {
                $keyword = $searchSuggestions[array_rand($searchSuggestions)];
            }
        }

        if ($keyword == null) {
            $searchSuggestions = DB::table('searches')
                ->orderBy('count', 'desc')
                ->take(30)
                ->get();

            if ($searchSuggestions->isNotEmpty()) {
                $randomRow = $searchSuggestions->random();
                $keyword = $randomRow->query;
            } else {
                $keyword = null;
            }
        }

        $suggestions_products = trendyol_suggestions_products($keyword);

        return new ProductMiniCollection([] , $suggestions_products);
    }
}
