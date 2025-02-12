<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\GeneralRequest;

class CategoriesByTypeRequest extends GeneralRequest
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
            'digital' => 'required|in:0,1',
        ];
    }
}
