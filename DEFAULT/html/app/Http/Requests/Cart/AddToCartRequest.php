<?php

namespace App\Http\Requests\Cart;

use App\Http\Requests\GeneralRequest;

class AddToCartRequest extends GeneralRequest
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
        $rules = [
            'trendyol' => 'nullable|integer|in:1,0',
            'provider' => 'nullable|integer|exists:providers,id',
            'id' => 'required|integer',
            'urunNo' => 'nullable|integer',
            'quantity' => 'required|integer|min:1',
            'color' => 'nullable|string',
        ];

        foreach ($this->all() as $key => $value) {
            if (preg_match('/^attribute_id_\d+$/', $key)) {
                $rules[$key] = 'nullable|string';
            }
        }

        return $rules;
    }
}
