<?php

namespace App\Http\Requests\Wishlist;

use App\Http\Requests\GeneralRequest;

class IdRequest extends GeneralRequest
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
        if ($this->is('api/*')) {
            return [
                'product_id' => 'required|integer',
            ];
        }
        return [
            'id' => 'required|integer|exists:wishlists,id',
        ];
    }
}
