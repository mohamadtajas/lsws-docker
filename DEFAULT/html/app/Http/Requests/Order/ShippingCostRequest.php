<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\GeneralRequest;

class ShippingCostRequest extends GeneralRequest
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
            'seller_list' => 'required|array',
            'seller_list.*.seller_id' => 'required|integer|exists:users,id',
            'seller_list.*.shipping_type' => 'required|string|in:pickup_point,home_delivery,carrier',
            'seller_list.*.shipping_id' => 'required_if:seller_list.*.shipping_type,pickup_point,carrier|nullable|integer'
        ];
    }
}
