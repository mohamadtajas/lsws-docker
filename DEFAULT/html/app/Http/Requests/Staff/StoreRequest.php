<?php

namespace App\Http\Requests\Staff;

use App\Http\Requests\GeneralRequest;
use App\Models\Staff;

class StoreRequest extends GeneralRequest
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
        $user_id = Staff::find($this->staff)->user->id ?? null;
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,'.$user_id,
            'password' => 'required|string|min:6',
            'mobile' => 'required|string',
            'role_id' => 'required|integer|exists:roles,id',
        ];
    }
}
