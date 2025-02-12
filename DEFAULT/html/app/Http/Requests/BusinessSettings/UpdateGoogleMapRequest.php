<?php

namespace App\Http\Requests\BusinessSettings;

use App\Http\Requests\GeneralRequest;

class UpdateGoogleMapRequest extends GeneralRequest
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
        $rules = [
            'types' => 'required|array',
            'types.*' => 'required|string',
            'google_map' => 'nullable|boolean'
        ];

        foreach ($this->input('types') as $type) {
            $rules[$type] = 'required|string';
        }

        return $rules;
    }

}
