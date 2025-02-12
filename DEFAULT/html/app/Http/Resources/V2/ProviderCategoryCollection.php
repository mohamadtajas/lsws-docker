<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProviderCategoryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return  [
            'data' => $this->collection->map(function ($data) {
                return [
                    'id' => $data->id,
                    'name' => $data->getTranslation('name'),
                    'banner' => $data->banner,
                    'icon' => $data->icon,
                    'number_of_children' => 0,
                    'prefix' => isset($data->provider)  ? strtolower($data->provider->name) : '',
                    'links' => [
                        'products' => isset($data->provider)  ? route('api.products.category.provider', [strtolower($data->provider->name), $data->id]) : route('api.products.category', $data->id),
                        'sub_categories' => isset($data->provider)  ? route('subCategories.provider.index', [strtolower($data->provider->name), $data->id]) : route('subCategories.index', $data->id)
                    ]
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
