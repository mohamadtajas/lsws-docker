<?php

namespace App\Http\Requests\CustomerProduct;

use App\Http\Requests\GeneralRequest;

class UpdateStatusRequest extends GeneralRequest
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
            'status' => 'required|in:0,1',
            'id' => 'required|exists:customer_products,id,user_id,'.auth()->id(),
        ];
    }
}
