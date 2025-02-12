<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductMiniCollection extends ResourceCollection
{
    public $collection;
    public $additionalData ;
    public $filters ;
    public $filtersValues ;
    public $colors ;
    public $categories ;
    public $brands ;
    public $category;
    public $brand;

    public function __construct($resource , $additionalData = null , $filters = [] , $filtersValues = [] , $colors = [] , $categories = [] , $brands = [] , $category = null , $brand = null)
    {
        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
        $this->additionalData = $additionalData ;
        $this->filters = $filters;
        $this->filtersValues = $filtersValues ;
        $this->colors = $colors ;
        $this->categories = $categories ;
        $this->brands = $brands ;
        $this->category = $category;
        $this->brand = $brand;
    }

    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {
                $wholesale_product =
                    ($data->wholesale_product == 1) ? true : false;
                return [
                    'id' => $data->id,
                    'name' => $data->getTranslation('name'),
                    'thumbnail_image' => uploaded_asset($data->thumbnail_img),
                    'has_discount' => home_base_price($data, false) != home_discounted_base_price($data, false),
                    'discount' => "-" . discount_in_percentage($data) . "%",
                    'stroked_price' => home_base_price($data),
                    'main_price' => home_discounted_base_price($data),
                    'rating' => (float) $data->rating,
                    'sales' => (int) $data->num_of_sale,
                    'is_wholesale' => $wholesale_product,
                    'links' => [
                        'details' => route('products.show', $data->id),
                    ]
                ];
            }),
            'trendyol_data' => $this->additionalData,
            'category' => [
                'id' => $this->category != null ? $this->category->id : 0 ,
                'name' =>  $this->category != null ? $this->category->name : ''
            ],
            'brand' => [
                'id' => $this->brand != null ? $this->brand->id : 0 ,
                'name' =>  $this->brand != null ? $this->brand->name : ''
            ]
        ];
    }

    public function with($request)
    {
        return [
            'filters' => $this->filters ,
            'filtersValues' => $this->filtersValues,
            'colors' => $this->colors,
            'categories' => $this->categories,
            'filterBrands' => $this->brands,
            'success' => true,
            'status' => 200
        ];
    }
}
