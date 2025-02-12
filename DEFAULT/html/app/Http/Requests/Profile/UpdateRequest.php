<?php

namespace App\Http\Requests\Profile;

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
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'password' => 'nullable|min:8',
            'phone'=> 'required|string|max:255|regex:/^\+?[0-9]{10,15}$/',
        ];
    }
}
