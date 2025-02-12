<?php

namespace App\Http\Requests\City;

use App\Http\Requests\GeneralRequest;

class IndexRequest extends GeneralRequest
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
            'sort_city' => 'nullable|string',
            'sort_state' => 'nullable|string',
        ];
    }
}
