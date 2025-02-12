<?php

namespace App\Http\Requests\Wallet;

use App\Http\Requests\GeneralRequest;

class OfflineRechageRequest extends GeneralRequest
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
            'payment_option' => 'required',
            'trx_id' => 'required',
            'photo' => 'required|integer|exists:uploads,id',
        ];
    }
}
