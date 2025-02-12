<?php

namespace App\Http\Requests\BusinessSettings;

use App\Http\Requests\GeneralRequest;

class UpdatePaymentMethodRequest extends GeneralRequest
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
        $paymentMethod = $this->input('payment_method');

        $rules = [
            'types' => 'required|array',
            'types.*' => 'required|string',
            'payment_method' => 'required|string',
            $paymentMethod . '_sandbox' => 'nullable|boolean',
        ];

        foreach ($this->input('types') as $key => $type) {
            $rules[$type] = 'required|string';
        }

        return $rules;
    }
}
