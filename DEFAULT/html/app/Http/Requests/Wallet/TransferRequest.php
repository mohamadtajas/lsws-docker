<?php

namespace App\Http\Requests\Wallet;

use App\Http\Requests\GeneralRequest;

class TransferRequest extends GeneralRequest
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
            'amount' => 'required|numeric|min:0',
            'email' => 'required|email|exists:users,email,user_type,customer',
        ];
    }
}
