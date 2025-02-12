<?php

namespace App\Http\Requests\Seller;

use App\Http\Requests\GeneralRequest;
use App\Models\Shop;

class StoreRequest extends GeneralRequest
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
        $shop = Shop::findOrFail($this->seller);
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $shop->user_id ,
            'password' => 'required|string|min:6',
        ];
    }
}
