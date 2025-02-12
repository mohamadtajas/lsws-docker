<?php

namespace App\Http\Requests\Attribute;

use App\Http\Requests\GeneralRequest;

class StoreValueRequest extends GeneralRequest
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
            'attribute_id' => 'required|integer|exists:attributes,id',
            'value' => 'required|string',
        ];
    }
}
