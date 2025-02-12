<?php

namespace App\Http\Requests\Address;

use App\Http\Requests\GeneralRequest;

class UpdateShippingTypeInCartRequest extends GeneralRequest
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
            'shipping_type' => 'required|string|in:home_delivery,pickup_point,carrier_base',
            'shipping_id' => 'required|integer',
        ];
    }
}
