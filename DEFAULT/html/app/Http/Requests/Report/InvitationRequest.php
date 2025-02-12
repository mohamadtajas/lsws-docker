<?php

namespace App\Http\Requests\Report;

use App\Http\Requests\GeneralRequest;

class InvitationRequest extends GeneralRequest
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
            'invited_user' => 'nullable|email|exists:users,email',
            'invited_by_user' => 'nullable|email|exists:users,email',
            'used' => 'nullable|string|in:used,unused',
            'date_range' => 'nullable|string',
            'export' => 'nullable|string|in:excel',
        ];
    }
}
