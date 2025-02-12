<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\GeneralRequest;

class IndexRequest extends GeneralRequest
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
            'keyword' => 'nullable|string|max:255',
            'sort_by' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'seller_id' => 'nullable|exists:shops,id',
            'brand' => 'nullable|string|exists:brands,slug',
            'brands' => 'nullable|array',
            'brands.*' => 'nullable|integer|exists:brands,id',
            'categories' => 'nullable|array',
            'categories.*' => 'nullable|integer|exists:categories,id',
            'color' => 'nullable|string|exists:colors,code',
            'selected_attribute_values' => 'nullable|array',
            'selected_attribute_values.*' => 'nullable|string|exists:attribute_values,uniqueId',
            'page' => 'nullable|integer',
        ];
    }
}
