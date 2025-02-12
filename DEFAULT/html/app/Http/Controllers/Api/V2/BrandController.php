<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Brand\NameRequest;
use App\Http\Resources\V2\BrandCollection;
use App\Models\Brand;
use App\Utility\SearchUtility;
use Cache;

class BrandController extends Controller
{
    public function index(NameRequest $request)
    {
        $brand_query = Brand::query();
        if($request->name != "" || $request->name != null){
            $brand_query->where('name', 'like', '%'.$request->name.'%');
            SearchUtility::store($request->name);
        }
        return new BrandCollection($brand_query->orderBy('logo' , 'desc')->paginate(10));
    }

    public function top()
    {
        return Cache::remember('app.top_brands', 86400, function(){
            return new BrandCollection(Brand::where('top', 1)->orderBy('logo' , 'desc')->get());
        });
    }
}
