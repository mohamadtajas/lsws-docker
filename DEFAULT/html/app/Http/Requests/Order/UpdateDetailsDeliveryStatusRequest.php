<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\GeneralRequest;

class UpdateDetailsDeliveryStatusRequest extends GeneralRequest
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
            'order_detail_id' => 'required|exists:order_details,id',
            'status' => 'required|string|in:pending,confirmed,picked_up,on_the_way,delivered,cancelled',
        ];
    }
}
