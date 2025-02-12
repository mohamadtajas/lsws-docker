<?php

namespace App\Http\Requests\Shop;

use App\Http\Requests\GeneralRequest;

class UpdateRequest extends GeneralRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'shop_id' => 'required|integer|exists:shops,id',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'slug' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'logo' => 'nullable|integer|exists:uploads,id',

            'shipping_cost' => 'nullable|numeric',

            'delivery_pickup_longitude' => 'nullable|numeric',
            'delivery_pickup_latitude' => 'nullable|numeric',

            'facebook' => 'nullable|url',
            'google' => 'nullable|url',
            'twitter' => 'nullable|url',
            'youtube' => 'nullable|url',
            'instagram' => 'nullable|url',

            'top_banner' => 'nullable|string',
            'sliders' => 'nullable|string',
            'banner_full_width_1' => 'nullable|string',
            'banners_half_width' => 'nullable|string',
            'banner_full_width_2' => 'nullable|string',
        ];
    }
}
