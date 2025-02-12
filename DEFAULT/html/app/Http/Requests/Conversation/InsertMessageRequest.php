<?php

namespace App\Http\Requests\Conversation;

use App\Http\Requests\GeneralRequest;

class InsertMessageRequest extends GeneralRequest
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
            'conversation_id' => 'required|integer|exists:conversations,id',
            'message' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
        ];
    }
}
