<?php

namespace App\Http\Requests\SellerWithdraw;

use App\Http\Requests\GeneralRequest;

class PaymentModalRequest extends GeneralRequest
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
            'id' => 'required|integer|exists:users,id',
            'seller_withdraw_request_id' => 'required|integer|exists:seller_withdraw_requests,id',
        ];
    }
}
