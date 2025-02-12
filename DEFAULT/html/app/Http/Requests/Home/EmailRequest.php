<?php

namespace App\Http\Requests\Home;

use App\Http\Requests\GeneralRequest;

class EmailRequest extends GeneralRequest
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
            'email' => 'required|email',
            'new_email_verificiation_code' => 'nullable|exists:users,new_email_verificiation_code'
        ];
    }
}
