<?php

namespace App\Http\Requests\Report;

use App\Http\Requests\GeneralRequest;

class WalletTransactionRequest extends GeneralRequest
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
            'payment_method' => 'nullable|string',
            'customer' => 'nullable|string',
            'date_range' => 'nullable|string',
            'export' => 'nullable|string|in:excel',
        ];
    }
}
