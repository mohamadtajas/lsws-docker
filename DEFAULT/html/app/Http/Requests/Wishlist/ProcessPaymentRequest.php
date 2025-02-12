<?php

namespace App\Http\Requests\Wishlist;

use App\Http\Requests\GeneralRequest;

class ProcessPaymentRequest extends GeneralRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|string',
        ];
    }
}
