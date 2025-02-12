<?php

namespace App\Http\Requests\FlashDeal;

use App\Http\Requests\GeneralRequest;

class ProductDiscountRequest extends GeneralRequest
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
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|integer|distinct|exists:products,id',
        ];
    }
}
