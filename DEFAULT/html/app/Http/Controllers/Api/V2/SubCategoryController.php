<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\CategoryCollection;
use App\Http\Resources\V2\ProviderCategoryCollection;
use App\Models\Category;
use App\Models\Provider;

class SubCategoryController extends Controller
{
    public function index(string $id)
    {
        return new CategoryCollection(Category::where('parent_id', $id)->get());
    }

    public function provider(string $provider , string $id)
    {
        $provider = Provider::where('name', $provider)->first();
        $category = Category::find($id);
        $category = $category ?: $provider->Service()->category($id);
        $subCategories = $provider->Service()->categories($category->external_id);
        return new ProviderCategoryCollection($subCategories);
    }
}
