<?php

namespace App\Http\Requests\Commission;

use App\Http\Requests\GeneralRequest;

class PayToSellerRequest extends GeneralRequest
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
            'shop_id' => 'required|integer|exists:shops,id',
            'amount' => 'required|numeric',
            'payment_option' => 'required|string|in:cash,bank_payment',
            'payment_withdraw' => 'nullable|string',
            'withdraw_request_id' => 'nullable|integer',
            'txn_code' => 'nullable|string',
            'additional_info' => 'nullable|string|max:500',
        ];
    }
}
