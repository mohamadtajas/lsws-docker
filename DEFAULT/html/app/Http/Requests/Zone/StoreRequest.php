<?php

namespace App\Http\Requests\Zone;

use App\Http\Requests\GeneralRequest;

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
            'country_id' => 'required|array',
            'country_id.*' => 'required|integer|exists:countries,id',
        ];
    }

    protected function prepareForValidation()
    {
        // Get all rule keys, including array keys
        $rules = array_keys($this->rules());

        // Replace the request input with only the fields that match the rule keys
        $filteredInput = $this->filterInput($this->all(), $rules);

        if ($this->has('button')) {
            $filteredInput['button'] = $this->input('button');
        }

        $this->replace($filteredInput);

        $this->merge([
            'status'    => 1
        ]);
    }
}
