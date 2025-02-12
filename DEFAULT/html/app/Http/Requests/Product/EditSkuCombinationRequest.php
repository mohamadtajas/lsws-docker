<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class EditSkuCombinationRequest extends FormRequest
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
            'id' => 'required|integer|exists:products,id',
            'colors_active' => 'nullable|integer|in:1,0',
            'colors' => 'nullable|array',
            'colors.*' => 'nullable|string|exists:colors,code',
            'unit_price' => 'required|numeric|min:0',
            'name' => 'required|string',
            'choice_no' => 'nullable|array',
            'choice_no.*' => 'nullable|integer',
            'choice_options_*' => 'nullable|array',
            'choice_options_*.*' => 'nullable|string',
        ];

        if($this->input('choice_no')) {
            foreach ($this->input('choice_no') as $no) {
                $rules['choice_options_' . $no] = 'nullable|array';
                $rules['choice_options_' . $no . '.*'] = 'nullable|string';
            }
        }

        return $rules;
    }
}
