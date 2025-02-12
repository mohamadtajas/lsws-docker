<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\GeneralRequest;

class GetPriceRequest extends GeneralRequest
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
            'id' => 'required|integer|exists:products,id',
            'quantity' => 'nullable|integer|min:0',
            'color' => 'nullable|string|max:255',
            'variants' => 'nullable|string',
        ];
    }
}
