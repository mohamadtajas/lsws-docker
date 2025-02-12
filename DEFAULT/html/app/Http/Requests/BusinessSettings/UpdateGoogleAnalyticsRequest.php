<?php

namespace App\Http\Requests\BusinessSettings;

use App\Http\Requests\GeneralRequest;

class UpdateGoogleAnalyticsRequest extends GeneralRequest
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
            'google_analytics' => 'nullable|boolean'
        ];

        foreach ($this->input('types') as $key => $type) {
            $rules[$type] = 'required|string';
        }

        return $rules;
    }
}
