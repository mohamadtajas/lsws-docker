<?php

namespace App\Http\Requests\Wallet;

use App\Http\Requests\GeneralRequest;

class RechargeSyCardRequest extends GeneralRequest
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
            'card_number' => 'required|string|min:16|max:19',
            'card_serial' => 'required|string|min:6|max:7',
        ];
    }
}
