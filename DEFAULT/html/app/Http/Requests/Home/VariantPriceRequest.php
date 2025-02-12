<?php

namespace App\Http\Requests\Home;

use App\Http\Requests\GeneralRequest;

class VariantPriceRequest extends GeneralRequest
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
            'color' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
        ];

        $input = $this->all();

        foreach ($input as $key => $value) {
            if (preg_match('/^attribute_id_\d+$/', $key)) {
                $rules[$key] = 'required|string';
            }
        }

        return $rules;
    }
}
