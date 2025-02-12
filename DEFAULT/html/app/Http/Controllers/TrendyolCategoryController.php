<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrendyolCategory\IndexRequest;
use App\Http\Requests\TrendyolCategory\KeyValueRequest;
use App\Http\Requests\TrendyolCategory\UpdateRequest;
use App\Models\TrendyolCategory;

class TrendyolCategoryController extends Controller
{
    protected $category;

    public function __construct(TrendyolCategory $category)
    {
        $this->category = $category;
        $this->middleware(['permission:trendyol_tax'])->only('show_trendyol_tax', 'key_value_store_trendyol_tax');
        $this->middleware(['permission:trendyol_black_list'])->only('show_trendyol_black_list', 'update_trendyol_black_list');
    }

    public function show_trendyol_tax(IndexRequest $request)
    {
        $sort_search = null;
        if ($request->has('search')) {
            $sort_search = $request->search;
            $filteredCategories = $this->category->where('name', 'Like', '%' . $sort_search . '%');
            $trendyolCategories = $this->category->paginate($filteredCategories, 50);
        } else {
            $trendyolCategories = $this->category->paginate($this->category->all(), 50);
        }

        return view('backend.setup_configurations.tax.trendyol', compact('trendyolCategories', 'sort_search'));
    }

    public function key_value_store_trendyol_tax(KeyValueRequest $request)
    {
        foreach ($request->values as $key => $value) {
            $trendyolCategory = $this->category->find($key);
            if ($value['percent'] < 0 || $value['flat'] < 0) {
                flash(translate('Something went wrong'))->error();
                return back();
            }
            if ($trendyolCategory) {
                $trendyolCategory->percent_tax = $value['percent'];
                $trendyolCategory->flat_tax = $value['flat'];
                $trendyolCategory->percent_discount = $value['percent_discount'];
                $trendyolCategory->flat_discount = $value['flat_discount'];

                $updatedCategory = (array) $trendyolCategory;

                $this->category->update($key, $updatedCategory);
            }
        }
        $admin = new AdminController();
        $admin->clearCache();
        flash(translate('Trendyol tax updated'))->success();
        return back();
    }

    public function show_trendyol_black_list(IndexRequest $request)
    {
        if ($request->has('search')) {
            $sort_search = $request->search;
            $filteredCategories = $this->category->where('name', 'Like', '%' . $sort_search . '%');
            $trendyolCategories = $this->category->paginate($filteredCategories, 50);
        } else {
            $trendyolCategories = $this->category->paginate($this->category->all(), 50);
        }

        return view('backend.setup_configurations.black_list_trendyol', compact('trendyolCategories'));
    }

    public function update_trendyol_black_list(UpdateRequest $request)
    {
        $trendyolCategory = $this->category->find($request->categoryId);
        if ($trendyolCategory) {
            if ($trendyolCategory->active == 1) {
                $trendyolCategory->active = "0";

                $updatedCategory = (array) $trendyolCategory;
                $this->category->update($request->categoryId, $updatedCategory);
            } else {
                $trendyolCategory->active = "1";

                $updatedCategory = (array) $trendyolCategory;
                $this->category->update($request->categoryId, $updatedCategory);
            }
            return array(
                'status' => 1,
                'request' => $trendyolCategory
            );
        }
        return array(
            'status' => 0
        );
    }
}
