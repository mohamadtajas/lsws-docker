<?php

namespace App\Http\Requests\Shop;

use App\Http\Requests\GeneralRequest;
use App\Models\BusinessSetting;

class VerifyFormRequest extends GeneralRequest
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
        $rules = [];
        $elements = json_decode(BusinessSetting::where('type', 'verification_form')->first()->value);

        foreach ($elements as $index => $element) {
            switch ($element->type) {
                case 'text':
                    $rules['element_' . $index] = 'required|string|max:255';
                    break;

                case 'select':
                case 'radio':
                    $rules['element_' . $index] = 'required|string';
                    break;

                case 'multi_select':
                    $rules['element_' . $index] = 'required|array';
                    $rules['element_' . $index . '.*'] = 'required|string';
                    break;

                case 'file':
                    $rules['element_' . $index] = 'required|file|mimes:jpeg,png,jpg,gif,doc,pdf';
                    break;

                default:
                    // Optional handling for unknown types
                    $rules['element_' . $index] = 'nullable';
                    break;
            }
        }

        return $rules;
    }
}
