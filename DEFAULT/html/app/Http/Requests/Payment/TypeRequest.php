<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\GeneralRequest;

class TypeRequest extends GeneralRequest
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
            'mode' => 'nullable|string',
            'list' => 'nullable|string',
        ];
    }
}
