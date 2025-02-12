<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\GeneralRequest;

class UpdateRequest extends GeneralRequest
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
            'permissions' => 'required|array',
            'permissions.*' => 'required|integer|exists:permissions,id',
            'lang' => 'required|string|exists:languages,code,status,1',
        ];
    }
}
