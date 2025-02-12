<?php

namespace App\Http\Requests\Conversation;

use App\Http\Requests\GeneralRequest;

class MessageStoreRequest extends GeneralRequest
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
            'conversation_id' => 'required|exists:conversations,id,receiver_id,'.auth()->id(),
            'message' => 'required|string',
        ];
    }
}
