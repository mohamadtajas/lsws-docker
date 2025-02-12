<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\GeneralRequest;
use Illuminate\Validation\Rule;

class LangRequest extends GeneralRequest
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
            'lang' => [
                'required',
                'string',
                Rule::exists('languages', 'code')->where(function ($query) {
                    return $query->where('status', 1); // Only allow languages with status = 1
                }),
            ]
        ];
    }
}
