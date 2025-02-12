<?php

namespace App\Http\Requests\City;

use App\Http\Requests\GeneralRequest;
use Illuminate\Validation\Rule;

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
        return [
            'name' => 'required|string',
            'state_id' => 'required|integer|exists:states,id',
            'cost' => 'required|numeric',
            'lang' => [
                'nullable',
                'string',
                Rule::exists('languages', 'code')->where(function ($query) {
                    return $query->where('status', 1); // Only allow languages with status = 1
                }),
            ]
        ];
    }
}
