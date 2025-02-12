<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\GeneralRequest;

class AdminRequest extends GeneralRequest
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
            'type' => 'nullable|string',
            'search' => 'nullable|string|max:255',
        ];
    }
}
