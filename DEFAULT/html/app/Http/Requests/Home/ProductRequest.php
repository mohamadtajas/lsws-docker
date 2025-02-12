<?php

namespace App\Http\Requests\Home;

use App\Http\Requests\GeneralRequest;

class ProductRequest extends GeneralRequest
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
            'type' => 'nullable|string|in:query,review',
            'product_referral_code' => 'nullable|string',
        ];
    }
}
