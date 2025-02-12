<?php

namespace App\Http\Requests\Color;

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
        $colorId = $this->route('id');
        return [
            'name' => 'required|string',
            'code' => [
                'required',
                'max:255',
                'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/i', // Validate hex color format
                Rule::unique('colors')->ignore($colorId), // Ignore the current record's ID when checking uniqueness
            ]
        ];
    }
}
