<?php

namespace App\Http\Requests\CustomerPackage;

use App\Http\Requests\GeneralRequest;

class OfflinePaymentApprovalRequest extends GeneralRequest
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
            'id' => 'required|integer|exists:customer_package_payments,id',
            'status' => 'required|in:1,0'
        ];
    }
}
