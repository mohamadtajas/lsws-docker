<?php

namespace App\Http\Requests\CustomerProduct;

use App\Http\Requests\GeneralRequest;

class LangRequest extends GeneralRequest
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
            'lang' => 'required|string|exists:languages,code,status,1',
        ];
    }
}
