<?php

namespace App\Http\Requests\Cart;

use App\Http\Requests\GeneralRequest;

class UpdateQuantityRequest extends GeneralRequest
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
            'id' => 'required|integer|exists:carts,id',
            'quantity' => 'required|integer|min:1',
        ];
    }
}
