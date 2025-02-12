<?php

namespace App\Http\Requests\SupportTicket;

use App\Http\Requests\GeneralRequest;

class SellerStoreRequest extends GeneralRequest
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
            'ticket_id' => 'required|integer|exists:tickets,id',
            'reply' => 'required|string',
            'attachments' => 'nullable|string',
            'user_id' => 'required|integer|exists:users,id'
        ];
    }
}
