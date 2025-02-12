<?php

namespace App\Http\Requests\Brand;

use App\Http\Requests\GeneralRequest;
use Illuminate\Validation\Rule;

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
            'name' => 'required|string|max:255',
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'slug' => 'nullable|string|max:255',
            'logo' => 'required|integer|exists:uploads,id',
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
