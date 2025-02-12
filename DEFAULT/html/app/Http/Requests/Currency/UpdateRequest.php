<?php

namespace App\Http\Requests\Currency;

use App\Http\Requests\GeneralRequest;
class UpdateRequest extends GeneralRequest
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
            'id' => 'required|integer|exists:currencies,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'symbol' => 'required|string|max:255',
            'exchange_rate' => 'required|numeric',
        ];
    }
}
