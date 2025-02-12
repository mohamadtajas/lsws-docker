<?php

namespace App\Http\Requests\TrendyolCategory;

use App\Http\Requests\GeneralRequest;

class KeyValueRequest extends GeneralRequest
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
            'values' => 'required|array',
            'values.*' => 'nullable|array',
        ];
    }
}
