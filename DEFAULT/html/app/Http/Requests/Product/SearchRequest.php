<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\GeneralRequest;

class SearchRequest extends GeneralRequest
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
            'brands' => 'nullable|array',
            'brands.*' => 'nullable|integer|exists:brands,id',
            'categories' => 'nullable|array',
            'categories.*' => 'nullable|integer|exists:categories,id',
            'sort_key' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'min' => 'nullable|numeric',
            'max' => 'nullable|numeric',
            'color' => 'nullable|string|exists:colors,code',
            'selected_attribute_values' => 'nullable|array',
            'selected_attribute_values.*' => 'nullable|string|exists:attribute_values,uniqueId',
            'page' => 'nullable|integer',
        ];
    }
}
