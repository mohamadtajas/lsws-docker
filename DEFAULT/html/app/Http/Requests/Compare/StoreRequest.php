<?php

namespace App\Http\Requests\Compare;

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
        return [
            'id' => 'required|integer',
            'product_name' => 'nullable|string',
            'trendyol' => 'nullable|integer|in:1,0',
            'urunNo' => 'nullable|integer',
            'provider' => 'nullable|integer|exists:providers,id'
        ];
    }
}
