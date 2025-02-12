<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\GeneralRequest;

class ForgetPasswordRequest extends GeneralRequest
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
            'email_or_phone' => 'required|string',
            'send_code_by' => 'required|string|in:email,phone',
        ];
    }
}
