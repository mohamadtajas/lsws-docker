<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\GeneralRequest;

class AuctionProductBidRequest extends GeneralRequest
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
            'product_id' => 'required|exists:products,id',
            'amount' => 'required|numeric',
        ];
    }
}
