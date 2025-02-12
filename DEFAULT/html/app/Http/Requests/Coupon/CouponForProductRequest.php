<?php

namespace App\Http\Requests\Coupon;

use App\Http\Requests\GeneralRequest;

class CouponForProductRequest extends GeneralRequest
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
            'coupon_type' => 'required|in:product_base',
            'name' => 'required|string',
        ];
    }
}
