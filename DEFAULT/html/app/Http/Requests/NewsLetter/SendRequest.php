<?php

namespace App\Http\Requests\NewsLetter;

use App\Http\Requests\GeneralRequest;

class SendRequest extends GeneralRequest
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
            'user_emails' => 'required|array',
            'user_emails.*' => 'required|email|exists:users,email',
            'subscriber_emails' => 'required|array',
            'subscriber_emails.*' => 'required|email|exists:subscribers,email',
            'subject' => 'required|string',
            'content' => 'required|string',
        ];
    }
}
