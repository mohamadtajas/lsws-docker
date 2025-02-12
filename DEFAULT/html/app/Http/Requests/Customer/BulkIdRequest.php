<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\GeneralRequest;

class BulkIdRequest extends GeneralRequest
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
            'id' => 'required|array',
            'id.*' => 'required|integer|exists:users,id',
        ];
    }
}
