<?php

namespace App\Http\Requests\Home;

use App\Http\Requests\GeneralRequest;

class FilterShopRequest extends GeneralRequest
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
            'sort_by' => 'nullable|string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'brand' => 'nullable|string',
            'selected_categories' => 'nullable|array',
            'selected_categories.*' => 'nullable|integer|exists:categories,id',
            'rating' => 'nullable|numeric',
        ];
    }
}
