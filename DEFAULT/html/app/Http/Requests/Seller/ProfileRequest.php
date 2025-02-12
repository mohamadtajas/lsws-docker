<?php

namespace App\Http\Requests\Seller;

use App\Http\Requests\GeneralRequest;

class ProfileRequest extends GeneralRequest
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
            'name' => 'required|string',
            'phone' => 'required|string|max:255|regex:/^\+?[0-9]{10,15}$/',
            'new_password' => 'nullable|min:8|same:confirm_password',
            'confirm_password' => 'nullable|min:8',
            'photo' => 'nullable|integer|exists:uploads,id',
            'cash_on_delivery_status' => 'nullable|in:0,1',
            'bank_payment_status' => 'nullable|in:0,1',
            'bank_name' => 'nullable|string',
            'bank_acc_name' => 'nullable|string',
            'bank_acc_no' => 'nullable|string',
            'bank_routing_no' => 'nullable|string',
        ];
    }
}
