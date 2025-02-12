<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProviderProductMiniCollection extends ResourceCollection
{
    public $collection;
    public $additionalData;
    public $filters;
    public $filtersValues;
    public $colors;
    public $categories;
    public $brands;

    public function __construct($resource, $filters = [], $filtersValues = [], $colors = [], $categories = [], $brands = [])
    {
        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
        $this->filters = $filters;
        $this->filtersValues = $filtersValues;
        $this->colors = $colors;
        $this->categories = $categories;
        $this->brands = $brands;
    }

    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {
                return [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'thumbnail_image' => $data['thumbnail'],
                    'has_discount' => $data['unit_price'] != $data['new_price'],
                    'discount' => "-" . round(100 - ($data['new_price'] * 100) / $data['unit_price'], 0)  . "%",
                    'stroked_price' => (string) single_price($data['unit_price']),
                    'main_price' =>(string) single_price($data['new_price']),
                    'rating' => (float) $data['rating'],
                    'sales' => (int) 0,
                    'is_wholesale' => false,
                    'prefix' => $data['provider'],
                    'links' => [
                        'details' => route('products.provider', [$data['provider'], $data['id']]),
                    ]
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'filters' => $this->filters,
            'filtersValues' => $this->filtersValues,
            'colors' => $this->colors,
            'categories' => $this->categories,
            'filterBrands' => $this->brands,
            'success' => true,
            'status' => 200
        ];
    }
}
