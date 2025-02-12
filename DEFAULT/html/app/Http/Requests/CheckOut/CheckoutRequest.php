<?php

namespace App\Http\Requests\CheckOut;

use App\Http\Requests\GeneralRequest;

class CheckoutRequest extends GeneralRequest
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
            'payment_option' => 'required|string',
            'trx_id' => 'nullable|string',
            'photo' => 'nullable|integer|exists:uploads,id',
            'additional_info' => 'nullable|string|max:500',
        ];
    }
}
