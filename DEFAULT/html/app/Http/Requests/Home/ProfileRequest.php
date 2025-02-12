<?php

namespace App\Http\Requests\Home;

use App\Http\Requests\GeneralRequest;

class ProfileRequest extends GeneralRequest
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
            'name' => 'required|string',
            'address' => 'nullable|string',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'phone' => 'nullable|string|max:255|regex:/^\+?[0-9]{10,15}$/',
            'new_password' => 'nullable|string|min:6',
            'confirm_password' => 'nullable|string|min:6',
            'photo' => 'nullable|integer|exists:uploads,id',
        ];
    }
}
