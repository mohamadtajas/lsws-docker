<?php

namespace App\Http\Requests\BusinessSettings;

use App\Http\Requests\GeneralRequest;

class UpdateSellerVerificationFormRequest extends GeneralRequest
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
            'type' => 'required|array',
            'type.*' => 'required|string|in:text,select,multi_select,radio,file',
            'label' => 'required|array',
            'label.*' => 'required|string',
            'option' => 'nullable|array',
        ];
        if ($this->input('option') != null) {
            foreach ($this->input('option') as  $option) {
                $rules['options_' . $option] = 'required|array';
            }
        }
        return $rules;
    }
}
