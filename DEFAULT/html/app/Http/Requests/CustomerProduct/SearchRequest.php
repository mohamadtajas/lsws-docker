<?php

namespace App\Http\Requests\CustomerProduct;

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
            'brand' => 'nullable|string|exists:brands,slug',
            'category' => 'nullable|string|exists:categories,slug',
            'sort_by' => 'nullable|string',
            'condition' => 'nullable|string|in:new,used',
        ];
    }
}
