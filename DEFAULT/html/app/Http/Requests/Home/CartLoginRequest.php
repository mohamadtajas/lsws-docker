<?php

namespace App\Http\Requests\Home;

use App\Http\Requests\GeneralRequest;

class CartLoginRequest extends GeneralRequest
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
            'country_code' => 'nullable|string|exists:countries,code',
            'phone' => 'nullable|exists:users,phone',
            'email' => 'required|exists:users,email',
            'password' => 'required|string|min:6',
            'remember' => 'nullable|boolean'
        ];
    }
}
