<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\GeneralRequest;

class AssignDeliveryBoyRequest extends GeneralRequest
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
            'order_id' => 'required|exists:orders,id',
            'delivery_boy' => 'required|exists:users,id',

        ];
    }
}
