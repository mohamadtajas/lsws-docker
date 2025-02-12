<?php

namespace App\Http\Requests\CheckOut;

use App\Http\Requests\GeneralRequest;

class StoreDeliveryInfoRequest extends GeneralRequest
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
        $rules =  [];

        foreach ($this->all() as $key => $value) {
            if (preg_match('/^shipping_type_\d+$/', $key)) {
                $rules[$key] = 'required|string|in:home_delivery,pickup_point,carrier';
            }

            if (preg_match('/^pickup_point_id_\d+$/', $key)) {
                $rules[$key] = 'nullable|string';
            }

            if (preg_match('/^carrier_id_\d+$/', $key)) {
                $rules[$key] = 'nullable|string';
            }
        }

        return $rules;
    }
}
