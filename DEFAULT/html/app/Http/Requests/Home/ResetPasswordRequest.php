<?php

namespace App\Http\Requests\Home;

use App\Http\Requests\GeneralRequest;

class ResetPasswordRequest extends GeneralRequest
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
            'email' => 'required|email|exists:users,email',
            'code' => 'required',
            'password' => 'required|string|min:6|same:password_confirmation',
            'password_confirmation' => 'required|string|min:6',
            'remember' => 'nullable|boolean'
        ];
    }
}
