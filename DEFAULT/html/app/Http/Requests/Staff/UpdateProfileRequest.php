<?php

namespace App\Http\Requests\Staff;

use App\Http\Requests\GeneralRequest;

class UpdateProfileRequest extends GeneralRequest
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
            'email' => 'required|email',
            'new_password' => 'nullable|min:8|same:confirm_password',
            'confirm_password' => 'nullable|min:8',
            'avatar' => 'nullable|integer|exists:uploads,id',
        ];
    }
}
