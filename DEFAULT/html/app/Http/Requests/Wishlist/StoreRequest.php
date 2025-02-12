<?php

namespace App\Http\Requests\Wishlist;

use App\Http\Requests\GeneralRequest;

class StoreRequest extends GeneralRequest
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
        if ($this->is('api/*')) {
            return [
                'user_id' => 'nullable|integer|exists:users,id',
                'product_id' => 'required|integer',
                'urunNo' => 'nullable|integer',
                'trendyol' => 'nullable|integer',
                'provider' => 'nullable|integer|exists:providers,id'
            ];
        }
        return [
            'id' => 'required|integer',
            'urunNo' => 'nullable|integer',
            'trendyol' => 'nullable|integer',
            'provider' => 'nullable|integer|exists:providers,id'
        ];
    }
}
