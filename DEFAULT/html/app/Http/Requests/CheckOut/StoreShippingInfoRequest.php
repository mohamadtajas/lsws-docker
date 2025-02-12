<?php

namespace App\Http\Requests\CheckOut;

use App\Http\Requests\GeneralRequest;

class StoreShippingInfoRequest extends GeneralRequest
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
            'address_id' => 'required|integer|exists:addresses,id',
        ];
    }
}
