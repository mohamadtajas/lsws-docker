<?php

namespace App\Http\Requests\Cart;

use App\Http\Requests\GeneralRequest;

class ApiAddToCartRequest extends GeneralRequest
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
            'trendyol' => 'nullable|integer|in:1,0',
            'id' => 'required|integer',
            'urunNo' => 'nullable|integer',
            'quantity' => 'required|integer|min:1',
            'variant' => 'nullable|string',
            'provider' => 'nullable|integer'
        ];
    }
}
